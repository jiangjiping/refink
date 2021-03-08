<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/2/27
 */

use Refink\Http\Route;
use Refink\WebSocket\Dispatcher;

Route::setMiddlewareAlias('auth', [\App\Http\Middleware\Auth::class]);

Route::get("/demo", function () {
    return "this is api demo";
});

Route::group("api", ['auth'], function () {
    Route::get("/user/info", function () {
        return "user info";
    });
    Route::post("/test/aa", function () {
        return "this is v2/aa";
    });
    Route::get("/test/aa", function () {
        return "this is v2/aa";
    });

    Route::post("/user/login", [\App\Http\Controllers\UserController::class, 'login']);
    Route::get("/user/login", [\App\Http\Controllers\UserController::class, 'login']);

    Route::get("/user/login1", [\App\Http\Controllers\UserController::class, 'login']);
    Route::get("/user/login2", function () {
        return "this is login2";
    });
});

//define websocket route
Dispatcher::bind("login", [\App\WebSocket\Handlers\UserHandler::class, 'login']);