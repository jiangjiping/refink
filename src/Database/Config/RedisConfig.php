<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/1
 */

namespace Refink\Database\Config;


class RedisConfig extends AbstractConfig
{
    public function __construct($host, $port = 6379, $passwd = '', $dbIndex = 0, $timeout = 0.0)
    {
        $this->host = $host;
        $this->port = $port;
        $this->password = $passwd;
        $this->dbName = $dbIndex;
        $this->timeout = $timeout;
    }
}