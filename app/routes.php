<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/2/27
 */

use App\Http\Controllers\UserController;
use App\Http\Middleware\Auth;
use App\WebSocket\Handlers\UserHandler;
use Refink\Http\Route;
use Refink\WebSocket\Dispatcher;

Route::setMiddlewareAlias('auth', [Auth::class]);

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

    Route::post("/user/login", [UserController::class, 'login']);
    Route::get("/user/login", [UserController::class, 'login']);

    Route::get("/user/login1", [UserController::class, 'login']);
    Route::get("/user/login2", function () {
        return "this is login2";
    });

    Route::get("/user/test", [UserController::class, 'test']);
});

//define websocket route
Dispatcher::bind("login", [UserHandler::class, 'login']);
