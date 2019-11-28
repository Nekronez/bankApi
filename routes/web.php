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
$router->group(['middleware' => 'throttle:2,60'], function () use ($router) {
    $router->get('/', function () use ($router) {
        return $router->app->version();
    });
});

$router->group(['middleware' => ['jwt.auth:authorization']], function() use ($router) 
    {
        $router->post('users', 'UserController@createUser');
        $router->get('products/accounts', 'UserController@getUserAccounts');
        $router->get('products/cards', 'UserController@getUserCards');
        $router->get('othercards', 'UserController@getUserOtherBankCards');
        $router->put('pincode',['uses' => 'UserController@putUserPassword']);
    }
);

$router->group(['middleware' => 'jwt.auth:session'], function() use ($router) 
    {
        $router->post('auth/login',['uses' => 'AuthController@getAuthorizationToken']);
        $router->post('auth/checkedsms',['uses' => 'AuthController@checkSMSCode']);
        $router->post('auth/newsms',['uses' => 'AuthController@sendNewSmsCode']);
    }
);

$router->post('auth/verified',['uses' => 'AuthController@verification']);