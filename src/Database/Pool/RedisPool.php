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

    public static function initPool($size, AbstractConfig $config, $name = "default")
    {
        if (!isset(self::$pools[$name])) {
            self::$pools[$name] = new static($size, $config, $name);
        }
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

    public function tryConnect(int $nowTime)
    {
        if ($this->connNum >= $this->size) {
            return;
        }
        try {
            //coroutine context will switch, so need incr connNum first.
            $this->connNum++;
            $redis = new \Redis();
            $redis->connect($this->config['host'], $this->config['port']);
            if (!empty($this->config['passwd'])) {
                $redis->auth($this->config['passwd']);
            }
            if ($this->config['db'] > 0) {
                $redis->select($this->config['db']);
            }
            $conn = new Connection($redis, $nowTime, function (\Redis $redis) {
                $redis->ping();
            });
            $this->pool->push($conn);
        } catch (\Throwable $e) {
            $this->connNum--;
            throw $e;
        }


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