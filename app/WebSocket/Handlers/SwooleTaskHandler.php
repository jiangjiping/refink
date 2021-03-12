<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/12
 */

namespace App\WebSocket\Handlers;


use Swoole\WebSocket\Server;

class SwooleTaskHandler
{
    public static function handle(Server $serv, $message)
    {
        foreach ($serv->connections as $fd) {
            $info = $serv->connection_info($fd);
            print_r($info);
            if ($info['websocket_status'] == 3) {
                $serv->push($fd, json_encode($message));
            }
        }

    }
}