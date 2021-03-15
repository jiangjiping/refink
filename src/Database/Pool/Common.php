<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/1
 */

namespace Refink\Database\Pool;


use Refink\Database\Config\AbstractConfig;
use Swoole\Coroutine\Channel;

trait Common
{

    private static $coroutineContext;
    private static $pools;

    private function __construct($size, AbstractConfig $config, $name = "default")
    {
        $this->pool = new Channel($size);
        $this->size = $size;
        $nowTime = time();
        $this->buildConfig($config);
        //keep min conn
        $this->tryConnect($nowTime);
        $this->heartbeat();
    }

    private function __clone()
    {
    }

    public function decrConnNum(): int
    {
        if ($this->connNum == 0) {
            return 0;
        }
        return --$this->connNum;
    }

    private static function getConnection($name = "default")
    {
        $cid = \co::getCid();
        $nowTime = time();
        //multi times invoke "getConnection" at the same coroutine context
        if (isset(self::$coroutineContext[$cid])) {
            return self::$coroutineContext[$cid]->getDbCli();
        }

        if (self::getInstance($name)->isEmpty()) {
            self::$pools[$name]->tryConnect($nowTime);
        }
        /** @var Connection $conn */
        $conn = self::getInstance($name)->pop();
        self::$coroutineContext[$cid] = $conn;
        defer(function () use ($conn, $name, $cid) {
            self::getInstance($name)->push($conn);
            unset(self::$coroutineContext[$cid]);
        });
        $conn->setActive($nowTime);
        return $conn->getDbCli();
    }

    /**
     * @param $name
     * @return Channel
     */
    public static function getInstance($name)
    {
        return self::$pools[$name]->pool;
    }
}