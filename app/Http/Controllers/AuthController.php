<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;

class AuthController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {}

    /**
     * Autentifica al usuario y devuelve un token para poder acceder a los demás endpoints
     * @param Request $request
     * @return JsonResponse
     */
    public function authenticate(Request $request)
    {
        $data = $this->validate($request, [
            'email' => 'required|string|max:256|email',
            'password' => 'required|string|min:4|max:32',
            'role' => 'required|string|in:administrator,instructor,user',
        ]);

        $appUser = $this->getDb()->selectOne("SELECT "
                . "`id`, `email`, `password`, `name`, `surname_1`, `surname_2`, `active` "
                . "FROM `".$data['role']."` "
                . "WHERE `email` = ?", [$data['email']]);

        if (!$appUser) {
            return response()->json(['error' => ['El email indicado no está registrado']], 400);
        }

        if (!$appUser->active) {
            return response()->json(['error' => ['Tu cuenta se encuentra deshabilitada']], 400);
        }

        /* @var $hashManager \Illuminate\Hashing\HashManager */
        $hashManager = app('hash');
        if (!$hashManager->check($data['password'], $appUser->password)) {
            return response()->json(['error' => ['Las credenciales no son válidas']], 400);
        }

        // generamos token
        $payload = [
            'iss' => env('JWT_ISSUER'),
            'iat' => time(),
            'data' => [
                'role' => $data['role'],
                'id' => $appUser->id,
                'email' => $appUser->email,
                'fullname' => trim($appUser->name.' '.$appUser->surname_1.' '.$appUser->surname_2),
            ],
        ];
        $token = JWT::encode($payload, env('JWT_SECRET'));
        return response()->json(['token' => $token], 200);
    }
}
