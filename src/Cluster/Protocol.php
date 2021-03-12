<?php
/**
 * protocol for cluster node
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/12
 */

namespace Refink\Cluster;


class Protocol
{
    public static function encode($message)
    {
        return pack('N', strlen($message)) . $message;
    }


    public static function decode(string $data): string
    {
        return substr($data, 4);
    }
}