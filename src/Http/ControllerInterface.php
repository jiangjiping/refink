<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/1
 */

namespace Refink\Http;


use Refink\Exception\ApiException;
use Refink\Job;
use Refink\ShouldQueue;

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
     * enqueue the job then this job will async run in queue consumer task worker
     * @param $job
     * @return mixed
     */
    public function postShouldQueueJob(ShouldQueue $job);


    /**
     * async post job to swoole task worker
     * @param Job $job
     * @return mixed
     */
    public function postJob(Job $job);
}