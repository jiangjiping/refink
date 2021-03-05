<?php
/**
 * set which queue type will be used in the application
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/5
 */

namespace Refink\Job;


class QueueDriver
{
    private static $instance;

    private function __construct(...$args)
    {
    }

    private function __clone()
    {
    }

    /**
     * get or set the queue driver
     * @param QueueInterface $queue
     * @return QueueInterface
     */
    public static function getInstance(QueueInterface $queue)
    {
        if (is_null(self::$instance)) {
            self::$instance = $queue;
        }
        return self::$instance;
    }
}