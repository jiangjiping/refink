<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/1
 */

namespace Refink\Log;


class Logger
{
    private static $instance;

    private $appLogPath;
    private $appLogFilePrefix = 'Refink';

    /**
     * after append the log then execute the appLogHandler
     * @var callable
     */
    private $appLogHandler;

    const LEVEL_INFO = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_NOTICE = 'NOTICE';

    private function __construct(...$args)
    {
    }

    private function __clone()
    {
    }

    /**
     * @param mixed ...$args
     * @return self
     */
    public static function getInstance(...$args)
    {
        if (is_null(self::$instance)) {
            self::$instance = new static(...$args);
            empty($args[0]) || self::$instance->appLogPath = $args[0];
            empty($args[1]) || self::$instance->appLogHandler = $args[1];
            empty($args[2]) || self::$instance->appLogFilePrefix = $args[2];
        }
        return self::$instance;
    }

    private function log($level, $content)
    {
        $nowTime = time();
        $date = date("Y/m/d H:i:s", $nowTime);
        $prefix = strtolower($this->appLogFilePrefix);
        file_put_contents($this->appLogPath . "/{$prefix}." . date('Ymd', $nowTime) . "." . strtolower($level) . ".log", "[$date] \"{$level}\" $content" . PHP_EOL, FILE_APPEND);
        if (is_callable($this->appLogHandler)) {
            call_user_func($this->appLogHandler, $level, $content);
        }
    }

    public function notice($content)
    {
        $this->log(self::LEVEL_NOTICE, $content);
    }

    public function warning($content)
    {
        $this->log(self::LEVEL_WARNING, $content);
    }

    public function info($content)
    {
        $this->log(self::LEVEL_INFO, $content);
    }

    public function error($content)
    {
        $this->log(self::LEVEL_ERROR, $content);
    }

}