<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class QuestionModelController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->table = 'question_model';
        $this->publicColumns = [
            'id', 'name', 'description', 'created_at', 'updated_at', 'active',
        ];
        $this->rulesForCreate = [
            'name' => 'required|min:2|max:32',
            'description' => 'nullable|min:2|max:256',
            'active' => 'required|boolean',
        ];
        $this->rulesForUpdate = [
            'name' => 'nullable|min:2|max:32',
            'description' => 'nullable|min:2|max:256',
            'active' => 'nullable|boolean',
        ];
    }

    public function create(Request $request)
    {
        return $this->notAllowed();
    }

    public function delete($id)
    {
        return $this->notAllowed();
    }
}
