### Refink是基于swoole的一款开箱即用的后端应用框架

 - 非常容易上手，无任何框架层的重度封装
 - 支持基于http的restful api接口，自带类似laravel的静态路由、中间件
 - 支持基于websocket MVC结构，直接写业务代码即可，不用自己在处理websocket事件
 - 支持数据库连接池, 当前进支持redis和mysql
 - 目前只能在协程环境中运行
 - 高性能：和原生swoole非常接近，因为框架代码极其精简，带来的性能损耗忽略不计
 
 
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
[root@pc-vagrant ~]# ab -n 20000 -c 2000 -k http://192.168.66.210:9501/v2/user/login
This is ApacheBench, Version 2.3 <$Revision: 1430300 $>
Copyright 1996 Adam Twiss, Zeus Technology Ltd, http://www.zeustech.net/
Licensed to The Apache Software Foundation, http://www.apache.org/

Benchmarking 192.168.66.210 (be patient)
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
Server Hostname:        192.168.66.210
Server Port:            9501

Document Path:          /v2/user/login
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
    
    
### 使用方法
    
 -  清先安装composer
 -  cd到指定目录，然后在shell终端执行命令:

```
composer require refink/refink
   
```

- 创建启动文件app.php

```
use Refink\Server;

require './vendor/autoload.php';

use Refink\Database\Config\MySQLConfig;
use Refink\Database\Config\RedisConfig;

$app = new Server("192.168.66.210", 9501, Server::SERVER_TYPE_HTTP);
$app
    ->initMySQLPool(40, new MySQLConfig("127.0.0.1", 3306, "demo", "username", "password", []))
    ->initRedisPool(64, new RedisConfig("127.0.0.1", 6379, "password"))
    ->run();
```
 
 - 命令行启动server
 
```
 php app.php start

```

 
 ### 如何同时支持http和websocket?
 
```
 $app = new Server("192.168.66.210", 9501, Server::SERVER_TYPE_HTTP | Server::SERVER_TYPE_WEBSOCKET );
```
 