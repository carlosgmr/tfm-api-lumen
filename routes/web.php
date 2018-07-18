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

// ADMINISTRATOR
$router->get('administrator', [
    'as' => 'administrator.listing',
    'uses' => 'AdministratorController@listing'
]);
$router->get('administrator/{id:[1-9][0-9]*}', [
    'as' => 'administrator.read',
    'uses' => 'AdministratorController@read'
]);
$router->post('administrator', [
    'as' => 'administrator.create',
    'uses' => 'AdministratorController@create'
]);
$router->patch('administrator/{id:[1-9][0-9]*}', [
    'as' => 'administrator.update',
    'uses' => 'AdministratorController@update'
]);
$router->delete('administrator/{id:[1-9][0-9]*}', [
    'as' => 'administrator.delete',
    'uses' => 'AdministratorController@delete'
]);

// INSTRUCTOR
$router->get('instructor', [
    'as' => 'instructor.listing',
    'uses' => 'InstructorController@listing'
]);
$router->get('instructor/{id:[1-9][0-9]*}', [
    'as' => 'instructor.read',
    'uses' => 'InstructorController@read'
]);
$router->post('instructor', [
    'as' => 'instructor.create',
    'uses' => 'InstructorController@create'
]);
$router->patch('instructor/{id:[1-9][0-9]*}', [
    'as' => 'instructor.update',
    'uses' => 'InstructorController@update'
]);
$router->delete('instructor/{id:[1-9][0-9]*}', [
    'as' => 'instructor.delete',
    'uses' => 'InstructorController@delete'
]);

// USER
$router->get('user', [
    'as' => 'user.listing',
    'uses' => 'UserController@listing'
]);
$router->get('user/{id:[1-9][0-9]*}', [
    'as' => 'user.read',
    'uses' => 'UserController@read'
]);
$router->post('user', [
    'as' => 'user.create',
    'uses' => 'UserController@create'
]);
$router->patch('user/{id:[1-9][0-9]*}', [
    'as' => 'user.update',
    'uses' => 'UserController@update'
]);
$router->delete('user/{id:[1-9][0-9]*}', [
    'as' => 'user.delete',
    'uses' => 'UserController@delete'
]);