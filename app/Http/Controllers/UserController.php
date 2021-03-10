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


        return $this->success($data, "HAHA");
    }
}