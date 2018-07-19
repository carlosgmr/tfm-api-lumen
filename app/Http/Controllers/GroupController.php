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
    }
}
