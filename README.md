
### Refink协程框架特性

 - 支持基于http的restful api接口，自带类似laravel的静态路由、中间件
 - 支持websocket消息路由(类似http的url路由)。只需要在对于的handler下写业务代码
 - 支持分布式生产端异步派发任务 (具体配置和使用见下文)
 - 支持数据库连接池, 连接池的连接都是有心跳检测自动保活机制，当前进支持redis和mysql
 - 高性能：和原生swoole非常接近，因为框架代码极其精简，带来的性能损耗忽略不计
    
    
### 使用方法
    
 -  使用composer安装项目:

```
composer create-project refink/refink
   
```
 
 - 命令行启动server(终端挂起模式)
 
```
 refink/server start

```
 - 命令行启动server(daemonize守护进程模式)
 
```
 refink/server start -d

```


### 如何访问后端地址？

- 当server成功启动时，会如下输出
```
 ____       __ _       _    
|  _ \ ___ / _(_)_ __ | | __
| |_) / _ \ |_| | '_ \| |/ /
|  _ <  __/  _| | | | |   < 
|_| \_\___|_| |_|_| |_|_|\_\
                            
**************************************************
http server       |  http://0.0.0.0:9501
websocket server  |  ws://0.0.0.0:9501
app log path      |  /var/log
swoole version    |  4.4.16
php version       |  7.2.24
**************************************************
press CTRL + C to stop.

```
假设您服务器对外可以访问的ip为: 192.168.1.122
则可访问: 

```
http://192.168.1.122:9501/demo

```

- 访问其他路由时，确保mysql或redis的连接信息已正确配置
 
### 路由配置

```
app/routes.php中有示例代码

```

 
### 如何同时支持http和websocket?
 
```
 $app = new Server("0.0.0.0", 9501, Server::SERVER_TYPE_HTTP | Server::SERVER_TYPE_WEBSOCKET );

```

### 关于env环境配置

- dev: php.ini添加配置APP_ENV=dev，则框架会自动加载项目根目录的 config_dev.php, 默认未配置也是加载它
- test: php.ini添加配置APP_ENV=test, 则框架会自动加载项目根目录 config_test.php
- prod: php.ini添加配置APP_ENV=prod, 则框架会自动加载 config_prod.php

### php-cli命令行执行业务代码

- 场景1：在linux crontab中执行的计划任务脚本
- 场景2：需要单独在终端执行的代码
- 使用方法: composer安装之后，项目根目录有个console可执行文件，这是php-cli下的入口文件, 单个可执行的php callable对象
本框架称其为 用户自定义的command

Usage: 

```
 1、注册command命令:

\Refink\Command::register("migrate", [\App\Console\Migrate::class, 'run'], "迁移数据");
 
 2 、terminal执行：

 [~!#] /path/to/console migrate

 3、crontab配置
 * 2 * * * /path/to/console migrate

```
### 数据库基本操作

```
use Refink\Database\Pool\MySQLPool;
use Refink\Database\Pool\RedisPool;

<?php

//mysql操作，整个调用链都是ide能代码提示的
MySQLPool::getConn()->query("select * from `follow` where id=9")->fetch(\PDO::FETCH_ASSOC)

//redis
RedisPool::getConn()->get("test_key1")

```

### ORM快速开始

```
        $userModel = new UserModel();
        //主键查询
        $data['a'] = $userModel->find(3);
        $data['b'] = $userModel->find(2);

        //多个等值条件查询
        $data['c'] = $userModel->where(['age' => 3, 'height' => 175])->get();
       

        //比较条件
        $data['e'] = $userModel->where('user_id', '>', 2)->getAll();
        $data['f'] = $userModel->where('user_id', 'in', [3, 1])->getAll();
        $data['g'] = $userModel->where("user_id", 4)->get();
        $data['h'] = $userModel->where("user_id", '=', 2)->get();

        //比较条件和等值条件组合
        $data['i'] = $userModel
            ->where("user_id", '>', 2)
            ->where(['type' => 1, 'age' => 29])
            ->getAll();

        $data['j'] = $userModel
            ->where('name', Model::OPERATOR_LIKE, "%a%")
            ->where('user_id', '>', 2)
            ->where('type', 1)
            ->getAll();

        //order by和limit
        $data['k'] = $userModel
            ->columns("*")
            ->where('name', Model::OPERATOR_LIKE, "%a%")
            ->where('user_id', '>', 2)
            ->where('type', 1)
            ->orderBy('user_id', Model::SORT_ASC)
            ->limit(1)
            ->getAll();
        
        //更新
        $userModel->where("user_id", 1)->update(['name' => 'ffff']);
        $userModel->where("user_id", '>', 3)->update(['avatar' => 'eeee.png', 'type' => Model::incr(10)]);
        $userModel->where("user_id", '>', 3)->update(['avatar' => 'eeee.png', 'height' => Model::decr(10)]);

        //插入
        for ($i = 0; $i < 10; $i++) {
            $userModel->insert([
                'name'   => "name_{$i}",
                'avatar' => "random_{$i}.png",
                'age'    => 10 + $i,
                'height' => mt_rand(170, 190),
                'type'   => mt_rand(0, 1)
            ]);
        }
         
         //主键删除
        $userModel->remove(10);
        
        //条件删除
        $userModel->where('user_id', Model::OPERATOR_IN, [13, '14'])->delete();

        //原生查询
        $data['pdo'] = $userModel->getPDO()->query("select * from `user`")->fetchAll(\PDO::FETCH_ASSOC);

```


