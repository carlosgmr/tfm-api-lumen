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

    /**
     * 
     * @param Request $request
     * @param int $id
     * @return bool
     */
    public function checkAcl(Request $request, $id = null)
    {
        switch ($this->getRouteName($request)) {
            case 'questionModel.listing':
            case 'questionModel.read':
                if (!in_array($request->appUser->role, [self::ROLE_ADMINISTRATOR, self::ROLE_INSTRUCTOR, self::ROLE_USER])) {
                    return false;
                }
                break;
            case 'questionModel.create':
            case 'questionModel.update':
            case 'questionModel.delete':
                if (!in_array($request->appUser->role, [self::ROLE_ADMINISTRATOR])) {
                    return false;
                }
                break;

            default:
                return false;
        }

        return true;
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
