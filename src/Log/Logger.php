<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/1
 */

namespace Refink\Log;


class Logger
{

    private static $appLogPath;
    private static $appLogFilePrefix = 'Refink';

    const LEVEL_INFO = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_NOTICE = 'NOTICE';

    public static function init($path, $appLogFilePrefix = 'Refink')
    {
        self::$appLogPath = $path;
        self::$appLogFilePrefix = $appLogFilePrefix;
    }

    private static function log($level, $content)
    {
        $nowTime = time();
        if (empty(self::$appLogPath)) {
            self::$appLogPath = "/var/log";
        }
        $date = date("Y/m/d H:i:s", $nowTime);
        $appName = self::$appLogFilePrefix;
        $level = strtolower($level);
        file_put_contents(self::$appLogPath . "/{$appName}." . date('Ymd', $nowTime) . ".{$level}.log", "[$date] \"{$level}\" $content" . PHP_EOL, FILE_APPEND);
    }

    public static function notice($content)
    {
        self::log(self::LEVEL_NOTICE, $content);
    }

    public static function warning($content)
    {
        self::log(self::LEVEL_WARNING, $content);
    }

    public static function info($content)
    {
        self::log(self::LEVEL_INFO, $content);
    }

    public static function error($content)
    {
        self::log(self::LEVEL_ERROR, $content);
    }


}