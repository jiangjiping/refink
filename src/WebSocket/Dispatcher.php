<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/6
 */

namespace Refink\WebSocket;


class Dispatcher
{
    private static $routes;

    /**
     * @param $event
     * @param callable $func
     */
    public static function bind($event, $func)
    {
        if (!isset(self::$routes[$event])) {
            self::$routes[$event] = $func;
        }
    }

    public static function getRoutes($event)
    {
        if (!isset(self::$routes[$event])) {
            return null;
        }
        return self::$routes[$event];
    }

}