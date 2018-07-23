<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->path() === 'auth/login') {
            return $next($request);
        }

        if (!$request->hasHeader('Authorization')) {
            return response()->json(['error' => ['El header Authorization es obligatorio']], 400);
        }

        try {
            $token = $request->header('Authorization');
            $credentials = JWT::decode($token, env('JWT_SECRET'), ['HS256']);
        } catch(ExpiredException $e) {
            return response()->json(['error' => ['El token enviado ha expirado']], 400);
        } catch(Exception $e) {
            return response()->json(['error' => ['Se ha producido un error decodificando el token: '.$e->getMessage()]], 400);
        }

        $request->appUser = $credentials->data;
        return $next($request);
    }
}
