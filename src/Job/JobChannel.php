<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/3
 */

namespace Refink\Job;


use Swoole\Coroutine\Channel;

class JobChannel
{
    /**
     * @var array  stored member type is \Swoole\Coroutine\Channel
     */
    private static $channels = [];


    private function __construct(...$args)
    {
    }

    private function __clone()
    {
    }

    /**
     * @param $uniqueKey
     * @return Channel
     */
    public static function getInstance($uniqueKey)
    {
        if (!isset(self::$channels[$uniqueKey])) {
            self::$channels[$uniqueKey] = new Channel(1);
        }
        return self::$channels[$uniqueKey];
    }

    public static function remove($uniqueKey)
    {
        if (isset(self::$channels[$uniqueKey])) {
            unset(self::$channels[$uniqueKey]);
        }
    }
}