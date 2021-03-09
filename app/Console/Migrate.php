<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/8
 */

namespace App\Console;


use Refink\Database\Pool\RedisPool;
use Refink\Log\Logger;

class Migrate
{
    public function run($args)
    {
        func();
        $val = RedisPool::getConn()->get("test_key1");
        var_dump($val);

        echo "Migrate running!\n";
        Logger::getInstance()->info("migrate finished");
    }
}