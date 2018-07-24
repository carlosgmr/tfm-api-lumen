<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
        $this->rulesForListing = [
            'group' => 'nullable|exists:group,id',
            'model' => 'nullable|exists:questionary_model,id',
            'public' => 'nullable|boolean',
            'active' => 'nullable|boolean',
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

    /**
     * 
     * @param Request $request
     * @param int $id
     * @return bool
     */
    public function checkAcl(Request $request, $id = null)
    {
        switch ($this->getRouteName($request)) {
            case 'questionary.listing':
            case 'questionary.read':
                if (!in_array($request->appUser->role, [self::ROLE_ADMINISTRATOR, self::ROLE_INSTRUCTOR, self::ROLE_USER])) {
                    return false;
                }
                break;
            case 'questionary.create':
            case 'questionary.update':
            case 'questionary.delete':
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
