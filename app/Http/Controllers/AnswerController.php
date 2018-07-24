<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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

    /**
     * 
     * @param Request $request
     * @param int $id
     * @return bool
     */
    public function checkAcl(Request $request, $id = null)
    {
        switch ($this->getRouteName($request)) {
            case 'answer.listing':
            case 'answer.read':
                if (!in_array($request->appUser->role, [self::ROLE_ADMINISTRATOR, self::ROLE_INSTRUCTOR, self::ROLE_USER])) {
                    return false;
                }
                break;
            case 'answer.create':
            case 'answer.update':
            case 'answer.delete':
                if (!in_array($request->appUser->role, [self::ROLE_INSTRUCTOR])) {
                    return false;
                }
                break;

            default:
                return false;
        }

        return true;
    }
}
