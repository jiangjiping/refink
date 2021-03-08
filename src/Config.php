<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/8
 */

namespace Refink;


class Config
{
    /**
     * @var self
     */
    private static $instance;
    private $conf;

    private function __construct($conf)
    {
        $this->conf = $conf;
    }

    private function __clone()
    {
    }

    /**
     * @param mixed $conf
     * @return static
     */
    public static function getInstance($conf = null)
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($conf);
        }
        return self::$instance;
    }


    /**
     * get the config value
     * @param $name
     * @return mixed
     * @throws \Exception
     */
    public function get($name)
    {
        if ($name == '*') {
            return self::$instance->conf;
        }
        if (isset(self::$instance->conf[$name])) {
            return self::$instance->conf[$name];
        }
        $keys = array_filter(explode('.', $name));
        $conf = self::$instance->conf;
        foreach ($keys as $k) {
            if (isset($conf[$k])) {
                $conf = $conf[$k];
                continue;
            }
            throw new \Exception("config key \"{$name}\" not found!");
        }
        //cache multi level key (eg: a.b.limit), use space-for-time
        is_array($conf) || self::$instance->conf[$name] = $conf;
        return $conf;
    }
}