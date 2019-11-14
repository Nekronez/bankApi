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

$router->group(['middleware' => 'jwt.auth'], function() use ($router) 
    {
        $router->post('card', 'CardController@createCard');
		$router->get('users/{userId}/cards/{id}', 'CardController@getUserCard');

		$router->get('users/{id}', 'UserController@getUser');
		$router->put('users/{id}', 'UserController@putUser');
		$router->delete('users/{id}', 'UserController@deleteUser');

        $router->get('users', function() {
            $users = \App\User::all();
            return response()->json($users);
        });
    }
);

$router->post(
    'auth/login',
    [
       'uses' => 'AuthController@authenticate'
    ]
);

$router->post('users', 'UserController@createUser');
