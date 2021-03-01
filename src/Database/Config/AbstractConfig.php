<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/1
 */

namespace Refink\Database\Config;


abstract class AbstractConfig
{

    protected $host;
    protected $port;
    protected $timeout = 0.0;
    protected $password = '';
    /**
     * @var mixed database name, redis-[0~16]
     */
    protected $dbName;

    protected $username;

    /**
     * @var array $options ;
     */
    protected $options;

//    /**
//     * @return static
//     */
//    public abstract function getConfig();

    public function getHost()
    {
        return $this->host;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getTimeout()
    {
        return $this->timeout;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getDbName()
    {
        return $this->dbName;
    }

    public function getUserName()
    {
        return $this->username;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setHost($host)
    {
        $this->host = $host;
    }

    public function setPort($port)
    {
        $this->port = $port;
    }

    public function setPassword($passwd)
    {
        $this->password = $passwd;
    }

    public function setUserName($username)
    {
        $this->username = $username;
    }

    public function setDbName($dbName)
    {
        $this->dbName = $dbName;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }
}