### 异步任务配置

- 使用 Refink\Server::setQueueDriver()方法进程配置
- 原理: 假设配置的队列消费进程数量为2, swoole的task worker进程数为4
  则从swoole的task worker中选取前两个task worker 作为queue consumer(队列消费)进程，队列消费进程内部是一个while循环，
  不停的从队列中pop任务出来消费。你可能会担心while循环会导致队列消费进程无法reload，这一点refink已经自动处理好了。这样做比
  单独的创建自定义的进程池作为队列消费进程好管理很多。

### Refink作者的思考

- 关于目前的框架生态：目前基于swoole的常用框架动不动就是微服务，而且java风味很浓重，对于phper来说其实不太友好，等于要重新习惯一些编码方式和风格
  这个是有上手成本的，而且整个框架封装太重，性能必然比原生swoole要降低不少，php的哲学就是简单实用，能快速的迭代开发产品。
  如果框架笨重难上手性能低，php的优势就没有了，所以部分人直接选择java了。
- 关于微服务：微服务要解决的问题是，当一个项目很庞大，开发者众多时。如果还是单个project通过ide管理，那分支代码冲突会非常频繁，
冲突解决稍微不注意，就把别人的功能模块弄坏了，而且代码量巨大，像java c++这样的ide还需要代码解析重新索引，ide git pull一下就卡cpu直接100%。
这种场景使用微服务才是能解决一些问题的，而且微服务的框架要提供像调用本地方法一样优雅的编码方式，调用其他微服务模块的方法时，ide能代码提示，
如果像调用http接口一样调用微服务，我觉得这个微服务框架也是不够出色的。

- php7和swoole的出现都是为了给php性能带来提升，作为框架的开发者，不能反其道而行，成为拖慢性能的罪魁祸首。
swoole的性能已经非常之高效了,作者曾经压测对比过:
基于c++的drogon框架、java8 + netty4、swoole4+php7 、golang 1.14自带http server、基于c语言libevent多线程实现的http server
在同样的环境下，QPS性能排序如下:
- 多线程ibevent_http_server > drogon > swoole > golang > java
其中java和golang接近，swoole和drogon接近。

 
#### ab压测 
  
  - win7下virtual box虚拟机: i3 4核cpu 4G内
  - 压测本机redis benchmark: 10w
  - 无任何IO，原生swoole qps ：5w+
  - Refink框架 UserController::login() 输出hello world qps: 4.8w
  - Refink框架 UserController::login() 操作一次redis get命令qps: 2.8w
  - Refink框架 UserController::login() 读一次mysql, 走主键索引 qps: 1w+
  
```
<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/2/27
 */

namespace App\Controllers;

use Refink\Database\Pool\MySQLPool;
use Refink\Http\HttpController;

class UserController extends HttpController
{

    public function login(array $request)
    {
        $data = MySQLPool::getConn()->query("select * from `follow` where id=9")->fetch(\PDO::FETCH_ASSOC);
        return $this->success($data, "HAHA");
    }
}

```
   
```
[root@pc-vagrant ~]# ab -n 20000 -c 2000 -k http://127.0.0.1:9501/api/user/login
This is ApacheBench, Version 2.3 <$Revision: 1430300 $>
Copyright 1996 Adam Twiss, Zeus Technology Ltd, http://www.zeustech.net/
Licensed to The Apache Software Foundation, http://www.apache.org/

Benchmarking 127.0.0.1 (be patient)
Completed 2000 requests
Completed 4000 requests
Completed 6000 requests
Completed 8000 requests
Completed 10000 requests
Completed 12000 requests
Completed 14000 requests
Completed 16000 requests
Completed 18000 requests
Completed 20000 requests
Finished 20000 requests


Server Software:        Refink
Server Hostname:        127.0.0.1
Server Port:            9501

Document Path:          /api/user/login
Document Length:        100 bytes

Concurrency Level:      2000
Time taken for tests:   1.912 seconds
Complete requests:      20000
Failed requests:        0
Write errors:           0
Keep-Alive requests:    20000
Total transferred:      4980000 bytes
HTML transferred:       2000000 bytes
Requests per second:    10462.81 [#/sec] (mean)
Time per request:       191.153 [ms] (mean)
Time per request:       0.096 [ms] (mean, across all concurrent requests)
Transfer rate:          2544.18 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0   15 101.8      0    1058
Processing:    17  164  86.1    138     509
Waiting:        1  164  86.1    138     509
Total:         17  179 140.8    141    1504

Percentage of the requests served within a certain time (ms)
  50%    141
  66%    182
  75%    214
  80%    249
  90%    304
  95%    370
  98%    423
  99%    508
 100%   1504 (longest request)

```