<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/8
 */

namespace Refink;


use Refink\Database\Config\MySQLConfig;
use Refink\Database\Config\RedisConfig;
use Refink\Database\Pool\MySQLPool;
use Refink\Database\Pool\RedisPool;
use Refink\Exception\CommandException;
use Refink\Exception\ErrorHandler;
use Refink\Log\Logger;
use Swoole\Timer;

class Console
{
    use ErrorHandler;

    private $appRoot;
    private $appLogPath;
    private $appLogHandler;
    private $appName;
    private $command;
    private $args;
    private $func;


    /**
     * Console constructor.
     * @param $appLogPath
     * @param callable $appLogHandler
     * @throws \Exception
     */
    public function __construct($appLogPath, $appLogHandler)
    {
        global $argv;
        $this->appRoot = dirname(__DIR__);
        $this->appLogPath = $appLogPath;
        $this->appLogHandler = $appLogHandler;
        if (!is_dir($appLogPath)) {
            mkdir($this->appLogPath, 0777, true);
        }
        if (!is_writeable($this->appLogPath)) {
            exit("$this->appLogPath is not writeable\n");
        }
        $this->setErrorHandler();
        $this->loadConfig();
        if (empty($argv[1])) {
            $help = Command::help();
            echo Terminal::getColoredText("$argv[0]: wrong number of arguments\n", Terminal::RED);
            exit("usage: $argv[0] [command]\n{$help}");
        }
        $this->command = $argv[1];
        $this->args = array_splice($argv, 1);
        try {
            $this->func = Command::getCommandCallback($this->command);
        } catch (CommandException $e) {
            echo Terminal::getColoredText($e->getMessage(), Terminal::RED);
            exit(Command::help());
        }
    }

    private function __clone()
    {
    }

    public function run()
    {
        go(function () {
            try {
                $this->initDBPool();
                //init log handler
                Logger::getInstance($this->appLogPath, $this->appLogHandler, $this->appName);
                if (is_array($this->func) && count($this->func) == 2) {
                    $this->func[0] = new $this->func[0];
                }
                call_user_func($this->func, $this->args);
            } catch (\Throwable $e) {
                echo Terminal::getColoredText($e->getMessage(), Terminal::RED) . PHP_EOL;
                Logger::getInstance()->error($e->getMessage());
            }
            //because db pool has tick timer, so clear all timer to prevent terminal block forever
            Timer::clearAll();
        });


    }

    private function loadConfig()
    {
        //load config
        $env = get_cfg_var("APP_ENV");
        empty($env) && $env = 'dev';
        $files = [
            "{$this->appRoot}/config_{$env}.php",
            "{$this->appRoot}/app/commands.php"
        ];
        foreach ($files as $file) {
            if (is_file($file)) {
                require $file;
            }
        }
    }

    private function initDBPool()
    {
        //redis
        RedisPool::initPool(1, new RedisConfig(REDIS['host'], REDIS['port'], REDIS['passwd'], REDIS['db']));
        //mysql
        MySQLPool::initPool(1, new MySQLConfig(MYSQL['host'], MYSQL['port'], MYSQL['db_name'], MYSQL['username'], MYSQL['passwd'], MYSQL['options']));
    }
}





