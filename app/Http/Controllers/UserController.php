<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/2/27
 */

namespace App\Http\Controllers;


use App\Jobs\SyncUserInfo;
use App\Models\UserModel;
use Refink\Config;
use Refink\Database\ORM\Model;
use Refink\Database\Pool\MySQLPool;
use Refink\Database\Pool\RedisPool;
use Refink\Http\Controller;
use Swoole\Server;

class UserController extends Controller
{

    public function login($request, Server $serv)
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

        //$data = Config::getInstance()->get("refink");
        //var_dump(Config::getInstance()->get('refink.task_worker_num'));

        //        $lastInsertId = $userModel->insert([
//            "name" => "l'tas",
//            "age" => 3,
//            'height' => 175,
//            'avatar' => 'an.png'
//        ]);
//        var_dump($lastInsertId);

        $userModel = new UserModel();
        $data['a'] = $userModel->find(3);
        $data['b'] = $userModel->find(2);
        $data['c'] = $userModel->where(['age' => 3, 'height' => 175])->get();
        $data['d'] = $userModel->where(['age' => 3, 'height' => 175])->get();
        $data['e'] = $userModel->where('user_id', '>', 2)->getAll();
        $data['f'] = $userModel->where('user_id', 'in', [3, 1])->getAll();
        $data['g'] = $userModel->where("user_id", 4)->get();
        $data['h'] = $userModel->where("user_id", '=', 2)->get();
        $data['i'] = $userModel
            ->where("user_id", '>', 2)
            ->where(['type' => 1, 'age' => 29])
            ->getAll();

        $data['j'] = $userModel
            ->where('name', Model::OPERATOR_LIKE, "%a%")
            ->where('user_id', '>', 2)
            ->where('type', 1)
            ->getAll();

        $data['k'] = $userModel
            ->columns("*")
            ->where('name', Model::OPERATOR_LIKE, "%a%")
            ->where('user_id', '>', 2)
            ->where('type', 1)
            ->orderBy('user_id', Model::SORT_ASC)
            ->limit(1)
            ->getAll();

        $userModel->where("user_id", 1)->update(['name' => 'ffff']);
        $userModel->where("user_id", '>', 3)->update(['avatar' => 'eeee.png', 'type' => Model::incr(10)]);
        $userModel->where("user_id", '>', 3)->update(['avatar' => 'eeee.png', 'height' => Model::decr(10)]);

        for ($i = 0; $i < 10; $i++) {
            $userModel->insert([
                'name'   => "name_{$i}",
                'avatar' => "random_{$i}.png",
                'age'    => 10 + $i,
                'height' => mt_rand(170, 190),
                'type'   => mt_rand(0, 1)
            ]);
        }

        $userModel->remove(10);

        $userModel->where('user_id', Model::OPERATOR_IN, [13, '14'])->delete();

        $data['pdo'] = $userModel->getPDO()->query("select * from `user`")->fetchAll(\PDO::FETCH_ASSOC);

        $data['queue_consumer_num'] = Config::getInstance()->get('refink.queue_consumer_num');

        return $this->success($data, "HAHA");
    }
}