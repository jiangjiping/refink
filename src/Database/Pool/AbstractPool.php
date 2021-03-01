<?php
/**
 * The abstract database connection pool
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/2/27
 */

namespace Refink\Database\Pool;


use Refink\Database\Config\AbstractConfig;
use Swoole\Coroutine\Channel;
use Swoole\Timer;

abstract class AbstractPool
{
    /**
     * @var array $config
     */
    protected $config;

    /**
     * @var Channel
     */
    protected $pool;

    abstract public function initPool($size, AbstractConfig $config, $name = "default");

    /**
     * get the database connection object
     * @param string $name
     * @return mixed
     */
    abstract public static function getConn($name = "default");

    /**
     * check all the connections are keep-alive
     */
    public function heartbeat()
    {
        //check pool per minute
        Timer::tick(60 * 1000, function () {
            $nowTime = time();
            //pool empty tell us that: all the connections are busy, so all connection are alive
            if ($this->pool->isEmpty()) {
                return;
            }
            $length = $this->pool->length();
            for ($i = 0; $i < $length; $i++) {
                /** @var Connection $conn */
                $conn = $this->pool->pop(0.1);
                if (empty($conn)) {
                    continue;
                }
                if ($conn->isExpired($nowTime)) {
                    static::connect($nowTime);
                    continue;
                }
                $conn->setLastHeartbeat($nowTime);
                $this->pool->push($conn);
            }

        });
    }

    //create a new connection
    abstract public function connect(int $nowTime);
}