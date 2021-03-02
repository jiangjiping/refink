#!/usr/bin/env bash

root_dir_name=TestApp

echo $(pwd)/global/module/$root_dir_name
echo "\n"

mkdir $(pwd)/$root_dir_name
mkdir $(pwd)/$root_dir_name/Cache
mkdir $(pwd)/$root_dir_name/Config
mkdir $(pwd)/$root_dir_name/Controllers
mkdir $(pwd)/$root_dir_name/Library
mkdir $(pwd)/$root_dir_name/Model

echo '<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/2/27
 */

use Refink\Http\Route;

Route::setMiddlewareAlias("web", ["Web\\Auth", "Web\\Limit"]);

Route::get("/v1/wx_login", function () {
    return "this is wx login";
});

Route::group("v2", ["web"], function () {
    Route::get("/user/info", function () {
        return "user info";
    });
    Route::post("/test/aa", function () {
        return "this is v2/aa";
    });
    Route::get("/test/aa", function () {
        return "this is v2/aa";
    });

    Route::post("/user/login", [\App\Controllers\UserController::class, "login"]);
    Route::get("/user/login", [\App\Controllers\UserController::class, "login"]);
});' >> $(pwd)/$root_dir_name/routes.php


echo '<?php
/**
 * this is the server start file
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/2/27
 */

use Refink\Server;

require "./vendor/autoload.php";

use Refink\Database\Config\MySQLConfig;
use Refink\Database\Config\RedisConfig;

$app = new Server("192.168.66.210", 9501, Server::SERVER_TYPE_HTTP | Server::SERVER_TYPE_WEBSOCKET);
$app
    ->initMySQLPool(40, new MySQLConfig("192.168.66.210", 3306, "demo", "pc_vagrant", "A_123456a", []))
    ->initRedisPool(64, new RedisConfig("127.0.0.1", 6379, "DJf7TS698ZpO5Y8Z"))
    ->run();' >> $(pwd)/app_test.php





