<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RegistryController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->table = 'registry';
        $this->publicColumns = [
            'id', 'user', 'questionary', 'question', 'answer', 'created_at'
        ];
        $this->rulesForCreate = [
            'user' => 'nullable|exists:user,id',
            'questionary' => 'required|exists:questionary,id',
            'question' => 'required|exists:question,id',
            'answer' => 'required|exists:answer,id',
        ];
        $this->rulesForUpdate = [];
    }

    public function delete($id)
    {
        return $this->notAllowed();
    }

    public function update(Request $request, $id)
    {
        return $this->notAllowed();
    }
}
