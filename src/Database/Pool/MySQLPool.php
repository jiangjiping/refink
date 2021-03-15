<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/2/27
 */

namespace Refink\Database\Pool;


use Refink\Database\Config\AbstractConfig;

class MySQLPool extends AbstractPool
{
    use Common;

    private static $pools;

    protected $config;

    public static function initPool($size, AbstractConfig $config, $name = "default")
    {
        if (!isset(self::$pools[$name])) {
            self::$pools[$name] = new static($size, $config, $name);
        }
    }

    private function buildConfig(AbstractConfig $config)
    {
        $this->config = [
            'dsn'      => sprintf("mysql:host=%s;dbname=%s;port=%d", $config->getHost(), $config->getDbName(), $config->getPort()),
            'username' => $config->getUserName(),
            'passwd'   => $config->getPassword(),
            'options'  => $config->getOptions()
        ];
    }

    public function tryConnect(int $nowTime)
    {
        if ($this->connNum >= $this->size) {
            return;
        }

        try {
            //coroutine context will switch, so need incr connNum first.
            $this->connNum++;
            $pdo = new \PDO($this->config['dsn'], $this->config['username'], $this->config['passwd'], $this->config['options']);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $conn = new Connection($pdo, $nowTime, function (\PDO $pdo) {
                $pdo->query("SELECT 1")->fetchColumn();
            });
            $this->pool->push($conn);
        } catch (\Throwable $e) {
            $this->connNum--;
            throw $e;
        }
    }


    /**
     * @param string $name
     * @return \PDO
     */
    public static function getConn($name = "default")
    {
        return self::getConnection($name);
    }
}