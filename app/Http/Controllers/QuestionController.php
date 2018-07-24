<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class QuestionController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->table = 'question';
        $this->publicColumns = [
            'id', 'questionary', 'statement', 'sort', 'model', 'created_at', 'updated_at', 'active'
        ];
        $this->rulesForListing = [
            'questionary' => 'nullable|exists:questionary,id',
            'model' => 'nullable|exists:question_model,id',
            'active' => 'nullable|boolean',
        ];
        $this->rulesForCreate = [
            'questionary' => 'required|exists:questionary,id',
            'statement' => 'required|min:2|max:65535',
            'sort' => 'required|integer|min:1|max:9999999999',
            'model' => 'required|exists:question_model,id',
            'active' => 'required|boolean',
        ];
        $this->rulesForUpdate = [
            'statement' => 'nullable|min:2|max:65535',
            'sort' => 'nullable|integer|min:1|max:9999999999',
            'active' => 'nullable|boolean',
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
            case 'question.listing':
            case 'question.read':
                if (!in_array($request->appUser->role, [self::ROLE_ADMINISTRATOR, self::ROLE_INSTRUCTOR, self::ROLE_USER])) {
                    return false;
                }
                break;
            case 'question.create':
            case 'question.update':
            case 'question.delete':
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
