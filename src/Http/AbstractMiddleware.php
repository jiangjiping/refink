<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/2/27
 */

namespace Refink\Http;

use Refink\Exception\MiddlewareException;

abstract class AbstractMiddleware
{
    abstract public function handle(&$request);

    public function terminate($errMsg)
    {
        $errMsg = json_encode([
            'code' => 500,
            'data' => [],
            'msg'  => $errMsg
        ]);
        throw new MiddlewareException($errMsg);
    }
}