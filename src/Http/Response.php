<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/3
 */

namespace Refink\Http;


class Response
{
    public static function error($errMsg, $data = [])
    {
        return json_encode([
            'code' => 500,
            'data' => $data,
            'msg'  => $errMsg
        ]);
    }

    public static function success($data, $msg = 'OK'): string
    {
        return json_encode([
            'code' => 0,
            'data' => $data,
            'msg'  => $msg
        ]);
    }
}