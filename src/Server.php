<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/2/26
 */

namespace Refink;

use Refink\Database\Config\MySQLConfig;
use Refink\Database\Config\RedisConfig;
use Refink\Database\Pool\MySQLPool;
use Refink\Database\Pool\RedisPool;
use Refink\Exception\ApiException;
use Refink\Exception\MiddlewareException;
use Refink\Http\Controller;
use Refink\Http\HttpController;
use Refink\Http\Route;
use Refink\Log\Logger;
use Swoole\Http\Request;
use Swoole\Http\Response;

\co::set(['hook_flags' => SWOOLE_HOOK_ALL]);

class Server
{
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

    const WEBSOCKET_ON_MESSAGE = 'message';
    const WEBSOCKET_ON_OPEN = 'open';
    const WEBSOCKET_ON_CLOSE = 'close';

    /**
     * @var string http server default content type
     */
    const HTTP_CONTENT_TYPE_JSON = 'application/json';

    const HTTP_CONTENT_TYPE_TEXT = 'text/plain';

    /**
     * @var string
     */
    private $httpContentType = self::HTTP_CONTENT_TYPE_JSON;

    private $mysqlPoolCreateFunc;
    private $redisPoolCreateFunc;

    private $appName = 'Refink';

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
     * @var string set the process title
     */
    private $processName = "refink";

    /**
     * @var array the swoole server config
     */
    private $settings;

    /**
     * @var integer 1-http, 2-websocket, 3-http and websocket
     */
    private $serverType;

