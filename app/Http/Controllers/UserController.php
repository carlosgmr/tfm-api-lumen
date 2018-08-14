<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->table = 'user';
        $this->publicColumns = [
            'id', 'email', 'name', 'surname_1', 'surname_2', 'created_at', 'updated_at', 'active',
        ];
        $this->rulesForCreate = [
            'email' => 'required|email|max:256|unique:'.$this->table.',email',
            'password' => 'required|min:4|max:32',
            'name' => 'required|min:2|max:64',
            'surname_1' => 'required|min:2|max:64',
            'surname_2' => 'nullable|min:2|max:64',
            'active' => 'required|boolean',
        ];
        $this->rulesForUpdate = [
            'email' => 'nullable|email|max:256|unique:'.$this->table.',email,###ID###,id',
            'password' => 'nullable|min:4|max:32',
            'name' => 'nullable|min:2|max:64',
            'surname_1' => 'nullable|min:2|max:64',
            'surname_2' => 'nullable|min:2|max:64',
            'active' => 'nullable|boolean',
        ];

        $this->relations = [
            'group' => [
                'join' => [
                    'table' => 'user_group',
                    'publicColumns' => ['added_at'],
                    'fkColumn' => 'group',
                    'whereColumn' => 'user',
                ],
                'publicColumns' => ['id', 'name', 'description', 'created_at', 'updated_at', 'active'],
            ]
        ];
    }

    /**
     * Aplica un formato personalizado a los datos pasados
     * @param array $data
     * @return array
     */
    public function formatData($data)
    {
        if (isset($data['password']) && $data['password'] !== null && $data['password'] !== '') {
            /* @var $hashManager \Illuminate\Hashing\HashManager */
            $hashManager = app('hash');
            $data['password'] = $hashManager->make($data['password'], ['rounds' => 10]);
        }

        return $data;
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
            case 'user.read':
            case 'user.listing':
            case 'user.listing.group':
                if (!in_array($request->appUser->role, [self::ROLE_ADMINISTRATOR, self::ROLE_INSTRUCTOR, self::ROLE_USER])) {
                    return false;
                }
                break;
            case 'user.update':
                if (!in_array($request->appUser->role, [self::ROLE_ADMINISTRATOR, self::ROLE_USER])) {
                    return false;
                }
                if ($request->appUser->role == self::ROLE_USER && $request->appUser->id != $id) {
                    return false;
                }
                break;
            case 'user.create':
            case 'user.delete':
            case 'user.current.group':
                if (!in_array($request->appUser->role, [self::ROLE_ADMINISTRATOR])) {
                    return false;
                }
                break;
            case 'user.listing.questionnairesMade':
            case 'user.read.questionaryDetails':
                if (!in_array($request->appUser->role, [self::ROLE_ADMINISTRATOR, self::ROLE_INSTRUCTOR, self::ROLE_USER])) {
                    return false;
                }
                if ($request->appUser->role == self::ROLE_USER && $request->appUser->id != $id) {
                    return false;
                }
                break;
            default:
                return false;
        }

        return true;
    }

    /**
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function listingGroup($id)
    {
        return $this->listingRelation($id, 'group');
    }

    /**
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function currentGroup(Request $request, $id)
    {
        $data = $this->validate($request, [
            'group' => 'present|array|max:100'
        ]);

        try {
            $this->validateIds($data['group'], 'group');

            // obtenemos el estado actual de la tabla
            $currentState = $this->getDb()->select("SELECT `id`, `group` "
                    . "FROM `user_group` "
                    . "WHERE `user` = ?", [$id]);
            $currentIds = [];

            // realizamos en BD las operaciones de eliminación e insercción necesarias
            foreach ($currentState as $current) {
                $currentIds[] = $current->group;
                if (!in_array($current->group, $data['group'])) {
                    $this->getDb()->delete("DELETE "
                            . "FROM `user_group` "
                            . "WHERE `id` = ?", [$current->id]);
                }
            }

            foreach ($data['group'] as $value) {
                if (!in_array($value, $currentIds)) {
                    $this->getDb()->insert("INSERT "
                            . "INTO `user_group`(`user`, `group`) "
                            . "VALUES (?, ?)", [$id, $value]);
                }
            }

            // devolvemos el estado actual
            $result = $this->listingRelation($id, 'group', true);
            $code = 200;

        } catch (\Exception $ex) {
            $result = ['error' => [$ex->getMessage()]];
            $code = $code ?? 500;
        }

        return response()->json($result, $code);
    }

    /**
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function questionnairesMade(Request $request, $id)
    {
        if (!$this->checkAcl($request, $id)) {
            return $this->unauthorized();
        }

        $query = "SELECT ".
                "r.`questionary` AS `questionary_id`,".
                "q.`group` AS `group_id`,".
                "q.`title` AS `questionary_title`,".
                "g.`name` AS `group_name` ".
            "FROM ".
                "`registry` AS r ".
                "INNER JOIN `questionary` AS q ON r.`questionary` = q.`id` ".
                "INNER JOIN `group` AS g ON q.`group` = g.`id` ".
            "WHERE ".
                "r.`user` = ? ".
            "GROUP BY ".
                "r.`questionary` ".
            "ORDER BY q.`group`, r.`questionary`";
        $bindings = [$id];
        $results = [];

        $questionarys = $this->getDb()->select($query, $bindings);

        foreach ($questionarys as $questionary) {
            $results[] = [
                'id' => $questionary->questionary_id,
                'title' => $questionary->questionary_title,
                'group' => [
                    'id' => $questionary->group_id,
                    'name' => $questionary->group_name,
                ],
            ];
        }
        
        return response()->json($results, 200);
    }

    /**
     * 
     * @param Request $request
     * @param int $idUser
     * @param int $idQuestionary
     * @return JsonResponse
     */
    public function questionaryDetails(Request $request, $idUser, $idQuestionary)
    {
        if (!$this->checkAcl($request, $idUser)) {
            return $this->unauthorized();
        }

        $result = [];

        //datos user
        $queryUser = 'SELECT `id`, `name`, `surname_1`, `surname_2` '.
                'FROM `user` '.
                'WHERE `id` = ?';
        $user = $this->getDb()->selectOne($queryUser, [$idUser]);

        if (!$user) {
            return response()->json(['error' => ['El recurso solicitado no existe']], 404);
        }
        $result['user'] = $user;

        //datos questionary
        $queryQuestionary = 'SELECT '.
                'q.`id` AS `questionary_id`,'.
                'q.`title` AS `questionary_title`,'.
                'q.`description` AS `questionary_description`,'.
                'g.`id` AS `group_id`,'.
                'g.`name` AS `group_name`,'.
                'qm.`id` AS `questionary_model_id`,'.
                'qm.`name` AS `questionary_model_name` '.
            'FROM '.
                '`questionary` AS q '.
                'INNER JOIN `questionary_model` AS qm ON q.`model` = qm.`id` '.
                'INNER JOIN `group` AS g ON q.`group` = g.`id` '.
            'WHERE q.`id` = ?';
        $questionary = $this->getDb()->selectOne($queryQuestionary, [$idQuestionary]);

        if (!$questionary) {
            return response()->json(['error' => ['El recurso solicitado no existe']], 404);
        }
        $result['questionary'] = [
            'id' => $questionary->questionary_id,
            'title' => $questionary->questionary_title,
            'description' => $questionary->questionary_description,
            'group' => [
                'id' => $questionary->group_id,
                'name' => $questionary->group_name,
            ],
            'model' => [
                'id' => $questionary->questionary_model_id,
                'name' => $questionary->questionary_model_name,
            ],
        ];

        //user made questionary?
        $queryMade = 'SELECT MAX(`created_at`) AS `last_date` FROM `registry` WHERE `user` = ? AND `questionary` = ?';
        $made = $this->getDb()->selectOne($queryMade, [$idUser, $idQuestionary]);
        $result['last_date'] = $made->last_date;
        if (!$result['last_date']) {
            return response()->json($result, 200);
        }

        //questions
        $result['questions'] = [];
        $queryQuestions = 'SELECT '.
                'q.`id` AS `question_id`,'.
                'q.`statement` AS `question_statement`,'.
                'qm.`id` AS `question_model_id`,'.
                'qm.`name` AS `question_model_name`,'.
                'q.`active` AS `question_active` '.
            'FROM '.
                '`question` AS q '.
                'INNER JOIN `question_model` AS qm ON q.`model` = qm.`id` '.
            'WHERE '.
                'q.`questionary` = ? '.
            'ORDER BY '.
                'q.`sort`';
        $questions = $this->getDb()->select($queryQuestions, [$idQuestionary]);
        $indexQuestions = [];

        foreach ($questions as $index => $question) {
            $indexQuestions[$question->question_id] = $index;
            $result['questions'][] = [
                'id' => $question->question_id,
                'statement' => $question->question_statement,
                'model' => [
                    'id' => $question->question_model_id,
                    'name' => $question->question_model_name,
                ],
                'active' => $question->question_active,
                'answers' => [],
                'registry' => null,
            ];
        }

        //answers
        $queryAnswers = 'SELECT `id`,`question`,`statement`,`correct` '.
                'FROM `answer` '.
                'WHERE `question` IN (SELECT `id` FROM `question` WHERE `questionary` = ?) '.
                'ORDER BY `question`, `id`';
        $answers = $this->getDb()->select($queryAnswers, [$idQuestionary]);

        foreach ($answers as $answer) {
            $index = $indexQuestions[$answer->question];
            $result['questions'][$index]['answers'][] = [
                'id' => $answer->id,
                'statement' => $answer->statement,
                'correct' => $answer->correct,
            ];
        }

        //registries
        $queryRegistries = 'SELECT `id`,`question`,`answer`,`created_at` '.
                'FROM `registry` '.
                'WHERE `user` = ? AND `questionary` = ?';
        $registries = $this->getDb()->select($queryRegistries, [$idUser, $idQuestionary]);

        foreach ($registries as $registry) {
            $index = $indexQuestions[$registry->question];
            $result['questions'][$index]['registry'] = [
                'id' => $registry->id,
                'answer' => $registry->answer,
                'created_at' => $registry->created_at,
            ];
        }

        return response()->json($result, 200);
    }
}
