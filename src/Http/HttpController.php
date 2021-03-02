<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/1
 */

namespace Refink\Http;


use Refink\Exception\ApiException;

class HttpController implements Controller
{

    public function success($data, $msg = 'OK'): string
    {
        return json_encode([
            'code' => 0,
            'data' => $data,
            'msg'  => $msg
        ]);
    }

    public function error($errMsg, $data = []): string
    {
        return json_encode([
            'code' => 500,
            'data' => $data,
            'msg'  => $errMsg
        ]);
    }

    public function renderError($errMsg, $data = [])
    {
        throw new ApiException($this->error($errMsg, $data));
    }
}