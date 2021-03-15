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
        //multi times invoke at the same coroutine context
        if (isset(self::$coroutineContext[$cid])) {
            /** @var Connection $conn */
            $conn = self::$coroutineContext[$cid];
            //check expired for when the process only have on coroutine
            if ($conn->isExpired($nowTime)) {
                $conn = null;
                self::$pools[$name]->decrConnNum();
                self::$pools[$name]->tryConnect($nowTime);
                goto GET_NEW_CONN;
            }
            return $conn->getDbCli();
        }

        GET_NEW_CONN:
        //check if pool size reach max
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