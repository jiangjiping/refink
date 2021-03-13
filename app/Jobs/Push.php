<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/12
 */

namespace App\Jobs;


use Refink\Job;
use Swoole\Server;


class Push implements Job
{
    private $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function handle(Server $serv = null)
    {
        foreach ($serv->connections as $fd) {
            $info = $serv->connection_info($fd);
            if ($info['websocket_status'] == 3) {
                $serv->push($fd, json_encode($this->message, JSON_UNESCAPED_UNICODE));
            }
        }

    }
}