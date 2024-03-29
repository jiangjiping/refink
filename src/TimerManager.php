<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/12
 */

namespace Refink;

use Swoole\Timer;

class TimerManager
{
    private static $timerIds = [];

    public static function add($timerId)
    {
        if (!isset(self::$timerIds[$timerId])) {
            self::$timerIds[$timerId] = true;
        }
    }

    public static function clearAll()
    {
        foreach (self::$timerIds as $timerId => $val) {
            Timer::clear($timerId);
        }
    }
}