<?php
/**
 * The abstract database connection pool
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/2/27
 */

namespace Refink\Database\Pool;


use Refink\Database\Config\AbstractConfig;
use Refink\TimerManager;
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

    /**
     * the pool size
     * @var integer
     */
    protected $size;

    /**
     * the total number of current connecting
     * @var integer
     */
    protected $connNum = 0;

    abstract public static function initPool($size, AbstractConfig $config, $name = "default");

    abstract public static function getInstance($name);

    /**
     * get the database connection object
     * @param string $name
     * @return mixed
     */
    abstract public static function getConn($name = "default");

    /**
     * check all the connections are alive
     */
    public function heartbeat()
    {
        //check pool per minute
        $timerId = Timer::tick(Connection::MAX_IDLE_TIME * 1000, function () {
            $nowTime = time();
            //pool empty tell us that: all the connections are busy, so all connection are alive
            if ($this->pool->isEmpty()) {
                return;
            }
            $length = $this->pool->length();
            for ($i = 0; $i < $length; $i++) {
                /** @var Connection $conn */
                $conn = $this->pool->pop(0.01);
                if (empty($conn)) {
                    continue;
                }
                if ($conn->isExpired($nowTime)) {
                    $conn->destroy();
                    $conn = null;
                    if (static::decrConnNum() == 0) {
                        //keep min
                        static::tryConnect($nowTime);
                    }
                    continue;
                }
                $conn->ping();
                $conn->setActive($nowTime);
                $this->pool->push($conn);
            }

        });

        TimerManager::add($timerId);
    }

    //try to create a new connection
    abstract public function tryConnect(int $nowTime);

    /**
     * @return integer
     */
    abstract public function decrConnNum(): int;
}