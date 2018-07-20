<?php

namespace App\Http\Controllers;

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
     * @param int $id
     * @return JsonResponse
     */
    public function listingUser($id)
    {
        return $this->listingRelation($id, 'user');
    }
}
