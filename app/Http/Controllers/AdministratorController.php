<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdministratorController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->table = 'administrator';
        $this->publicColumns = [
            'id', 'email', 'name', 'surname_1', 'surname_2', 'created_at', 'updated_at', 'active',
        ];
        $this->rulesForCreate = [
            'email' => 'required|email|max:256|unique:'.$this->table.',email',
            'password' => 'required|min:4|max:32',
            'name' => 'required|min:2|max:64',
            'surname_1' => 'required|min:2|max:64',
            'surname_2' => 'nullable|min:2|max:64',
            'active' => 'required|boolean',
        ];
        $this->rulesForUpdate = [
            'email' => 'nullable|email|max:256|unique:'.$this->table.',email,###ID###,id',
            'password' => 'nullable|min:4|max:32',
            'name' => 'nullable|min:2|max:64',
            'surname_1' => 'nullable|min:2|max:64',
            'surname_2' => 'nullable|min:2|max:64',
            'active' => 'nullable|boolean',
        ];
    }

    /**
     * Aplica un formato personalizado a los datos pasados
     * @param array $data
     * @return array
     */
    public function formatData($data)
    {
        if (isset($data['password']) && $data['password'] !== null && $data['password'] !== '') {
            switch (env('PASSWORD_ALGO')) {
                case 'bcrypt':
                    /* @var $hashManager \Illuminate\Hashing\HashManager */
                    $hashManager = app('hash');
                    $data['password'] = $hashManager->make($data['password'], ['rounds' => 10]);
                    break;
                case 'sha1':
                    $data['password'] = sha1($data['password']);
                    break;
            }
        }

        return $data;
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
            case 'administrator.listing':
            case 'administrator.read':
            case 'administrator.create':
            case 'administrator.update':
            case 'administrator.delete':
                if (!in_array($request->appUser->role, [self::ROLE_ADMINISTRATOR])) {
                    return false;
                }
                break;

            default:
                return false;
        }

        return true;
    }
}
