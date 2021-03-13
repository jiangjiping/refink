<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/3
 */

namespace Refink;

/**
 * Interface Job
 * running in normal swoole task worker
 * @package Refink
 */
interface Job
{
    public function handle(\Swoole\Server $server = null);
}