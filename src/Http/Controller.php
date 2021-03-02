<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/1
 */

namespace Refink\Http;


use Refink\Exception\ApiException;

interface Controller
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
     * get the error response string by static method
     * @param $errMsg
     * @param array $data
     * @return string
     */
    public static function getErrorResponse($errMsg, $data = []): string;
}