<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->table = 'group';
        $this->publicColumns = [
            'id', 'name', 'description', 'created_at', 'updated_at', 'active',
        ];
        $this->rulesForCreate = [
            'name' => 'required|min:2|max:256',
            'description' => 'nullable|min:2|max:256',
            'active' => 'required|boolean',
        ];
        $this->rulesForUpdate = [
            'name' => 'nullable|min:2|max:256',
            'description' => 'nullable|min:2|max:256',
            'active' => 'nullable|boolean',
        ];

        $this->relations = [
            'instructor' => [
                'join' => [
                    'table' => 'instructor_group',
                    'publicColumns' => ['added_at'],
                    'fkColumn' => 'instructor',
                    'whereColumn' => 'group',
                ],
                'publicColumns' => ['id', 'email', 'name', 'surname_1', 'surname_2', 'created_at', 'updated_at', 'active'],
            ],
            'user' => [
                'join' => [
                    'table' => 'user_group',
                    'publicColumns' => ['added_at'],
                    'fkColumn' => 'user',
                    'whereColumn' => 'group',
                ],
                'publicColumns' => ['id', 'email', 'name', 'surname_1', 'surname_2', 'created_at', 'updated_at', 'active'],
            ]
        ];
    }

    /**
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function listingInstructor($id)
    {
        return $this->listingRelation($id, 'instructor');
    }

    /**
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function currentInstructor(Request $request, $id)
    {
        $data = $this->validate($request, [
            'instructor' => 'present|array|max:100'
        ]);

        try {
            $this->validateIds($data['instructor'], 'instructor');

            // obtenemos el estado actual de la tabla
            $currentState = $this->getDb()->select("SELECT `id`, `instructor` "
                    . "FROM `instructor_group` "
                    . "WHERE `group` = ?", [$id]);
            $currentIds = [];

            // realizamos en BD las operaciones de eliminación e insercción necesarias
            foreach ($currentState as $current) {
                $currentIds[] = $current->instructor;
                if (!in_array($current->instructor, $data['instructor'])) {
                    $this->getDb()->delete("DELETE "
                            . "FROM `instructor_group` "
                            . "WHERE `id` = ?", [$current->id]);
                }
            }

            foreach ($data['instructor'] as $value) {
                if (!in_array($value, $currentIds)) {
                    $this->getDb()->insert("INSERT "
                            . "INTO `instructor_group`(`group`, `instructor`) "
                            . "VALUES (?, ?)", [$id, $value]);
                }
            }

            // devolvemos el estado actual
            $result = $this->listingRelation($id, 'instructor', true);
            $code = 200;

        } catch (\Exception $ex) {
            $result = ['error' => [$ex->getMessage()]];
            $code = $code ?? 500;
        }

        return response()->json($result, $code);
    }

    /**
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function listingUser($id)
    {
        return $this->listingRelation($id, 'user');
    }

    /**
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function currentUser(Request $request, $id)
    {
        $data = $this->validate($request, [
            'user' => 'present|array|max:100'
        ]);

        try {
            $this->validateIds($data['user'], 'user');

            // obtenemos el estado actual de la tabla
            $currentState = $this->getDb()->select("SELECT `id`, `user` "
                    . "FROM `user_group` "
                    . "WHERE `group` = ?", [$id]);
            $currentIds = [];

            // realizamos en BD las operaciones de eliminación e insercción necesarias
            foreach ($currentState as $current) {
                $currentIds[] = $current->user;
                if (!in_array($current->user, $data['user'])) {
                    $this->getDb()->delete("DELETE "
                            . "FROM `user_group` "
                            . "WHERE `id` = ?", [$current->id]);
                }
            }

            foreach ($data['user'] as $value) {
                if (!in_array($value, $currentIds)) {
                    $this->getDb()->insert("INSERT "
                            . "INTO `user_group`(`group`, `user`) "
                            . "VALUES (?, ?)", [$id, $value]);
                }
            }

            // devolvemos el estado actual
            $result = $this->listingRelation($id, 'user', true);
            $code = 200;

        } catch (\Exception $ex) {
            $result = ['error' => [$ex->getMessage()]];
            $code = $code ?? 500;
        }

        return response()->json($result, $code);
    }
}
