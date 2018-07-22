<?php

namespace App\Http\Controllers;

class AnswerController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->table = 'answer';
        $this->publicColumns = [
            'id', 'question', 'statement', 'correct'
        ];
        $this->rulesForListing = [
            'question' => 'nullable|exists:question,id',
        ];
        $this->rulesForCreate = [
            'question' => 'required|exists:question,id',
            'statement' => 'required|min:2|max:65535',
            'correct' => 'nullable|boolean',
        ];
        $this->rulesForUpdate = [
            'statement' => 'nullable|min:2|max:65535',
            'correct' => 'nullable|boolean',
        ];
    }
}
