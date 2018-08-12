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
        $this->rulesForListing = [
            'user' => 'nullable|exists:user,id',
            'questionary' => 'nullable|exists:questionary,id',
            'question' => 'nullable|exists:question,id',
            'answer' => 'nullable|exists:answer,id',
        ];
        $this->rulesForCreate = [
            'user' => 'nullable|exists:user,id',
            'questionary' => 'required|exists:questionary,id',
            'question' => 'required|exists:question,id',
            'answer' => 'required|exists:answer,id',
        ];
        $this->rulesForUpdate = [];
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
            case 'registry.listing':
            case 'registry.read':
                if (!in_array($request->appUser->role, [self::ROLE_ADMINISTRATOR, self::ROLE_INSTRUCTOR])) {
                    return false;
                }
                break;
            case 'registry.create':
                if (!in_array($request->appUser->role, [self::ROLE_USER])) {
                    return false;
                }
                break;
            case 'registry.update':
            case 'registry.delete':
                break;

            default:
                return false;
        }

        return true;
    }

    public function delete(Request $request, $id)
    {
        return $this->notAllowed();
    }

    public function update(Request $request, $id)
    {
        return $this->notAllowed();
    }
}
