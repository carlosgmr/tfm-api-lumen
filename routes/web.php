<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('administrator', [
    'as' => 'administrator.listing',
    'uses' => 'AdministratorController@listing'
]);
$router->get('administrator/{id}', [
    'as' => 'administrator.read',
    'uses' => 'AdministratorController@read'
]);
$router->post('administrator', [
    'as' => 'administrator.create',
    'uses' => 'AdministratorController@create'
]);
$router->patch('administrator/{id}', [
    'as' => 'administrator.update',
    'uses' => 'AdministratorController@update'
]);
$router->delete('administrator/{id}', [
    'as' => 'administrator.delete',
    'uses' => 'AdministratorController@delete'
]);