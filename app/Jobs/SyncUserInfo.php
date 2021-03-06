<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/3
 */

namespace App\Jobs;

//redis data write back  to mysql
use Refink\Database\Pool\MySQLPool;
use Refink\Job;
use Refink\Log\Logger;

class SyncUserInfo implements Job
{

    private $userId;
    private $name;
    private $avatar;
    private $time;

    public function __construct($userId, $name, $avatar, $time)
    {
        $this->userId = $userId;
        $this->name = $name;
        $this->avatar = $avatar;
        $this->time = $time;
    }

    public function handle()
    {
        $pdo = MySQLPool::getConn();
        Logger::getInstance()->info("good job ....");
        $stmt = $pdo->prepare("insert into `test` set user_id=:user_id, `name`=:uname, avatar=:avatar, `time`=:utime");
        $stmt->bindValue(":user_id", $this->userId);
        $stmt->bindValue(":uname", $this->name);
        $stmt->bindValue(":avatar", $this->avatar);
        $stmt->bindValue(":utime", $this->time);
        $stmt->execute();
    }

    public function getGroupId()
    {
        return $this->userId;
    }
}