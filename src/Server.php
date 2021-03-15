<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/2/26
 */

namespace Refink;

use Refink\Cluster\Gateway;
use Refink\Cluster\Protocol;
use Refink\Database\Config\MySQLConfig;
use Refink\Database\Config\RedisConfig;
use Refink\Database\Pool\MySQLPool;
use Refink\Database\Pool\RedisPool;
use Refink\Exception\ApiException;
use Refink\Exception\ErrorHandler;
use Refink\Exception\MiddlewareException;
use Refink\Http\Controller;
use Refink\Http\Route;
use Refink\Job\JobChannel;
use Refink\Job\QueueInterface;
use Refink\Log\Logger;
use Refink\WebSocket\Dispatcher;
use Swoole\Atomic;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Process;
use Swoole\Server\Task;
use Swoole\Table;

\co::set(['hook_flags' => SWOOLE_HOOK_ALL]);

class Server
{
    use ErrorHandler;

    /**
     * the server support http protocol
     * @var integer
     */
    const SERVER_TYPE_HTTP = 1;

    /**
     * the server support websocket protocol
     * @var integer
     */
    const SERVER_TYPE_WEBSOCKET = 2;

    /**
     * http server default content type
     * @var string
     */
    const HTTP_CONTENT_TYPE_JSON = 'application/json';

    /**
     * http content type "text/plain"
     * @var string
     */
    const HTTP_CONTENT_TYPE_TEXT = 'text/plain';

    const RELOAD_MIN_ATOMIC = 1000000;

    /**
     * the http server response content-type
     * @var string
     */
    private $httpContentType = self::HTTP_CONTENT_TYPE_JSON;

    /**
     * @var callable
     */
    private $mysqlPoolCreateFunc;

    /**
     * @var callable
     */
    private $redisPoolCreateFunc;

    /**
     * if set then swoole task worker will loop for pop job
     * from the queue driver to process.
     *
     * @var QueueInterface
     */
    private $queueDriver;

    /**
     * how many task worker used for queue consume.
     * this value must <= swoole.settings.task_worker_num
     * @var integer
     */
    private $queueConsumerNum;

    /**
     * control how many jobs can concurrent running
     * @var int
     */
    private $jobConcurrentNum = 1024;

    /**
     * control if the job is sequential running
     * @var boolean
     */
    private $jobSequential;

    /**
     * Application name. the terminal process title prefix will use it.
     * @var string
     */
    private $appName = 'refink';

    /**
     * the application's all log files save path
     * @var string
     */
    private $appLogPath = '/var/log';

    /**
     * @var callable
     */
    private $appLogHandler;

    /**
     * the address listen by the server socket.
     * @var string
     */
    private $listen;

    /**
     * the port for server socket bind to.
     * @var integer
     */
    private $port;

    /**
     * the swoole server object
     */
    private $swooleServer;

    /**
     * @var array The command line argument
     */
    private $argv;

    /**
     * @var array the swoole server config
     */
    private $settings;

    /**
     * @var integer 1-http, 2-websocket, 3-http and websocket
     */
    private $serverType;

    /**
     * the application root path, not contains "/app"
     * @var string
     */
    private $appRoot;

    /**
     * when websocket onMessage, use this function to decode it
     * @var callable
     */
    private $webSocketMsgDecoder;

    /**
     * eg: if you use json msg, {"event": "talk","to_uid": 1222, "content": "hi!"},
     * now "event" is the route key
     * @var string
     */
    private $webSocketMsgRouteKey = "event";

    /**
     * @var callable
     */
    private $webSocketOnOpenHandler;

    /**
     * @var callable
     */
    private $webSocketOnCloseHandler;

    /**
     * the server's lan ip address in the cluster
     * @var string
     */
    private $clusterLanIP;

    /**
     * this lan port for the server communication each other in the cluster
     * @var integer
     */
    private $clusterLanPort;

