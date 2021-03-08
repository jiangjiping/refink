<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/8
 */

namespace App\Console;


use Refink\Database\Pool\RedisPool;

class Migrate
{
    public function run($args)
    {
        $val = RedisPool::getConn()->get("test_key1");


        echo "Migrate running!\n";
    }
}