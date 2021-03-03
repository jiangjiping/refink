<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/1
 */

namespace Refink\Http;

use Refink\Exception\ApiException;
use Refink\Job\Dispatcher;
use Refink\Job\RedisQueue;

class Controller implements ControllerInterface
{
    /**
     * @var callable
     */
    private static $jobDispatcher;

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
        if (is_callable(self::$jobDispatcher)) {
            call_user_func(self::$jobDispatcher);
            return;
        }
        (new RedisQueue())->enqueue($job);
    }

}