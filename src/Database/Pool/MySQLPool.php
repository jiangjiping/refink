<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/2/27
 */

namespace Refink\Database\Pool;


use Refink\Database\Config\AbstractConfig;
use Swoole\Coroutine\Channel;

class MySQLPool extends AbstractPool
{
    use Common;

    protected $config;

    protected static $size;

    private function __construct($size, AbstractConfig $config, $name = "default")
    {
        $size < 1 && $size = 1;
        $this->pool = new Channel($size);
        $nowTime = time();
        $this->buildConfig($config);
        for ($i = 0; $i < $size; $i++) {
            $this->connect($nowTime);
        }
        $this->heartbeat();
    }

    private function __clone()
    {
    }

    public static function initPool($size, AbstractConfig $config, $name = "default")
    {
        if (!isset(self::$pools[$name])) {
            self::$pools[$name] = new static($size, $config, $name);
        }
    }

    public static function getInstance($name)
    {
        return self::$pools[$name]->pool;
    }

    private function buildConfig(AbstractConfig $config)
    {
        $this->config = [
            'dsn'      => sprintf("mysql:host=%s;dbname=%s;port=%d", $config->getHost(), $config->getDbName(), $config->getPort()),
            'username' => $config->getUserName(),
            'passwd'   => $config->getPassword(),
            'options'  => $config->getOptions()
        ];
    }

    public function connect(int $nowTime)
    {
        $pdo = new \PDO($this->config['dsn'], $this->config['username'], $this->config['passwd'], $this->config['options']);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $conn = new Connection($pdo, $nowTime);
        $this->pool->push($conn);
    }


    /**
     * @param string $name
     * @return \PDO
     */
    public static function getConn($name = "default")
    {
        return self::getConnection($name);
    }
}