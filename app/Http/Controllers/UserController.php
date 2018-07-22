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
}
