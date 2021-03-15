<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/1
 */

namespace Refink\Database\Pool;


class Connection
{
    /**
     * idle max seconds
     * @var integer
     */
    const MAX_IDLE_TIME = 60;

    /**
     * the connected database client object
     * @var mixed
     */
    private $dbClient;

    /**
     * connection's last active time
     * @var integer
     */
    private $lastActiveTime;

    private $pingFunc;

    public function __construct($dbClient, $nowTime, $pingFunc)
    {
        $this->lastActiveTime = $nowTime;
        $this->dbClient = $dbClient;
        $this->pingFunc = $pingFunc;
    }

    public function getDbCli()
    {
        return $this->dbClient;
    }

    /**
     * check the connection is already expired
     * @param int $nowTime the current timestamp
     * @return boolean
     */
    public function isExpired(int $nowTime)
    {
        return $nowTime - $this->lastActiveTime > self::MAX_IDLE_TIME;
    }

    public function setActive($ts)
    {
        $this->lastActiveTime = $ts;
    }

    public function destroy()
    {
        if (is_callable([$this->dbClient, 'close'])) {
            $this->dbClient->close();
        }
        $this->dbClient = null;
    }

    public function ping()
    {
        call_user_func($this->pingFunc, $this->dbClient);
    }
}