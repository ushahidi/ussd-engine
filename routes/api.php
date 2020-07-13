<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| Chatbot Drivers routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes to connect your chatbot channel with Botman.
| It's strongly recommended to define single routes for each channel. By doing that you
| have more control of the specifics of the chatbot, the configuration and commands.
|
*/

Route::post('/africastalking', 'AfricasTalkingController@handle');
