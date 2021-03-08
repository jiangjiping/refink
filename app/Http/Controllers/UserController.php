<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/2/27
 */

namespace App\Http\Controllers;


use App\Jobs\SyncUserInfo;
use Refink\Config;
use Refink\Database\Pool\MySQLPool;
use Refink\Database\Pool\RedisPool;
use Refink\Http\Controller;

class UserController extends Controller
{

    public function login($request)
    {
        //func();
//        var_dump($request['4444']);
//        var_dump($request);
        //  $data = MySQLPool::getConn()->query("select * from `follow` where id=9")->fetch(\PDO::FETCH_ASSOC);
        //var_dump(1111);
        // $data = array_merge($data, $request);
        //var_dump(333);
        $data = [];
        // $server->task(['a' => 1, 'name' => 'xx33333']);

        //$data = RedisPool::getConn()->get("test_key1");
//        for ($i = 0; $i < 10; $i++) {
//            $job = new SyncUserInfo(666, "name_{$i}", "ok_{$i}.png", $i + 100);
//            $this->dispatch($job);
//        }
//        $data['app_key'] = APP_KEY;
//        $data['new_haha'] = "update";

        $data = Config::getInstance()->get("refink");
        var_dump(Config::getInstance()->get('refink.task_worker_num'));

        return $this->success($data, "HAHA");
    }
}