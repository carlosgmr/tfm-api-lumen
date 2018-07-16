<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdministratorController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Devuelve todos los elementos disponibles en la entidad
     * 
     * @return JsonResponse
     */
    public function listing()
    {
        $results = DB::select("SELECT id, email, name, surname_1, surname_2, created_at, updated_at, active FROM administrator");
        return response()->json($results, 200);
    }

    /**
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function read($id)
    {
        return response()->json([], 200);
    }

    /**
     * 
     * @return JsonResponse
     */
    public function create()
    {
        return response()->json([], 201);
    }

    /**
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function update($id)
    {
        return response()->json([], 200);
    }

    /**
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function delete($id)
    {
        return response()->json([], 204);
    }
}
