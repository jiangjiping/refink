<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/3
 */

namespace Refink\Job;


use Refink\Database\Pool\RedisPool;
use Refink\Job;

class RedisQueue implements QueueInterface
{
    public function enqueue(Job $job)
    {
        return RedisPool::getConn()->lPush($this->getQueueKey($job->getGroupId() % 4), serialize($job));
    }

    private function getQueueKey($groupId)
    {
        return sprintf("jobs_queue:%s:l", $groupId);
    }

    public function dequeue($grpId)
    {
        return unserialize(RedisPool::getConn()->rPop($this->getQueueKey($grpId)));
    }
}