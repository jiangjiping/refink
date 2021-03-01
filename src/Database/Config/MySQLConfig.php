<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/1
 */

namespace Refink\Database\Config;


class MySQLConfig extends AbstractConfig
{

    public function __construct($host, $port, $dbName, $username, $passwd, array $options)
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $passwd;
        $this->options = $options;
        $this->dbName = $dbName;
    }

}