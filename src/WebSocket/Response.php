<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/6
 */

namespace Refink\WebSocket;


class Response
{
    private static $successPacker;
    private static $errorPacker;

    public static function error($errMsg, $data = [])
    {
        if (is_callable(self::$errorPacker)) {
            return call_user_func(self::$errorPacker, $errMsg, $data);
        }
        return json_encode([
            'code' => 500,
            'data' => $data,
            'msg'  => $errMsg
        ]);
    }

    public static function success($data, $msg = 'OK'): string
    {
        if (is_callable(self::$successPacker)) {
            return call_user_func(self::$errorPacker, $data, $msg);
        }
        return json_encode([
            'code' => 0,
            'data' => $data,
            'msg'  => $msg
        ]);
    }

    public static function setPacker($successPacker, $errorPacker)
    {
        self::$successPacker = $successPacker;
        self::$errorPacker = $errorPacker;
    }
}