<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegistryController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->table = 'registry';
        $this->publicColumns = [
            'id', 'user', 'questionary', 'question', 'answer', 'created_at'
        ];
        $this->rulesForListing = [
            'user' => 'nullable|exists:user,id',
            'questionary' => 'nullable|exists:questionary,id',
            'question' => 'nullable|exists:question,id',
            'answer' => 'nullable|exists:answer,id',
        ];
        $this->rulesForCreate = [
            'user' => 'nullable|exists:user,id',
            'questionary' => 'required|exists:questionary,id',
            'question' => 'required|exists:question,id',
            'answer' => 'required|exists:answer,id',
        ];
        $this->rulesForUpdate = [];
    }

    /**
     * 
     * @param Request $request
     * @param int $id
     * @return bool
     */
    public function checkAcl(Request $request, $id = null)
    {
        switch ($this->getRouteName($request)) {
            case 'registry.listing':
            case 'registry.read':
                if (!in_array($request->appUser->role, [self::ROLE_ADMINISTRATOR, self::ROLE_INSTRUCTOR])) {
                    return false;
                }
                break;
            case 'registry.saveAttempt':
                if (!in_array($request->appUser->role, [self::ROLE_USER])) {
                    return false;
                }
                if ($request->appUser->role == self::ROLE_USER && $request->appUser->id != $id) {
                    return false;
                }
                break;
            case 'registry.create':
            case 'registry.update':
            case 'registry.delete':
                break;

            default:
                return false;
        }

        return true;
    }

    /**
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function delete(Request $request, $id)
    {
        return $this->notAllowed();
    }

    /**
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        return $this->notAllowed();
    }

    /**
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request)
    {
        return $this->notAllowed();
    }

    /**
     * 
     * @param Request $request
     * @param int $id
     * @param int $idQuestionary
     * @return JsonResponse
     */
    public function saveAttempt(Request $request, $id, $idQuestionary)
    {
        if (!$this->checkAcl($request, $id)) {
            return $this->unauthorized();
        }

        // validación si existen registros para el usuario y examen
        $queryNumRegistries = 'SELECT COUNT(*) AS `num_registries` FROM `registry` WHERE `user` = ? AND `questionary` = ?';
        $resultNumRegistries = $this->getDb()->selectOne($queryNumRegistries, [$id, $idQuestionary]);

        if ($resultNumRegistries->num_registries > 0) {
            return response()->json(['registries' => ['El usuario ya ha realizado el examen/encuesta']], 422);
        }

        // validación si el usuario puede hacer el examen por pertenecer a uno de sus grupos
        $queryValidQuestionnaires = 'SELECT `id` '.
            'FROM `questionary` '.
            'WHERE '.
                '`group` IN (SELECT distinct(`group`) FROM `user_group` WHERE `user` = ?) AND '.
                '`active` = ?';
        $resultValidQuestionnaires = $this->getDb()->select($queryValidQuestionnaires, [$id, 1]);
        $validQuestionnaires = [];

        foreach ($resultValidQuestionnaires as $resultValidQuestionary) {
            $validQuestionnaires[] = $resultValidQuestionary->id;
        }

        if (!in_array($idQuestionary, $validQuestionnaires)) {
            return response()->json(['registries' => ['El usuario no tiene permisos para realizar el examen/encuesta']], 422);
        }

        // cargamos las preguntas y respuestas válidas
        $validQuestions = [];
        $validAnswers = [];
        $queryQuestions = 'SELECT '.
                'q.`id` AS `question_id`,'.
                'a.`id` AS `answer_id` '.
            'FROM '.
                '`question` AS q '.
                'INNER JOIN `answer` AS a ON a.`question` = q.`id` '.
            'WHERE '.
                'q.`questionary` = ? '.
            'ORDER BY '.
                'q.`id`, a.`id`';
        $resultQuestions = $this->getDb()->select($queryQuestions, [$idQuestionary]);

        foreach ($resultQuestions as $resultQuestion) {
            if (!in_array($resultQuestion->question_id, $validQuestions)) {
                $validQuestions[] = $resultQuestion->question_id;
            }
            if (!isset($validAnswers[$resultQuestion->question_id])) {
                $validAnswers[$resultQuestion->question_id] = [];
            }
            $validAnswers[$resultQuestion->question_id][] = $resultQuestion->answer_id;
        }

        // validaciones formato
        $data = $this->validate($request, [
            'registries' => 'required|array|min:1'
        ]);
        $registries = $data['registries'];
        $errors = $this->validateRegistries($registries, $validQuestions, $validAnswers);

        if (!empty($errors)) {
            return response()->json(['registries' => $errors], 422);
        }
        
        // realizamos insert
        $insertRegistries = 'INSERT INTO `registry` (`user`,`questionary`,`question`,`answer`) VALUES ';
        $insertRegistries2 = '';
        $insertRegistriesValues = [];

        foreach ($registries as $registry) {
            $insertRegistries2 .= ($insertRegistries2 !== '' ? ',' : '').'(?,?,?, ?)';
            array_push($insertRegistriesValues, $id, $idQuestionary, $registry['question'], $registry['answer']);
        }

        $insertAnswersOk = $this->getDb()->insert($insertRegistries.$insertRegistries2, $insertRegistriesValues);

        if (!$insertAnswersOk) {
            return response()->json(['error' => ['Los datos no han podido ser creados']], 500);
        }

        return response()->json($data, 201);
    }

    /**
     * 
     * @param array $registries
     * @param array $validQuestions
     * @param array $validAnswers
     * @return array
     */
    private function validateRegistries(array $registries, array $validQuestions, array $validAnswers)
    {
        $errors = [];
        $questionsSent = [];

        foreach ($registries as $i => $r) {
            if (!is_array($r)) {
                $errors[] = 'Error en registro #'.($i+1).': el registro debe ser un objeto';
                continue;
            }

            if (!isset($r['question']) || (isset($r['question']) && !in_array($r['question'], $validQuestions))) {
                $errors[] = 'Error en registro #'.($i+1).': la pregunta no es válida';
                continue;
            } else {
                if (in_array($r['question'], $questionsSent)) {
                    $errors[] = 'Error en registro #'.($i+1).': ya estás enviando un registro para la misma pregunta';
                } else {
                    $questionsSent[] = $r['question'];
                }
            }

            if (!isset($r['answer']) || (isset($r['answer']) && !in_array($r['answer'], $validAnswers[$r['question']]))) {
                $errors[] = 'Error en registro #'.($i+1).': la respuesta no es válida';
            }
        }

        if (empty($errors) && count($questionsSent) !== count($validQuestions)) {
            $errors[] = 'Faltan preguntas por responder';
        }

        return $errors;
    }
}
