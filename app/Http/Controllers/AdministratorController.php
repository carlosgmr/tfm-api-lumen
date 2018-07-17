<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
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
        $this->editabledColumns = [
            'email', 'name', 'password', 'surname_1', 'surname_2', 'active',
        ];
    }

    /**
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request)
    {
        $post = $request->post();
        $data = [];
        foreach ($this->editabledColumns as $column) {
            $data[$column] = $post[$column] ?? null;
        }

        if ($data['password'] !== null && $data['password'] !== '') {
            /* @var $hashManager \Illuminate\Hashing\HashManager */
            $hashManager = app('hash');
            $data['password'] = $hashManager->make(null, ['rounds' => 10]);
        }

        try {
            if (!$this->isValidUnique('email', $data['email'])) {
                $code = 400;
                throw new \Exception('El email indicado ya está registrado');
            }

            if (!$this->createInDb($data)) {
                throw new \Exception('Los datos no han podido ser creados');
            }

            $id = $this->getDb()->getPdo()->lastInsertId();
            $result = $this->getPublicData($id);
            $code = 201;
        } catch (\Exception $ex) {
            $result = ['error' => $ex->getMessage()];
            $code = $code ?? 500;
        }

        return response()->json($result, $code);
    }

    /**
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        $post = $request->post();
        $data = [];
        foreach ($this->editabledColumns as $column) {
            if (isset($post[$column])) {
                $data[$column] = $post[$column];
            }
        }

        if (isset($data['password']) && $data['password'] !== null && $data['password'] !== '') {
            /* @var $hashManager \Illuminate\Hashing\HashManager */
            $hashManager = app('hash');
            $data['password'] = $hashManager->make(null, ['rounds' => 10]);
        }

        try {
            if (isset($data['email']) && !$this->isValidUnique('email', $data['email'], $id)) {
                $code = 400;
                throw new \Exception('El email indicado ya está registrado');
            }

            if (!$this->updateInDb($id, $data)) {
                throw new \Exception('Los datos no han podido ser actualizados');
            }

            $result = $this->getPublicData($id);
            $code = 200;
        } catch (\Exception $ex) {
            $result = ['error' => $ex->getMessage()];
            $code = $code ?? 500;
        }

        return response()->json($result, $code);
    }
}
