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

// AUTH
$router->post('auth/login', [
    'as' => 'auth.login',
    'uses' => 'AuthController@authenticate'
]);

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
$router->get('instructor/{id:[1-9][0-9]*}/group', [
    'as' => 'instructor.listing.group',
    'uses' => 'InstructorController@listingGroup'
]);
$router->post('instructor/{id:[1-9][0-9]*}/group', [
    'as' => 'instructor.current.group',
    'uses' => 'InstructorController@currentGroup'
]);
$router->get('instructor/{id:[1-9][0-9]*}/questionary', [
    'as' => 'instructor.listing.questionary',
    'uses' => 'InstructorController@listingQuestionary'
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
$router->get('user/{id:[1-9][0-9]*}/group', [
    'as' => 'user.listing.group',
    'uses' => 'UserController@listingGroup'
]);
$router->post('user/{id:[1-9][0-9]*}/group', [
    'as' => 'user.current.group',
    'uses' => 'UserController@currentGroup'
]);
$router->get('user/{id:[1-9][0-9]*}/group/questionary', [
    'as' => 'user.listing.questionnairesMade',
    'uses' => 'UserController@questionnairesMade'
]);

// GROUP
$router->get('group', [
    'as' => 'group.listing',
    'uses' => 'GroupController@listing'
]);
$router->get('group/{id:[1-9][0-9]*}', [
    'as' => 'group.read',
    'uses' => 'GroupController@read'
]);
$router->post('group', [
    'as' => 'group.create',
    'uses' => 'GroupController@create'
]);
$router->patch('group/{id:[1-9][0-9]*}', [
    'as' => 'group.update',
    'uses' => 'GroupController@update'
]);
$router->delete('group/{id:[1-9][0-9]*}', [
    'as' => 'group.delete',
    'uses' => 'GroupController@delete'
]);
$router->get('group/{id:[1-9][0-9]*}/instructor', [
    'as' => 'group.listing.instructor',
    'uses' => 'GroupController@listingInstructor'
]);
$router->post('group/{id:[1-9][0-9]*}/instructor', [
    'as' => 'group.current.instructor',
    'uses' => 'GroupController@currentInstructor'
]);
$router->get('group/{id:[1-9][0-9]*}/user', [
    'as' => 'group.listing.user',
    'uses' => 'GroupController@listingUser'
]);
$router->post('group/{id:[1-9][0-9]*}/user', [
    'as' => 'group.current.user',
    'uses' => 'GroupController@currentUser'
]);

// QUESTIONARY MODEL
$router->get('questionary-model', [
    'as' => 'questionaryModel.listing',
    'uses' => 'QuestionaryModelController@listing'
]);
$router->get('questionary-model/{id:[1-9][0-9]*}', [
    'as' => 'questionaryModel.read',
    'uses' => 'QuestionaryModelController@read'
]);
$router->post('questionary-model', [
    'as' => 'questionaryModel.create',
    'uses' => 'QuestionaryModelController@create'
]);
$router->patch('questionary-model/{id:[1-9][0-9]*}', [
    'as' => 'questionaryModel.update',
    'uses' => 'QuestionaryModelController@update'
]);
$router->delete('questionary-model/{id:[1-9][0-9]*}', [
    'as' => 'questionaryModel.delete',
    'uses' => 'QuestionaryModelController@delete'
]);

// QUESTION MODEL
$router->get('question-model', [
    'as' => 'questionModel.listing',
    'uses' => 'QuestionModelController@listing'
]);
$router->get('question-model/{id:[1-9][0-9]*}', [
    'as' => 'questionModel.read',
    'uses' => 'QuestionModelController@read'
]);
$router->post('question-model', [
    'as' => 'questionModel.create',
    'uses' => 'QuestionModelController@create'
]);
$router->patch('question-model/{id:[1-9][0-9]*}', [
    'as' => 'questionModel.update',
    'uses' => 'QuestionModelController@update'
]);
$router->delete('question-model/{id:[1-9][0-9]*}', [
    'as' => 'questionModel.delete',
    'uses' => 'QuestionModelController@delete'
]);

// QUESTIONARY
$router->get('questionary', [
    'as' => 'questionary.listing',
    'uses' => 'QuestionaryController@listing'
]);
$router->get('questionary/{id:[1-9][0-9]*}', [
    'as' => 'questionary.read',
    'uses' => 'QuestionaryController@read'
]);
$router->post('questionary', [
    'as' => 'questionary.create',
    'uses' => 'QuestionaryController@create'
]);
$router->patch('questionary/{id:[1-9][0-9]*}', [
    'as' => 'questionary.update',
    'uses' => 'QuestionaryController@update'
]);
$router->delete('questionary/{id:[1-9][0-9]*}', [
    'as' => 'questionary.delete',
    'uses' => 'QuestionaryController@delete'
]);
$router->get('questionary/{id:[1-9][0-9]*}/complete', [
    'as' => 'questionary.readComplete',
    'uses' => 'QuestionaryController@readComplete'
]);

// QUESTION
$router->get('question', [
    'as' => 'question.listing',
    'uses' => 'QuestionController@listing'
]);
$router->get('question/{id:[1-9][0-9]*}', [
    'as' => 'question.read',
    'uses' => 'QuestionController@read'
]);
$router->post('question', [
    'as' => 'question.create',
    'uses' => 'QuestionController@create'
]);
$router->patch('question/{id:[1-9][0-9]*}', [
    'as' => 'question.update',
    'uses' => 'QuestionController@update'
]);
$router->delete('question/{id:[1-9][0-9]*}', [
    'as' => 'question.delete',
    'uses' => 'QuestionController@delete'
]);

// ANSWER
$router->get('answer', [
    'as' => 'answer.listing',
    'uses' => 'AnswerController@listing'
]);
$router->get('answer/{id:[1-9][0-9]*}', [
    'as' => 'answer.read',
    'uses' => 'AnswerController@read'
]);
$router->post('answer', [
    'as' => 'answer.create',
    'uses' => 'AnswerController@create'
]);
$router->patch('answer/{id:[1-9][0-9]*}', [
    'as' => 'answer.update',
    'uses' => 'AnswerController@update'
]);
$router->delete('answer/{id:[1-9][0-9]*}', [
    'as' => 'answer.delete',
    'uses' => 'AnswerController@delete'
]);

// REGISTRY
$router->get('registry', [
    'as' => 'registry.listing',
    'uses' => 'RegistryController@listing'
]);
$router->get('registry/{id:[1-9][0-9]*}', [
    'as' => 'registry.read',
    'uses' => 'RegistryController@read'
]);
$router->post('registry', [
    'as' => 'registry.create',
    'uses' => 'RegistryController@create'
]);
$router->patch('registry/{id:[1-9][0-9]*}', [
    'as' => 'registry.update',
    'uses' => 'RegistryController@update'
]);
$router->delete('registry/{id:[1-9][0-9]*}', [
    'as' => 'registry.delete',
    'uses' => 'RegistryController@delete'
]);