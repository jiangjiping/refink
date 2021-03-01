<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/2/27
 */

namespace Refink\Database\Pool;


use Refink\Database\Config\AbstractConfig;
use Swoole\Coroutine\Channel;

class RedisPool extends AbstractPool
{
    use Common;

    private static $pools;

    protected $config;


    public function initPool($size, AbstractConfig $config, $name = "default")
    {
        $this->pool = new Channel($size);
        $nowTime = time();
        $this->buildConfig($config);

        for ($i = 0; $i < $size; $i++) {
            $this->connect($nowTime);
        }
        $this->heartbeat();

        self::$pools[$name] = $this->pool;
    }

    private function buildConfig(AbstractConfig $config)
    {
        $this->config = [
            'host'   => $config->getHost(),
            'port'   => $config->getPort(),
            'passwd' => $config->getPassword(),
            'db'     => $config->getDbName()
        ];
    }

    public function connect(int $nowTime)
    {
        $redis = new \Redis();
        $redis->connect($this->config['host'], $this->config['port']);
        if (!empty($this->config['passwd'])) {
            $redis->auth($this->config['passwd']);
        }
        if ($this->config['db'] > 0) {
            $redis->select($this->config['db']);
        }
        $conn = new Connection($redis, $nowTime);
        $this->pool->push($conn);
    }


    /**
     * @param string $name
     * @return \Redis
     */
    public static function getConn($name = "default")
    {
        return self::getConnection($name);
    }
}