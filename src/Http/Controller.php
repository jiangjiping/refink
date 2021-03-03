<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/1
 */

namespace Refink\Http;

use Refink\Exception\ApiException;

class Controller implements AbstractController
{

    public function success($data, $msg = 'OK'): string
    {
        return Response::success($data, $msg);
    }

    public function error($errMsg, $data = []): string
    {
        return Response::error($errMsg, $data);
    }

    public function renderError($errMsg, $data = [])
    {
        throw new ApiException($this->error($errMsg, $data));
    }

    public function dispatch($job)
    {
    }
}