    /**
     * use swoole table to store queue consumer worker pid
     * @var Table
     */
    private $queueConsumerPidMap;

    /**
     * Server constructor
     * @param string $listen
     * @param int $port
     * @param int $serverType default support http and websocket
     */
    public function __construct($listen = "0.0.0.0", $port = 9501, $serverType = self::SERVER_TYPE_HTTP | self::SERVER_TYPE_WEBSOCKET)
    {
        global $argv;
        $this->argv = $argv;
        if (!isset($this->argv[1])) {
            exit("Usage: {$this->argv[0]} [start|stop|reload|restart]\n");
        }
        if (in_array('-d', $this->argv)) {
            $this->settings['daemonize'] = true;
        }
        $this->appRoot = dirname(__DIR__);
        $pidFileNamePrefix = md5($this->appRoot);
        $this->settings['pid_file'] = "/var/run/{$pidFileNamePrefix}.{$this->appName}.pid";
        $this->listen = $listen;
        $this->port = $port;
        $this->serverType = $serverType;
        $lastMasterPid = is_file($this->settings['pid_file']) ? (int)trim(file_get_contents($this->settings['pid_file'])) : 0;

        switch ($this->argv[1]) {
            case "start":
                //check server is already running
                if ($lastMasterPid) {
                    if (Process::kill($lastMasterPid, 0)) {
                        exit(Terminal::getColoredText("the server is running!", Terminal::RED) . PHP_EOL);
                    }
                    //the last master process's pid is old and the master process is not exists.
                    unlink($this->settings['pid_file']);
                }
                break;
            case "stop":
                if (!$lastMasterPid) {
                    exit();
                }
                Process::kill($lastMasterPid, SIGRTMIN + 1);
                exit();
            case "reload":
                if (!$lastMasterPid) {
                    exit();
                }
                Process::kill($lastMasterPid, SIGRTMIN + 2);
                exit();
            case "restart":
                if ($lastMasterPid) {
                    Process::kill($lastMasterPid, SIGRTMIN + 1);
                    //check if the master process is exit
                    while (Process::kill($lastMasterPid, 0)) {
                        usleep(20000);
                    }
                }
                break;
            default:
                exit("Usage: {$this->argv[0]} [start|stop|reload|restart]\n");
        }

        if ($serverType == self::SERVER_TYPE_HTTP) {
            //only support http protocol
            $this->swooleServer = new \Swoole\Http\Server($this->listen, $this->port);
        } else {
            //support http or websocket, swoole web socket server also support http protocol
            $this->swooleServer = new \Swoole\WebSocket\Server($this->listen, $this->port);
            //set the websocket protocol event callbacks
            $this->swooleServer->on('open', function (\Swoole\WebSocket\Server $server, Request $request) {
                if (is_callable($this->webSocketOnOpenHandler)) {
                    call_user_func($this->webSocketOnOpenHandler, $server, $request);
                }
            });

            $this->swooleServer->on('message', function (\Swoole\WebSocket\Server $server, $frame) {
                $this->setErrorHandler();
                try {
                    $routeKey = empty($this->webSocketMsgRouteKey) ? 'event' : $this->webSocketMsgRouteKey;
                    $data = is_callable($this->webSocketMsgDecoder) ? call_user_func($this->webSocketMsgDecoder, $frame->data) : json_decode($frame->data, true);
                    if (!isset($data[$routeKey])) {
                        return $server->push($frame->fd, WebSocket\Response::error("route key not found!"));
                    }

                    $func = Dispatcher::getRoutes($data[$routeKey]);
                    if (empty($func)) {
                        return $server->push($frame->fd, WebSocket\Response::error("dispatcher not found!"));
                    }

                    if (is_array($func) && class_exists($func[0])) {
                        $func = [new $func[0], $func[1]];
                    }
                    $response = call_user_func($func, $data);
                    $server->push($frame->fd, $response);
                } catch (\Throwable $e) {
                    $result = WebSocket\Response::error($e->getMessage() . ', trace: ' . json_encode($e->getTrace(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                    Logger::getInstance()->error($result);
                    return $server->push($frame->fd, $result);
                }

            });

            $this->swooleServer->on('close', function ($server, $fd) {
                if (is_callable($this->webSocketOnCloseHandler)) {
                    call_user_func($this->webSocketOnCloseHandler, $server, $fd);
                }
            });
        }

        //set http protocol on request event callback
        if ($serverType & self::SERVER_TYPE_HTTP) {
            $this->swooleServer->on('request', function (Request $request, Response $response) {
                $response->setHeader('Content-Type', $this->httpContentType);
                $response->setHeader('Server', 'Refink');
                if (empty($route = Route::getRouteInfo($request->server['request_method'], $request->server['request_uri']))) {
                    $response->end("{$request->server['request_method']} to the request uri \"{$request->server['request_uri']}\" fail, route not found!");
                    return;
                }
                $params = array();
                if (!empty($request->get)) {
                    $params = array_merge($params, $request->get);
                }
                if (!empty($request->post)) {
                    $params = array_merge($params, $request->post);
                }

                try {
                    //processing http middleware
                    $result = '';
                    if (isset($route['middleware'])) {
                        foreach ($route['middleware'] as $alias) {
                            $middlewares = Route::getMiddlewareByAlias($alias);
                            foreach ($middlewares as $mid) {
                                (new $mid)->handle($params);
                            }
                        }
                    }

                    //route dispatch
                    if (empty($result)) {
                        if (is_array($route['func'])) {
                            $class = $route['func'][0];
                            $action = $route['func'][1];
                            /** @var Controller $class */
                            $class = new $class;
                            $result = $class->$action($params);
                        } else {
                            $result = call_user_func($route['func'], $params);
                        }
                    }
                } catch (MiddlewareException $e) {
                    $result = $e->getMessage();
                    Logger::getInstance()->error($result);
                } catch (ApiException $e) {
                    $result = $e->getMessage();
                    Logger::getInstance()->error($result);
                } catch (\Throwable $e) { //use \Throwable instead of \Exception, because PHP Fatal error can not be try catch by \Exception
                    $result = Http\Response::error($e->getMessage() . ', trace: ' . json_encode($e->getTrace(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                    Logger::getInstance()->error($result);
                } finally {
                    $response->end($result);
                }
            });
        }

        $this->swooleServer->on('start', function ($server) {
            cli_set_process_title("$this->appName: master");
            Terminal::echoTableLine();
            $padLen = 18;
            if ($this->serverType & self::SERVER_TYPE_HTTP) {
                echo str_pad("http server", $padLen) . '|  ' . Terminal::getColoredText("http://{$this->listen}:9501", Terminal::BOLD_BLUE) . PHP_EOL;
            }
            if ($this->serverType & self::SERVER_TYPE_WEBSOCKET) {
                echo str_pad("websocket server", $padLen) . '|  ' . Terminal::getColoredText("ws://{$this->listen}:9501", Terminal::BOLD_BLUE) . PHP_EOL;
            }
            echo str_pad("app log path", $padLen) . '|  ' . (empty($this->appLogPath) ? Terminal::getColoredText("not config!", Terminal::RED) : $this->appLogPath) . PHP_EOL;
            echo str_pad("swoole version", $padLen) . '|  ' . SWOOLE_VERSION . PHP_EOL;
            echo str_pad("php version", $padLen) . '|  ' . PHP_VERSION . PHP_EOL;
            $routes = "{$this->appRoot}/app/routes.php";
            if (!is_file($routes) && $this->serverType & self::SERVER_TYPE_HTTP) {
                echo str_pad("warning", $padLen) . '|  ' . Terminal::getColoredText($routes, Terminal::RED) . " not exists!" . PHP_EOL;
            }
            Terminal::echoTableLine();
            echo str_pad("press " . Terminal::getColoredText("CTRL + C", Terminal::BOLD_MAGENTA) . " to stop.", 20) . PHP_EOL;
        });

        $this->swooleServer->on('task', function ($server, Task $task) {
            $task->data->handle($server);;
        });

        $this->swooleServer->on('managerStart', function ($server) {
            cli_set_process_title("$this->appName: manager");
        });

        $this->swooleServer->on('workerStart', function ($server, $workerId) {
            //set php error handler
            $this->setErrorHandler();
            //load config
            $this->loadConfig();
            //init log handler
            Logger::getInstance($this->appLogPath, $this->appLogHandler, $this->appName);

            $name = "worker";
            $inTaskWorker = false;
            $taskWorkerId = $workerId - $this->swooleServer->setting['worker_num'];
            if ($taskWorkerId >= 0) {
                $name = "task worker";
                $inTaskWorker = true;
            } else {
                Controller::bindSwooleServer($this->swooleServer);
            }
            cli_set_process_title("$this->appName: {$name}");
            if (is_callable($this->redisPoolCreateFunc)) {
                call_user_func($this->redisPoolCreateFunc, new RedisConfig(REDIS['host'], REDIS['port'], REDIS['passwd'], REDIS['db']));
            }
            if (is_callable($this->mysqlPoolCreateFunc)) {
                call_user_func($this->mysqlPoolCreateFunc, new MySQLConfig(MYSQL['host'], MYSQL['port'], MYSQL['db_name'], MYSQL['username'], MYSQL['passwd'], MYSQL['options']));
            }

            if ($workerId == 0) {
                Gateway::register($this->clusterLanIP, $this->clusterLanPort);
            }

            if ($inTaskWorker && !is_null($this->queueDriver)) {
                $jobWorkerId = $taskWorkerId;
                if ($jobWorkerId < $this->queueConsumerNum) {
                    $context = [];
                    cli_set_process_title("$this->appName: task worker (queue consumer)");
                    //save queue consumer worker pid
                    $pid = (string)posix_getpid();
                    $this->queueConsumerPidMap->set($pid, array('pid' => (int)$pid));
                    $running = true;
                    while ($running) {
                        $running = $this->queueConsumerPidMap->exist($pid);
                        $job = $this->queueDriver->dequeue($jobWorkerId);
                        if (empty($job)) {
                            \co::sleep(0.2);
                            continue;
                        }

                        //main coroutine dispatch job to user queue, this step like lock
                        $this->jobSequential && JobChannel::getInstance($job->getGroupId())->push($job);

                        //control the peak coroutine number
                        while (count($context) > $this->jobConcurrentNum) {
                            \co::sleep(0.02);
                        }
                        go(function () use ($job, &$context) {
                            $cid = \co::getCid();
                            $context[$cid] = 1;
                            defer(function () use ($cid, $job, &$context) {
                                //this step like unlock
                                $this->jobSequential && JobChannel::getInstance($job->getGroupId())->pop();
                                unset($context[$cid]);
                            });
                            $job->handle();
                        });
                    }
                }
                //for onTask
            }

        });

        $this->swooleServer->on('workerStop', function ($server, int $workerId) {

        });

        $this->swooleServer->on('workerExit', function ($server, int $workerId) {
            if ($workerId == 0) {
                go(function () {
                    Gateway::unregister($this->clusterLanIP, $this->clusterLanPort);
                });
            }
            TimerManager::clearAll();
        });
    }


    private function loadConfig()
    {
        $env = get_cfg_var("APP_ENV");
        empty($env) && $env = 'dev';
        $files = [
            "{$this->appRoot}/config_{$env}.php",
            "{$this->appRoot}/app/routes.php"
        ];
        foreach ($files as $file) {
            if (is_file($file)) {
                require $file;
            }
        }

        Config::getInstance([
            'swoole' => $this->swooleServer->setting,
            'refink' => [
                'queue_consumer_num' => $this->queueConsumerNum,
                'lan_ip'             => (string)$this->clusterLanIP,
                'lan_port'           => (int)$this->clusterLanPort
            ]
        ]);
    }


    private function checkEnv(&$configFile)
    {
        $env = get_cfg_var("APP_ENV");
        empty($env) && $env = 'dev';
        $configFile = "{$this->appRoot}/config_{$env}.php";
        return is_file($configFile);
    }


    private function showLogo()
    {
        $logo = <<<LOGO
 ____       __ _       _    
|  _ \ ___ / _(_)_ __ | | __
| |_) / _ \ |_| | '_ \| |/ /
|  _ <  __/  _| | | | |   < 
|_| \_\___|_| |_|_| |_|_|\_\
                            
LOGO;
        $logo .= PHP_EOL;

        echo Terminal::getColoredText($logo, Terminal::GREEN);

    }

    /**
     * [optional] the swoole server config settings
     * if you want change config, you need to restart server
     * reload will not take effect
     * @param array $settings
     * @return $this
     */
    public function setSwooleConf(array $settings)
    {
        foreach ($settings as $k => $v) {
            $this->settings[$k] = $v;
        }
        return $this;
    }


    /**
     * [optional] create mysql connection pool
     * @param int $size the connection number of the pool
     * @return $this
     */
    public function initMySQLPool(int $size)
    {
        $this->mysqlPoolCreateFunc = function (MySQLConfig $config) use ($size) {
            MySQLPool::initPool($size, $config);
        };
        return $this;
    }

    /**
     * [optional] create redis connection pool
     * @param int $size the connection number of the pool
     * @return $this
     */
    public function initRedisPool(int $size)
    {
        $this->redisPoolCreateFunc = function (RedisConfig $config) use ($size) {
            RedisPool::initPool($size, $config);
        };

        return $this;
    }

    /**
     * [optional] set the http content type
     * @param string $contentType
     * @return $this
     */
    public function setHttpContentType($contentType = self::HTTP_CONTENT_TYPE_JSON)
    {
        $this->httpContentType = $contentType;
        return $this;
    }

    /**
     * [optional] set app log handler
     * @param $logPath
     * @param callable $handler on framework log finished, then exec the handler
     * @return $this
     */
    public function setAppLogHandler($logPath, callable $handler)
    {
        if (!empty($logPath)) {
            $this->appLogPath = $logPath;
            if (!is_dir($this->appLogPath)) {
                mkdir($this->appLogPath, 0777, true);
            }
            if (!is_writeable($this->appLogPath)) {
                exit("$this->appLogPath is not writeable\n");
            }
            $this->settings['log_file'] = "{$this->appLogPath}/{$this->appName}.swoole.log";
        }
        if (is_callable($handler)) {
            $this->appLogHandler = $handler;
        }
        return $this;
    }

    /**
     * [optional] set your application name
     * @param $name
     */
    public function setAppName($name)
    {
        if (!empty($name)) {
            $this->appName = $name;
        }
    }

    /**
     * [optional] set the websocket message decoder, default
     * use "json_decode" function
     * @param callable $func
     * @param string $msgRouteKey dispatch msg to websocket handler by the msgRouteKey
     */
    public function setWebSocketMsgDecoder($func, $msgRouteKey)
    {
        $this->webSocketMsgDecoder = $func;
        $this->webSocketMsgRouteKey = $msgRouteKey;
    }

    /**
     * [optional] set the swoole task worker receive job callback func, if set this
     * the task worker will loop for this handler
     * @param QueueInterface $driver if you want to
     * @param int $queueConsumerNum how many task workers used for queue consume
     * @param int $jobConcurrentNum how many job can concurrent running
     * @param bool $jobSequential to control the job processing in Sequential
     * @return $this
     */
    public function setQueueDriver(QueueInterface $driver, $queueConsumerNum = 4, $jobConcurrentNum = 1024, $jobSequential = true)
    {
        $this->queueDriver = $driver;
        $this->queueConsumerNum = $queueConsumerNum;
        $this->jobConcurrentNum = $jobConcurrentNum;
        $this->jobSequential = $jobSequential;
        return $this;
    }

    /**
     * [optional] set the http response body data format
     * @param callable $successPacker
     * @param callable $errorPacker
     */
    public function setHttpResponsePacker($successPacker, $errorPacker)
    {
        Http\Response::setPacker($successPacker, $errorPacker);
    }

    /**
     * [optional] set the websocket response msg format
     * @param callable $successPacker the function to pack success response
     * @param callable $errorPacker the function to pack error response
     */
    public function setWebSocketPacker($successPacker, $errorPacker)
    {
        WebSocket\Response::setPacker($successPacker, $errorPacker);
    }

    /**
     * [optional] set the websocket "on open" event callback
     * @param callable $func
     */
    public function setWebSocketOnOpen($func)
    {
        $this->webSocketOnOpenHandler = $func;
    }

    /**
     * [optional] set the websocket "on close" event callback
     * @param callable $func
     */
    public function setWebSocketOnClose($func)
    {
        $this->webSocketOnCloseHandler = $func;
    }


    public function enableCluster($eth = 'eth0', $port = 9600)
    {
        $ips = swoole_get_local_ip();
        if (empty($ips[$eth])) {
            exit(Terminal::getColoredText("can not get lan ip from {$eth}!\n", Terminal::RED));
        }
        $this->clusterLanIP = $ips[$eth];
        $this->clusterLanPort = $port;

        $clusterPort = $this->swooleServer->addlistener($this->clusterLanIP, $port, SWOOLE_SOCK_TCP);
        $clusterPort->set([
            'open_length_check'     => true,
            'package_length_type'   => 'N',
            'package_length_offset' => 0,
            'package_body_offset'   => 4,
            'package_max_length'    => 1024 * 1024
        ]);

        $clusterPort->on('receive', function ($serv, $fd, $reactor_id, $data) {
            $data = Protocol::decode($data);
            $data->handle($serv);
        });
    }

    private function clearQueueConsumerWorkerPids()
    {
        $pidSet = [];
        foreach ($this->queueConsumerPidMap as $row) {
            $pidSet[] = $row['pid'];
        }
        foreach ($pidSet as $pid) {
            $this->queueConsumerPidMap->del($pid);
        }
    }

    public function run()
    {
        if (!$this->checkEnv($configFile)) {
            exit("config file: " . Terminal::getColoredText("$configFile", Terminal::RED) . " not found, please config it first!" . PHP_EOL);
        }

        if (!empty($this->settings['task_worker_num']) && $this->queueConsumerNum > $this->settings['task_worker_num']) {
            exit(Terminal::getColoredText("the queue consumer number must <= swoole.settings.task_worker_num\n", Terminal::RED) . "call Refink\Server::setQueueDriver to resize the queue consumer number.\n");
        }

        $this->swooleServer->set($this->settings);
        //stop
        Process::signal(SIGRTMIN + 1, function () {
            $this->clearQueueConsumerWorkerPids();
            $this->swooleServer->shutdown();
        });
        //reload
        Process::signal(SIGRTMIN + 2, function () {
            $this->clearQueueConsumerWorkerPids();
            $this->swooleServer->reload();
        });

        //save master process pid
        file_put_contents($this->settings['pid_file'], posix_getpid());
        //display logo
        empty($this->settings['daemonize']) && $this->showLogo();

        //use swoole table to control looping queue consumer task worker reload or stop
        $this->queueConsumerPidMap = new Table($this->queueConsumerNum);
        $this->queueConsumerPidMap->column('pid', Table::TYPE_INT, 4);
        $this->queueConsumerPidMap->create();

        $this->swooleServer->start();
    }


}


