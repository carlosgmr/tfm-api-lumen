<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->table = 'user';
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

        $this->relations = [
            'group' => [
                'join' => [
                    'table' => 'user_group',
                    'publicColumns' => ['added_at'],
                    'fkColumn' => 'group',
                    'whereColumn' => 'user',
                ],
                'publicColumns' => ['id', 'name', 'description', 'created_at', 'updated_at', 'active'],
            ]
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
            /* @var $hashManager \Illuminate\Hashing\HashManager */
            $hashManager = app('hash');
            $data['password'] = $hashManager->make($data['password'], ['rounds' => 10]);
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
            case 'user.read':
            case 'user.listing':
            case 'user.listing.group':
                if (!in_array($request->appUser->role, [self::ROLE_ADMINISTRATOR, self::ROLE_INSTRUCTOR, self::ROLE_USER])) {
                    return false;
                }
                break;
            case 'user.update':
                if (!in_array($request->appUser->role, [self::ROLE_ADMINISTRATOR, self::ROLE_USER])) {
                    return false;
                }
                if ($request->appUser->role == self::ROLE_USER && $request->appUser->id != $id) {
                    return false;
                }
                break;
            case 'user.create':
            case 'user.delete':
            case 'user.current.group':
                if (!in_array($request->appUser->role, [self::ROLE_ADMINISTRATOR])) {
                    return false;
                }
                break;
            case 'user.listing.questionnairesMade':
                if (!in_array($request->appUser->role, [self::ROLE_ADMINISTRATOR, self::ROLE_INSTRUCTOR, self::ROLE_USER])) {
                    return false;
                }
                if ($request->appUser->role == self::ROLE_USER && $request->appUser->id != $id) {
                    return false;
                }
                break;
            default:
                return false;
        }

        return true;
    }

    /**
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function listingGroup($id)
    {
        return $this->listingRelation($id, 'group');
    }

    /**
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function currentGroup(Request $request, $id)
    {
        $data = $this->validate($request, [
            'group' => 'present|array|max:100'
        ]);

        try {
            $this->validateIds($data['group'], 'group');

            // obtenemos el estado actual de la tabla
            $currentState = $this->getDb()->select("SELECT `id`, `group` "
                    . "FROM `user_group` "
                    . "WHERE `user` = ?", [$id]);
            $currentIds = [];

            // realizamos en BD las operaciones de eliminación e insercción necesarias
            foreach ($currentState as $current) {
                $currentIds[] = $current->group;
                if (!in_array($current->group, $data['group'])) {
                    $this->getDb()->delete("DELETE "
                            . "FROM `user_group` "
                            . "WHERE `id` = ?", [$current->id]);
                }
            }

            foreach ($data['group'] as $value) {
                if (!in_array($value, $currentIds)) {
                    $this->getDb()->insert("INSERT "
                            . "INTO `user_group`(`user`, `group`) "
                            . "VALUES (?, ?)", [$id, $value]);
                }
            }

            // devolvemos el estado actual
            $result = $this->listingRelation($id, 'group', true);
            $code = 200;

        } catch (\Exception $ex) {
            $result = ['error' => [$ex->getMessage()]];
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
    public function questionnairesMade(Request $request, $id)
    {
        if (!$this->checkAcl($request)) {
            return $this->unauthorized();
        }

        $query = "SELECT ".
                "r.`questionary` AS `questionary_id`,".
                "q.`group` AS `group_id`,".
                "q.`title` AS `questionary_title`,".
                "g.`name` AS `group_name` ".
            "FROM ".
                "`registry` AS r ".
                "INNER JOIN `questionary` AS q ON r.`questionary` = q.`id` ".
                "INNER JOIN `group` AS g ON q.`group` = g.`id` ".
            "WHERE ".
                "r.`user` = ? ".
            "GROUP BY ".
                "r.`questionary` ".
            "ORDER BY q.`group`, r.`questionary`";
        $bindings = [$id];
        $results = [];

        $questionarys = $this->getDb()->select($query, $bindings);

        foreach ($questionarys as $questionary) {
            $results[] = [
                'id' => $questionary->questionary_id,
                'title' => $questionary->questionary_title,
                'group' => [
                    'id' => $questionary->group_id,
                    'name' => $questionary->group_name,
                ],
            ];
        }
        
        return response()->json($results, 200);
    }
}
