<?php

namespace App\Http\Controllers;

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
}
