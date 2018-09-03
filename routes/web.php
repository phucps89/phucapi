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

use \Illuminate\Support\Facades\Route;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

Route::get('/', function () {
    return app()->version();
});

Route::get('/reference', [
    'as' => 'reference',
    'uses' => 'Controller@reference'
]);

Route::post('/login', function () {
    $request = \App\Libraries\Helper::getRequestInstance();
    try {
        // attempt to verify the credentials and create a token for the user
        if (! $token = JWTAuth::attempt([
            'email' => $request->get('email'),
            'password' => $request->get('password'),
        ])) {
            return \App\Services\Response\ResponseFacade::send('Invalid email or password', \Illuminate\Http\Response::HTTP_BAD_REQUEST);
        }
    } catch (JWTException $e) {
        // something went wrong whilst attempting to encode the token
        return \App\Services\Response\ResponseFacade::send($e->getMessage(), \Illuminate\Http\Response::HTTP_BAD_REQUEST);
    }

    return \App\Services\Response\ResponseFacade::send([
        'token' => $token
    ]);
});

Route::get('/logout', [
    'uses' => 'Controller@logout'
]);

Route::get('/user', [
    'middleware' => 'jwt.auth',
    'uses' => 'Controller@user'
]);

Route::group([
    'middleware' => 'jwt.auth',
    'prefix' => 'admin',
], function (){
    Route::get('/server-info', [
        'uses' => 'Controller@server'
    ]);

    Route::get('/category', [
        'uses' => 'CategoryController@list'
    ]);
});