<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/1
 */

namespace Refink\Http;


use Refink\Exception\ApiException;
use Refink\Job;

interface ControllerInterface
{
    /**
     * get the successful response string
     * @param $data
     * @param string $msg
     * @return string
     */
    public function success($data, $msg = ''): string;

    /**
     * manual throw exception with the error response string
     * @param $errMsg
     * @param array $data
     * @throws ApiException
     */
    public function renderError($errMsg, $data = []);

    /**
     * get the error response string
     * @param  $errMsg
     * @param array $data
     * @return string
     */
    public function error($errMsg, $data = []): string;

    /**
     * enqueue the job then dispatch
     * this job will run in queue consumer task worker
     * @param $job
     * @return mixed
     */
    public function postJob(Job $job);


    /**
     * push message to websocket client
     * @param array $toUserIds
     * @param $message
     * @return mixed
     */
    public function push(array $toUserIds, $message);
}