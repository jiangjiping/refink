<?php
/**
 * this is the server start file
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/2/27
 */

use Refink\Server;

require './vendor/autoload.php';

$app = new Server("0.0.0.0", 9501, Server::SERVER_TYPE_HTTP | Server::SERVER_TYPE_WEBSOCKET);
//$app->initMySQLPool(40);
//$app->initRedisPool(64);
//$app->setQueueDriver(new \Refink\Job\RedisQueue());
//$app->setSwooleConf([
//    'task_worker_num'       => 4,
//    'task_enable_coroutine' => true
//]);
//$app->setAppLogHandler(__DIR__ . '/app/Logs', function ($level, $content) {
//    //process the log yourself, but not blocking this.
//    var_dump($level);
//    echo $content . PHP_EOL;
//});
//$app->setAppName("MY_APP");

$app->run();