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
    const ROLE_ADMINISTRATOR = 'administrator';
    const ROLE_INSTRUCTOR = 'instructor';
    const ROLE_USER = 'user';

    /**
     * Conexión con la base de datos
     * @var \Illuminate\Database\Connection
     */
    private $db;

    /**
     * Nombre de la tabla de base de datos con la que trabaja la clase Controller
     * @var string
     */
    protected $table;

    /**
     * Nombre de las columnas expuestas por la API en las operaciones listing y read
     * @var array
     */
    protected $publicColumns;

    /**
     * Reglas de validación de datos de entrada para la operación listing
     * @var array
     */
    protected $rulesForListing;

    /**
     * Reglas de validación de datos de entrada para la operación create
     * @var array
     */
    protected $rulesForCreate;

    /**
     * Reglas de validación de datos de entrada para la operación update
     * @var array
     */
    protected $rulesForUpdate;

    /**
     * Configuración de las relaciones con otros tablas
     * @var array
     */
    protected $relations;

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
     * Aplica un formato personalizado a los datos pasados
     * Si es necesario debe ser sobreescrito en las clases hijas
     * @param array $data
     * @return array
     */
    public function formatData($data)
    {
        return $data;
    }

    /**
     * Comprueba si el request actual tiene permisos para acceder al recurso
     * de acuerdo a las credenciales asociades al token
     * Se debe implementar en cada clase Controller
     * @param Request $request
     * @param int $id
     * @return bool
     */
    public function checkAcl(Request $request, $id = null)
    {
        return true;
    }

    /**
     * Retorna el alias de la ruta actual
     * @todo Corregir la forma de obtener la ruta
     * @param Request $request
     * @return string
     */
    public function getRouteName(Request $request)
    {
        $route = $request->route();
        return $route[1]['as'];
    }

    /**
     * Devuelve todos los elementos disponibles en la entidad
     * @return JsonResponse
     */
    public function listing(Request $request)
    {
        if (!$this->checkAcl($request)) {
            return $this->unauthorized();
        }

        if (!empty($this->rulesForListing)) {
            $data = $this->validate($request, $this->rulesForListing);
        } else {
            $data = [];
        }

        array_walk($this->publicColumns, function(&$value, $key) {$value = '`'.$value.'`';});
        $query = "SELECT ".implode(",", $this->publicColumns).
                " FROM `".$this->table."`";

        $where = "";
        $bindings = [];
        foreach ($data as $col => $value)
        {
            if (!empty($where)) {
                $where .= " AND ";
            }

            $where .= "`".$col."` = ?";
            $bindings[] = $value;
        }

        if (!empty($where)) {
            $query .= " WHERE ".$where;
        }

        $results = $this->getDb()->select($query, $bindings);
        return response()->json($results, 200);
    }

    /**
     * Devuelve todos los elementos disponibles en la entidad relacionada
     * @param int $id
     * @param string $relationTable
     * @param bool $onlyData
     * @return JsonResponse|array
     */
    protected function listingRelation($id, $relationTable, $onlyData = false)
    {
        $config = $this->relations[$relationTable];

        $colsT1 = $config['publicColumns'];
        array_walk($colsT1, function(&$value, $key) {$value = 'T1.`'.$value.'`';});
        $colsT2 = $config['join']['publicColumns'];
        array_walk($colsT2, function(&$value, $key) {$value = 'T2.`'.$value.'`';});

        $query = "SELECT ".implode(",", $colsT1).(!empty($colsT2) ? ",".implode(",", $colsT2) : "")." ".
                "FROM ".
                    "`".$relationTable."` AS T1 ".
                    "INNER JOIN `".$config['join']['table']."` AS T2 ".
                    "ON T2.`".$config['join']['fkColumn']."` = T1.`id` ".
                "WHERE T2.`".$config['join']['whereColumn']."` = ?";
        $results = $this->getDb()->select($query, [$id]);

        return !$onlyData ? response()->json($results, 200) : $results;
    }

    /**
     * Lee un recurso concreto de la entidad
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function read(Request $request, $id)
    {
        if (!$this->checkAcl($request, $id)) {
            return $this->unauthorized();
        }

        $result = $this->getPublicData($id);

        if (empty($result)) {
            return response()->json(['error' => ['El recurso solicitado no existe']], 404);
        }

        return response()->json($result, 200);
    }

    /**
     * Retorna los datos públicos de un recurso
     * @param int $id
     * @return array
     */
    protected function getPublicData($id)
    {
        array_walk($this->publicColumns, function(&$value, $key) {$value = '`'.$value.'`';});
        return $this->getDb()->selectOne("SELECT ".implode(",", $this->publicColumns).
                " FROM `".$this->table."`".
                " WHERE id = :id",
                [
                    'id' => $id,
                ]);
    }

    /**
     * Crea un recurso en la entidad
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request)
    {
        if (!$this->checkAcl($request)) {
            return $this->unauthorized();
        }

        $data = $this->formatData($this->validate($request, $this->rulesForCreate));

        try {
            if (!$this->createInDb($data)) {
                throw new \Exception('Los datos no han podido ser creados');
            }

            $id = $this->getDb()->getPdo()->lastInsertId();
            $result = $this->getPublicData($id);
            $code = 201;
        } catch (\Exception $ex) {
            $result = ['error' => [$ex->getMessage()]];
            $code = $code ?? 500;
        }

        return response()->json($result, $code);
    }

    /**
     * Inserta una fila en la tabla actual
     * @param array $data
     * @return bool
     */
    protected function createInDb($data)
    {
        $columns = array_keys($data);
        array_walk($columns, function(&$value, $key) {$value = '`'.$value.'`';});
        $params = array_fill(0, count($data), '?');
        $values = array_values($data);

        return $this->getDb()->insert("INSERT INTO `".$this->table."` (".implode(",", $columns).")".
                " VALUES (".implode(",", $params).")", $values);
    }

    /**
     * Actualiza un recurso en la entidad
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        if (!$this->checkAcl($request, $id)) {
            return $this->unauthorized();
        }

        foreach ($this->rulesForUpdate as &$value) {
            $value = preg_replace('/###ID###/', $id, $value);
        }

        $data = $this->formatData($this->validate($request, $this->rulesForUpdate));

        try {
            if (!empty($data)) {
                $this->updateInDb($id, $data);
            }

            $result = $this->getPublicData($id);
            if (empty($result)) {
                $code = 404;
                throw  new \Exception('El recurso solicitado no existe');
            }

            $code = 200;
        } catch (\Exception $ex) {
            $result = ['error' => [$ex->getMessage()]];
            $code = $code ?? 500;
        }

        return response()->json($result, $code);
    }

    /**
     * Actualiza una fila en la tabla actual
     * @param int $id
     * @param array $data
     * @return int
     */
    protected function updateInDb($id, $data)
    {
        $columns = array_keys($data);
        array_walk($columns, function(&$value, $key) {$value = '`'.$value.'` = ?';});
        $values = array_values($data);
        $values[] = $id;

        return $this->getDb()->update("UPDATE `".$this->table."` SET ".implode(",", $columns)." WHERE id = ?", $values);
    }

    /**
     * Elimina un recurso de la entidad
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function delete(Request $request, $id)
    {
        if (!$this->checkAcl($request, $id)) {
            return $this->unauthorized();
        }

        try {
            $result = $this->getPublicData($id);

            if (empty($result)) {
                $code = 404;
                throw  new \Exception('El recurso solicitado no existe');
            }

            if (!$this->getDb()->delete("DELETE FROM `".$this->table."` WHERE id = ?", [$id])) {
                throw new \Exception('Los datos no han podido ser eliminados');
            }

            $code = 200;
        } catch (\Exception $ex) {
            $result = ['error' => [$ex->getMessage()]];
            $code = $code ?? 500;
        }

        return response()->json($result, $code);
    }

    /**
     * Retorna un json response 405
     * @return JsonResponse
     */
    public function notAllowed()
    {
        return response()->json(['error' => ['Método no soportado']], 405);
    }

    /**
     * Retorna un json response 401
     * @return JsonResponse
     */
    public function unauthorized()
    {
        return response()->json(['error' => ['Acceso no autorizado']], 401);
    }

    /**
     * Valida si un array contiene un listado de ids válidos
     * @param array $ids
     * @param string $table
     * @throws \Exception
     */
    protected function validateIds($ids, $table)
    {
        $i = 1;
        foreach ($ids as $id) {
            if (!is_int($id)) {
                throw new \Exception('El elemento nº '.$i.' del array enviado no es un entero');
            }
            if ($id <= 0) {
                throw new \Exception('El elemento nº '.$i.' del array no es mayor o igual a 1');
            }
            if (!$this->getDb()->selectOne("SELECT `id` FROM `".$table."` WHERE `id` = ?", [$id])) {
                throw new \Exception('El elemento nº '.$i.' del array no es un id válido');
            }
            $i++;
        }
    }
}
