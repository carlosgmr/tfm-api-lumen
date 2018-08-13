<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class QuestionaryController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->table = 'questionary';
        $this->publicColumns = [
            'id', 'group', 'title', 'description', 'model', 'created_at', 'updated_at', 'public', 'active'
        ];
        $this->rulesForListing = [
            'group' => 'nullable|exists:group,id',
            'model' => 'nullable|exists:questionary_model,id',
            'public' => 'nullable|boolean',
            'active' => 'nullable|boolean',
        ];
        $this->rulesForCreate = [
            'group' => 'required|exists:group,id',
            'title' => 'required|min:2|max:256',
            'description' => 'nullable|max:65535',
            'model' => 'required|exists:questionary_model,id',
            'public' => 'required|boolean',
            'active' => 'required|boolean',
        ];
        $this->rulesForUpdate = [
            'title' => 'nullable|min:2|max:256',
            'description' => 'nullable|max:65535',
            'public' => 'nullable|boolean',
            'active' => 'nullable|boolean',
        ];
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
            case 'questionary.listing':
            case 'questionary.read':
                if (!in_array($request->appUser->role, [self::ROLE_ADMINISTRATOR, self::ROLE_INSTRUCTOR, self::ROLE_USER])) {
                    return false;
                }
                break;
            case 'questionary.create':
            case 'questionary.update':
            case 'questionary.delete':
            case 'questionary.readComplete':
                if (!in_array($request->appUser->role, [self::ROLE_INSTRUCTOR])) {
                    return false;
                }
                break;

            default:
                return false;
        }

        return true;
    }

    public function readComplete(Request $request, $id)
    {
        if (!$this->checkAcl($request, $id)) {
            return $this->unauthorized();
        }

        $result = [];

        //questionary
        $query = 'SELECT '.
                'q.`id` AS `questionary_id`,'.
                'q.`title` AS `questionary_title`,'.
                'q.`description` AS `questionary_description`,'.
                'q.`created_at` AS `questionary_created_at`,'.
                'q.`updated_at` AS `questionary_updated_at`,'.
                'q.`public` AS `questionary_public`,'.
                'q.`active` AS `questionary_active`,'.
                'g.`id` AS `group_id`,'.
                'g.`name` AS `group_name`,'.
                'qm.`id` AS `questionary_model_id`,'.
                'qm.`name` AS `questionary_model_name` '.
            'FROM '.
                '`questionary` AS q '.
                'INNER JOIN `group` AS g ON q.`group` = g.`id` '.
                'INNER JOIN `questionary_model` AS qm ON q.`model` = qm.`id` '.
            'WHERE '.
                'q.`id` = ?';
        $questionary = $this->getDb()->selectOne($query, [$id]);

        if (empty($questionary)) {
            return response()->json(['error' => ['El recurso solicitado no existe']], 404);
        }

        $result['id'] = $questionary->questionary_id;
        $result['title'] = $questionary->questionary_title;
        $result['description'] = $questionary->questionary_description;
        $result['created_at'] = $questionary->questionary_created_at;
        $result['updated_at'] = $questionary->questionary_updated_at;
        $result['public'] = $questionary->questionary_public;
        $result['active'] = $questionary->questionary_active;
        $result['group'] = [
            'id' => $questionary->group_id,
            'name' => $questionary->group_name,
        ];
        $result['model'] = [
            'id' => $questionary->questionary_model_id,
            'name' => $questionary->questionary_model_name,
        ];

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
        $questions = $this->getDb()->select($queryQuestions, [$id]);
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
            ];
        }

        //answers
        $queryAnswers = 'SELECT `id`,`question`,`statement`,`correct` '.
                'FROM `answer` '.
                'WHERE `question` IN (SELECT `id` FROM `question` WHERE `questionary` = ?) '.
                'ORDER BY `question`, `id`';
        $answers = $this->getDb()->select($queryAnswers, [$id]);

        foreach ($answers as $answer) {
            $index = $indexQuestions[$answer->question];
            $result['questions'][$index]['answers'][] = [
                'id' => $answer->id,
                'statement' => $answer->statement,
                'correct' => $answer->correct,
            ];
        }

        //usuarios que han realizado el examen
        $queryUsers = 'SELECT u.`id`,u.`name`,u.`surname_1`,u.`surname_2` '.
                'FROM `registry` AS r INNER JOIN `user` AS u ON r.`user` = u.`id` '.
                'WHERE r.`questionary` = ? '.
                'GROUP BY u.`id` '.
                'ORDER BY u.`surname_1`,u.`surname_2`,u.`name`';
        $result['users'] = $this->getDb()->select($queryUsers, [$id]);

        return response()->json($result, 200);
    }
}
