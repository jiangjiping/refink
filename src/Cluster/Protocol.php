<?php
/**
 * protocol for cluster node
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/12
 */

namespace Refink\Cluster;


use Refink\Job;

class Protocol
{
    public static function encode($message)
    {
        $message = serialize($message);
        return pack('N', strlen($message)) . $message;
    }


    public static function decode(string $data): Job
    {
        $data = substr($data, 4);
        return unserialize($data);
    }
}