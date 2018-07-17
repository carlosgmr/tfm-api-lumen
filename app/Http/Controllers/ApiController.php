<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Clase base para acciones de la API
 *
 * @author Carlos Molina
 */
class ApiController extends Controller
{
    /**
     * Conexión con la base de datos
     * @var \Illuminate\Database\Connection
     */
    private $db;

    /**
     * Nombre de la tabla de base de datos con la que trabaja la clase Controller
     * 
     * @var string
     */
    protected $table;

    /**
     * Nombre de las columnas expuestas por la API en las operaciones listing y read
     * 
     * @var array
     */
    protected $publicColumns;

    /**
     * Nombre de las columnas que se pueden editar en las operaciones insert y update
     * 
     * @var array
     */
    protected $editabledColumns;

    /**
     * Devuelve la instancia de la conexión con la base de datos
     * @return \Illuminate\Database\Connection
     */
    protected function getDb()
    {
        if (isset($this->db)) {
            return $this->db;
        }

        /* @var $databaseManager \Illuminate\Database\DatabaseManager */
        $databaseManager = app('db');
        $this->db = $databaseManager->connection();
        return $this->db;
    }

    /**
     * Devuelve todos los elementos disponibles en la entidad
     * 
     * @return JsonResponse
     */
    public function listing()
    {
        $results = $this->getDb()->select("SELECT ".implode(",", $this->publicColumns).
                " FROM ".$this->table);
        return response()->json($results, 200);
    }

    /**
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function read($id)
    {
        $result = $this->getPublicData($id);

        if (empty($result)) {
            return response()->json(['error' => 'El recurso solicitado no existe'], 404);
        }

        return response()->json($result, 200);
    }

    /**
     * Retorna los datos públicos de un recurso
     * 
     * @param int $id
     * @return array
     */
    protected function getPublicData($id)
    {
        return $this->getDb()->select("SELECT ".implode(",", $this->publicColumns).
                " FROM ".$this->table.
                " WHERE id = :id",
                [
                    'id' => $id,
                ]);
    }

    /**
     * Comprueba si el valor pasado existe en la columna de la tabla actual
     * @param string $column
     * @param mixed $value
     * @param int $id
     * @return bool
     */
    protected function isValidUnique($column, $value, $id = null)
    {
        $query = "SELECT id".
                " FROM ".$this->table.
                " WHERE ".$column." = ?";
        $bindings = [$value];

        if ($id) {
            $query .= " AND id <> ?";
            $bindings[] = $id;
        }

        $result = $this->getDb()->selectOne($query, $bindings);
        return $result === null;
    }

    /**
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request)
    {
        return response()->json(['error' => 'Método no soportado'], 405);
    }

    /**
     * Inserta una fila en la tabla actual
     * @param array $data
     * @return bool
     */
    protected function createInDb($data)
    {
        $columns = array_keys($data);
        $params = array_fill(0, count($data), '?');
        $values = array_values($data);

        return $this->getDb()->insert("INSERT INTO ".$this->table." (".implode(",", $columns).")".
                " VALUES (".implode(",", $params).")", $values);
    }

    /**
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        return response()->json(['error' => 'Método no soportado'], 405);
    }

    /**
     * Actualiza una fila en la tabla actual
     * 
     * @param int $id
     * @param array $data
     * @return int
     */
    protected function updateInDb($id, $data)
    {
        $columns = array_keys($data);
        array_walk($columns, function(&$value, $key) {$value .= ' = ?';});
        $values = array_values($data);
        $values[] = $id;

        return $this->getDb()->update("UPDATE ".$this->table." SET ".implode(",", $columns)." WHERE id = ?", $values);
    }

    /**
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function delete($id)
    {
        try {
            $result = $this->getPublicData($id);

            if (empty($result)) {
                throw  new \Exception('El recurso solicitado no existe', 404);
            }

            if (!$this->getDb()->delete("DELETE FROM ".$this->table." WHERE id = ?", [$id])) {
                throw new \Exception('Los datos no han podido ser eliminados');
            }

            $code = 200;
        } catch (\Exception $ex) {
            $result = ['error' => $ex->getMessage()];
            $code = $code ?? 500;
        }

        return response()->json($result, $code);
    }
}
