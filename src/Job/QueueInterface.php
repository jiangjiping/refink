<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/3
 */

namespace Refink\Job;

use Refink\Job;

/**
 * First-In First-Out queue
 * Class FIFOQueueInterface
 * @package Refink\Job
 */
interface QueueInterface
{
    public function enqueue(Job $job);

    public function dequeue($grpId);
}