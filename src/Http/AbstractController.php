<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/1
 */

namespace Refink\Http;


use Refink\Exception\ApiException;

abstract class AbstractController
{
    /**
     * get the successful response string
     * @param $data
     * @param string $msg
     * @return string
     */
    abstract public function success($data, $msg = ''): string;

    /**
     * manual throw exception with the error response string
     * @param $errMsg
     * @param array $data
     * @throws ApiException
     */
    abstract public function renderError($errMsg, $data = []);

    /**
     * get the error response string
     * @param  $errMsg
     * @param array $data
     * @return string
     */
    abstract public function error($errMsg, $data = []): string;
}