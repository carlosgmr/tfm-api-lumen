<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

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
}
