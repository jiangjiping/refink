<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/1
 */

namespace Refink\Database\Pool;


class Connection
{
    const MAX_IDLE_TIME = 60;

    /**
     * @var mixed the connected database client object
     */
    private $dbClient;

    /**
     * @var integer the last heartbeat timestamp
     */
    private $lastHeartbeat;

    public function __construct($dbClient, $nowTime)
    {
        $this->lastHeartbeat = $nowTime;
        $this->dbClient = $dbClient;
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
        return $nowTime - $this->lastHeartbeat > self::MAX_IDLE_TIME;
    }

    public function setLastHeartbeat($ts)
    {
        $this->lastHeartbeat = $ts;
    }
}