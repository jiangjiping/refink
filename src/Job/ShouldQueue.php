<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/13
 */

namespace Refink\Job;

/**
 * Interface ShouldQueueJob
 * running in swoole queue consumer task worker
 * @package Refink
 */

interface ShouldQueue
{
    public function getGroupId();
}