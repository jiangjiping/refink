<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/3
 */

namespace Refink\Job;


use Refink\Config;
use Refink\Database\Pool\RedisPool;
use Refink\Job;
use Refink\ShouldQueue;

class RedisQueue implements QueueInterface
{
    public function enqueue(ShouldQueue $job)
    {
        return RedisPool::getConn()->lPush($this->getQueueKey($job->getGroupId() % Config::getInstance()->get('refink.queue_consumer_num')), serialize($job));
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