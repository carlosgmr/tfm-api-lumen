<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstructorController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->table = 'instructor';
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
                    'table' => 'instructor_group',
                    'publicColumns' => ['added_at'],
                    'fkColumn' => 'group',
                    'whereColumn' => 'instructor',
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
            switch (env('PASSWORD_ALGO')) {
                case 'bcrypt':
                    /* @var $hashManager \Illuminate\Hashing\HashManager */
                    $hashManager = app('hash');
                    $data['password'] = $hashManager->make($data['password'], ['rounds' => 10]);
                    break;
                case 'sha1':
                    $data['password'] = sha1($data['password']);
                    break;
            }
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
            case 'instructor.read':
            case 'instructor.listing':
            case 'instructor.listing.group':
                if (!in_array($request->appUser->role, [self::ROLE_ADMINISTRATOR, self::ROLE_INSTRUCTOR, self::ROLE_USER])) {
                    return false;
                }
                break;
            case 'instructor.update':
            case 'instructor.listing.questionary':
                if (!in_array($request->appUser->role, [self::ROLE_ADMINISTRATOR, self::ROLE_INSTRUCTOR])) {
                    return false;
                }
                if ($request->appUser->role == self::ROLE_INSTRUCTOR && $request->appUser->id != $id) {
                    return false;
                }
                break;
            case 'instructor.create':
            case 'instructor.delete':
            case 'instructor.current.group':
                if (!in_array($request->appUser->role, [self::ROLE_ADMINISTRATOR])) {
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
                    . "FROM `instructor_group` "
                    . "WHERE `instructor` = ?", [$id]);
            $currentIds = [];

            // realizamos en BD las operaciones de eliminación e insercción necesarias
            foreach ($currentState as $current) {
                $currentIds[] = $current->group;
                if (!in_array($current->group, $data['group'])) {
                    $this->getDb()->delete("DELETE "
                            . "FROM `instructor_group` "
                            . "WHERE `id` = ?", [$current->id]);
                }
            }

            foreach ($data['group'] as $value) {
                if (!in_array($value, $currentIds)) {
                    $this->getDb()->insert("INSERT "
                            . "INTO `instructor_group`(`instructor`, `group`) "
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
    public function listingQuestionary(Request $request, $id)
    {
        if (!$this->checkAcl($request, $id)) {
            return $this->unauthorized();
        }

        $query = 'SELECT '.
                'q.`id` AS `questionary_id`,'.
                'q.`title` AS `questionary_title`,'.
                'q.`description` AS `questionary_description`,'.
                'q.`created_at` AS `questionary_created_at`,'.
                'q.`updated_at` AS `questionary_updated_at`,'.
                'q.`public` AS `questionary_public`,'.
                'q.`active` AS `questionary_active`,'.
                'qm.`id` AS `questionary_model_id`,'.
                'qm.`name` AS `questionary_model_name`,'.
                'g.`id` AS `group_id`,'.
                'g.`name` AS `group_name` '.
            'FROM '.
                '`questionary` AS q '.
                'INNER JOIN `group` AS g ON q.`group` = g.`id` '.
                'INNER JOIN `questionary_model` AS qm ON q.`model` = qm.`id` '.
            'WHERE '.
                'q.`group` IN ('.
                    'SELECT g.`id` '.
                    'FROM `instructor_group` AS ig INNER JOIN `group` AS g ON ig.`group` = g.`id` '.
                    'WHERE ig.`instructor` = ? '.
                ') '.
            'ORDER BY '.
                'q.`group`,'.
                'q.`id`';

        $bindings = [$id];
        $results = [];
        $items = $this->getDb()->select($query, $bindings);

        foreach ($items as $item) {
            $results[] = [
                'id' => $item->questionary_id,
                'title' => $item->questionary_title,
                'description' => $item->questionary_description,
                'created_at' => $item->questionary_created_at,
                'updated_at' => $item->questionary_updated_at,
                'public' => $item->questionary_public,
                'active' => $item->questionary_active,
                'model' => [
                    'id' => $item->questionary_model_id,
                    'name' => $item->questionary_model_name,
                ],
                'group' => [
                    'id' => $item->group_id,
                    'name' => $item->group_name,
                ],
            ];
        }

        return response()->json($results, 200);
    }
}