    /**
     * Server constructor.
     * @param string $listen
     * @param int $port
     * @param int $serverType default support http and websocket
     */
    public function __construct($listen = "0.0.0.0", $port = 9501, $serverType = self::SERVER_TYPE_HTTP | self::SERVER_TYPE_WEBSOCKET)
    {
        global $argv;
        $this->argv = $argv;
        if (!isset($this->argv[1])) {
            exit("Usage: php {$this->argv[0]} [start|stop|reload|restart]\n");
        }
        if (in_array('-d', $this->argv)) {
            $this->settings['daemonize'] = true;
        }
        $this->settings['pid_file'] = __DIR__ . "/{$this->processName}.server.pid";
        $this->settings['log_file'] = __DIR__ . "/swoole.log";
        $this->listen = $listen;
        $this->port = $port;
        $this->serverType = $serverType;
        $lastMasterPid = is_file($this->settings['pid_file']) ? (int)trim(file_get_contents($this->settings['pid_file'])) : 0;
        switch ($this->argv[1]) {
            case "start":
                //check server is already running
                if ($lastMasterPid) {
                    if (posix_kill($lastMasterPid, 0)) {
                        exit(Terminal::getColoredText("the server is running!", Terminal::RED) . PHP_EOL);
                    }
                    //the last master process's pid is old and the master process is not exists.
                    unlink($this->settings['pid_file']);
                }
                break;
            case "stop":
                if (!$lastMasterPid) {
                    return;
                }
                posix_kill($lastMasterPid, SIGTERM);
                exit();
            case "reload":
                if (!$lastMasterPid) {
                    return;
                }
                posix_kill($lastMasterPid, SIGUSR1);
                exit();
            case "restart":
                if ($lastMasterPid) {
                    posix_kill($lastMasterPid, SIGTERM);
                    //check if the master process is exit
                    while (posix_kill($lastMasterPid, 0)) {
                        usleep(20000);
                    }
                }
                break;
            default:
                exit("Usage: php {$this->argv[0]} [start|stop|reload|restart]\n");
        }

        if ($serverType == self::SERVER_TYPE_HTTP) {
            //only support http protocol
            $this->swooleServer = new \Swoole\Http\Server($this->listen, $this->port);
        } else {
            //support http or websocket, swoole web socket server also support http protocol
            $this->swooleServer = new \Swoole\WebSocket\Server($this->listen, $this->port);
            //set the websocket protocol event callbacks
            $this->swooleServer->on('open', function (\Swoole\WebSocket\Server $server, $request) {
                echo "server: handshake success with fd{$request->fd}\n";
            });

            $this->swooleServer->on('message', function (\Swoole\WebSocket\Server $server, $frame) {
                echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
                $server->push($frame->fd, "this is server");
            });

            $this->swooleServer->on('close', function ($server, $fd) {
                echo "client {$fd} closed\n";
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
                    foreach ($route['middleware'] as $alias) {
                        $middlewares = Route::getMiddlewareByAlias($alias);
                        foreach ($middlewares as $mid) {
                            (new $mid)->handle($params);
                            //call_user_func([(new $mid), 'handle'], $params);
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
                    $result = $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
                    var_dump($e->getTrace());
                    $result = HttpController::getErrorResponse($result);
                    Logger::getInstance()->error($result);
                } finally {
                    $response->end($result);
                }
            });
        }

        $this->swooleServer->on('start', function ($server) {
            cli_set_process_title("$this->processName:Master");
            Terminal::echoTableLine();
            if ($this->serverType & self::SERVER_TYPE_HTTP) {
                echo str_pad("http server", 18) . '|  ' . Terminal::getColoredText("http://192.168.66.210:9501", Terminal::BOLD_BLUE) . PHP_EOL;
            }
            if ($this->serverType & self::SERVER_TYPE_WEBSOCKET) {
                echo str_pad("websocket server", 18) . '|  ' . Terminal::getColoredText("ws://192.168.66.210:9501", Terminal::BOLD_BLUE) . PHP_EOL;
            }
            echo str_pad("app log path", 18) . '|  ' . (empty($this->appLogPath) ? Terminal::getColoredText("not config!", Terminal::RED) : $this->appLogPath) . PHP_EOL;
            echo str_pad("swoole version", 18) . '|  ' . SWOOLE_VERSION . PHP_EOL;
            echo str_pad("php version", 18) . '|  ' . PHP_VERSION . PHP_EOL;
            Terminal::echoTableLine();
            echo str_pad("press " . Terminal::getColoredText("CTRL + C", Terminal::BOLD_MAGENTA) . " to stop.", 20) . PHP_EOL;

        });

        $this->swooleServer->on('managerStart', function ($server) {
            cli_set_process_title("$this->processName:Manager");
        });
        $this->swooleServer->on('workerStart', function ($server, $workerId) {
            cli_set_process_title("$this->processName:Worker");
            if (is_callable($this->redisPoolCreateFunc)) {
                call_user_func($this->redisPoolCreateFunc);
            }
            if (is_callable($this->mysqlPoolCreateFunc)) {
                call_user_func($this->mysqlPoolCreateFunc);
            }

            //set php error handler
            set_error_handler(function ($errno, $errStr, $errFile, $errLine) {
                $errType = '';
                switch ($errno) {
                    case E_ERROR:
                        $errType = 'Php Fatal Error: ';
                        break;
                    case E_WARNING:
                        $errType = 'Php Warning: ';
                        break;
                    case E_PARSE:
                        $errType = 'Php Parse Error: ';
                        break;
                    case E_NOTICE:
                        $errType = 'Php Notice: ';
                        break;
                    case E_CORE_ERROR:
                        $errType = 'Php Core Error: ';
                        break;
                    case E_CORE_WARNING:
                        $errType = 'Php Core Warning: ';
                        break;
                    case E_COMPILE_ERROR:
                        $errType = 'Php Compile error: ';
                        break;
                    case E_COMPILE_WARNING:
                        $errType = 'php Compile Warning: ';
                        break;
                    case E_USER_ERROR:
                        $errType = 'Php User Error: ';
                        break;
                    case E_USER_WARNING:
                        $errType = 'Php User Warning: ';
                        break;
                    case E_USER_NOTICE:
                        $errType = 'Php User Notice: ';
                        break;
                    default:
                        $errType = "Unknown Error: ";
                        break;
                }
                //todo need backstrace
                throw new \Exception("$errType $errStr");
            });


        });

        $this->swooleServer->set($this->settings);

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
     * set the server's process name
     * @param $name
     * @return $this
     */
    public function setProcessName($name)
    {
        $this->processName = $name;
        return $this;
    }

    /**
     * [optional] the swoole server config settings
     * @param array $swooleServerConfig
     * @return $this
     */
    public function set(array $swooleServerConfig)
    {
        foreach ($swooleServerConfig as $k => $v) {
            $this->settings[$k] = $v;
        }
        return $this;
    }


    /**
     * [optional] create mysql connection pool
     * @param int $size the connection number of the pool
     * @param MySQLConfig $config
     * @return $this
     */
    public function initMySQLPool(int $size, MySQLConfig $config)
    {
        $this->mysqlPoolCreateFunc = function () use ($size, $config) {
            $pool = new MySQLPool();
            $pool->initPool($size, $config);
        };
        return $this;
    }

    /**
     * [optional] create redis connection pool
     * @param int $size the connection number of the pool
     * @param RedisConfig $config
     * @return $this
     */
    public function initRedisPool(int $size, RedisConfig $config)
    {
        $this->redisPoolCreateFunc = function () use ($size, $config) {
            $pool = new RedisPool();
            $pool->initPool($size, $config);
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
     * @param callable $func
     * @return $this
     */
    public function setAppLogHandler($logPath, callable $func)
    {
        if (!empty($logPath)) {
            $this->appLogPath = $logPath;
        }
        if (is_callable($func)) {
            $this->appLogHandler = $func;
        }
        return $this;
    }

    public function setAppName($name)
    {
        if (!empty($name)) {
            $this->appName = $name;
        }
    }

    public function setWebSocketEvent($event, callable $func)
    {
        $this->swooleServer->on($event, $func);
    }

    public function run()
    {
        //reload
        pcntl_signal(SIGUSR1, function () {
            $this->swooleServer->reload();
        });
        //save master process pid
        file_put_contents($this->settings['pid_file'], posix_getpid());
        //display logo
        $this->showLogo();
        //init log handler
        Logger::getInstance($this->appLogPath, $this->appLogHandler, $this->appName);
        $this->swooleServer->start();
    }
}


