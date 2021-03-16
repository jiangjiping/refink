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
     * @param $lockKey
     * @return Channel
     */
    public static function getInstance($lockKey)
    {
        if (!isset(self::$channels[$lockKey])) {
            self::$channels[$lockKey] = new Channel(1);
        }
        return self::$channels[$lockKey];
    }

    public static function remove($lockKey)
    {
        if (isset(self::$channels[$lockKey])) {
            unset(self::$channels[$lockKey]);
        }
    }
}