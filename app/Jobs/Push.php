<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/12
 */

namespace App\Jobs;


use Refink\Cluster\Gateway;
use Refink\Config;
use Refink\Job;
use Swoole\Server;


class Push implements Job
{
    private $message;
    private $toUid;
    private $forward = 0;

    public function __construct($toUid, $message)
    {
        $this->toUid = $toUid;
        $this->message = $message;
    }

    public function handle(Server $serv = null)
    {
//        $sock = Gateway::getUserSocket($this->toUid);
//        if (empty($sock)) {
//            //toUid offline
//            return;
//        }
//        list($lanIP, $lanPort, $fd) = explode(':', $sock, 3);
//        $node = "$lanIP:$lanPort";
        $localNode = Config::getInstance()->get('refink.lan_ip') . ':' . Config::getInstance()->get('refink.lan_port');
//        if ($node == $localNode) {
//            $serv->push($fd, $this->message);
//            return;
//        }

        if (!$this->forward) {
            $this->forward = 1;
            Gateway::forward($this, [$localNode]);
        } else {
            foreach ($serv->connections as $fd) {
                $info = $serv->connection_info($fd);
                if (isset($info['websocket_status']) && $info['websocket_status'] == 3) {
                    $serv->push($fd, json_encode($this->message, JSON_UNESCAPED_UNICODE));
                }
            }
            return;
        }


        //broadcast
//        foreach ($serv->connections as $fd) {
//            $info = $serv->connection_info($fd);
//            if ($info['websocket_status'] == 3) {
//                $serv->push($fd, json_encode($this->message, JSON_UNESCAPED_UNICODE));
//            }
//        }

    }
}