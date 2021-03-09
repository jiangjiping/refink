
### Refink适用场景

- app后端
- 游戏后端

### Refink特性

 - 非常容易上手，无任何框架层的重度封装
 - 支持基于http的restful api接口，自带类似laravel的静态路由、中间件
 - 支持异步任务：api接口将耗时任务放队列，后台进程异步消费，代码书写方式类似laravel的job，ide代码智能提示支持良好
   同时支持配置异步任务是并行还是串行(按顺序)执行
 - 支持基于websocket MVC结构，直接写业务代码即可，不用自己在处理websocket事件
 - 支持数据库连接池, 当前进支持redis和mysql
 - 目前只能在协程环境中运行
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



### Refink作者的思考

-  swoole生态目前常用的框架就那几个，但是都是java系风格编码方式，其实对phper来说未必友好，而且封装都太重，
有一个虽然简单，但是很多地方不给你自由扩展的机会，比如日志错误处理居然要自己去处理，框架没自动处理好，而需要
功能扩展的地方却到处是 !instance of 判断限制扩展。
   php7和swoole的出现都是为了给php性能带来提升，作为框架的开发者，不能反其道而行，成为拖慢性能的罪魁祸首。
swoole的性能已经非常之高效了,作者曾经压测对比过:
基于c++的drogon框架、java8 + netty4、swoole4+php7 、golang 1.14自带http server、基于c语言libevent多线程实现的http server
在同样的环境下，QPS性能排序如下:
- 多线程ibevent_http_server > drogon > swoole > golang > java
其中java和golang接近，swoole和drogon接近。既然swoole那么高效，所以框架作者应该尽可能的减少框架层消耗
下面提出几个框架作者不该做的事情：

```
1、 不能为了代码看起来优雅，而使用低效的代码，如laravel中的路由中间件使用的pipleline，使用了递归创建闭包，要知道递归理论上是有栈溢出可能的
而且及其低效，堆栈调用不需要开销吗？在我们学习算法的时候，递归只是教材上用法，实际生产项目都是换成同等的迭代模式
2、 为了强行引入某个设计模式，但是却让ide没了代码智能提示，而且这个设计模式并没有解决实际的问题。要知道phpstorm代码提示相当智能的，但是很多框架作者
为了用java的IOC容器之类的，Di::getInstance('xxx'), 本来可以XXX::get()这样ide代码提示的，换成字符串写发了，开发效率能提升？特别是xxx如果很难记忆
或者很容易手滑打错时。所以引入一个设计模式，一定要看解决了什么问题，但同时带来了什么问题。权衡之后再考虑引入
3、过度的封装，做c++的人虚函数调用开销都紧张的很。
4、配置太繁杂，而且框架上手成本高

```

refink翻译出来是：重新思考， 这也是作者开发该框架的原因，重新思考了上述问题之后，开发了refink框架，开箱即用，简单容易上手，关键点：
保持了swoole的高效！！！
 
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