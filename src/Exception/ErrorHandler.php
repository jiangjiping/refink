<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/8
 */

namespace Refink\Exception;


trait ErrorHandler
{
    private function setErrorHandler()
    {
        set_error_handler(function ($errno, $errStr, $errFile, $errLine) {
            $errType = '';
            switch ($errno) {
                case E_ERROR:
                    $errType = 'Php Fatal Error: ';
                    break;
                case E_WARNING:
                    $errType = 'Php Warning: ';
                    break;
                case E_PARSE:
                    $errType = 'Php Parse Error: ';
                    break;
                case E_NOTICE:
                    $errType = 'Php Notice: ';
                    break;
                case E_CORE_ERROR:
                    $errType = 'Php Core Error: ';
                    break;
                case E_CORE_WARNING:
                    $errType = 'Php Core Warning: ';
                    break;
                case E_COMPILE_ERROR:
                    $errType = 'Php Compile error: ';
                    break;
                case E_COMPILE_WARNING:
                    $errType = 'php Compile Warning: ';
                    break;
                case E_USER_ERROR:
                    $errType = 'Php User Error: ';
                    break;
                case E_USER_WARNING:
                    $errType = 'Php User Warning: ';
                    break;
                case E_USER_NOTICE:
                    $errType = 'Php User Notice: ';
                    break;
                default:
                    $errType = "Unknown Error: ";
                    break;
            }

            throw new \Exception("$errType $errStr");
        });
    }
}