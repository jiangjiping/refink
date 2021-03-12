<?php
/**
 * Gateway use to control cluster's all node server
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/11
 */

namespace Refink\Cluster;


use Refink\Database\Pool\RedisPool;
use Refink\Log\Logger;
use Refink\TimerManager;
use Swoole\Client;
use Swoole\Timer;

class Gateway
{
    const RDS_KEY_CLUSTER = 'refink:cluster_info:s';

    /**
     * how many seconds the cluster boot will take
     * @var integer
     */
    const CLUSTER_MAX_BOOT_TIME = 60;

    /**
     * save tcp clients that connected to all other nodes
     * @var array
     */
    private static $nodeTcpClients = [];

    /**
     * local cache the node info
     * @var array
     */
    private static $cacheNodes = [];

    private static $lastRegistryTime;

    /**
     * register node info for cluster
     * @param $lanIP
     * @param $lanPort
     */
    public static function register($lanIP, $lanPort)
    {
        RedisPool::getConn()->sAdd(self::RDS_KEY_CLUSTER, "$lanIP:$lanPort");
        self::$lastRegistryTime = time();

        //timer refresh the node info
        $timerId = Timer::tick(60 * 1000, function () {
            $nodes = self::getAllNodesFromRegistry();
            if (!empty($nodes)) {
                self::$cacheNodes = $nodes;
            }
            //todo check all tcp clients is alive
        });
        TimerManager::add($timerId);
    }

    /**
     * forward message to some target
     * @param $message
     * @param array $targetNodes eg: array("192.168.2.100:9600","192.168.2.101:9600"), if empty then forward to all other nodes in the cluster
     * @param
     */
    public static function forward($message, array $targetNodes = [])
    {
        empty($targetNodes) && $targetNodes = self::getAllNodes();
        $message = Protocol::encode($message);
        foreach ($targetNodes as $node) {
            list($ip, $port) = explode(':', $node, 2);
            if (!isset(self::$nodeTcpClients[$node])) {
                //default is non blocking socket
                $cli = new Client(SWOOLE_SOCK_TCP);
                if (!$cli->connect($ip, $port, 3)) {
                    Logger::getInstance()->error("Gateway::forward() connect to $ip:$port fail!");
                    continue;
                }
                self::$nodeTcpClients[$node] = $cli;
                self::$nodeTcpClients[$node]->send($message);
            }
        }

    }

    /**
     * get all the node info in the cluster
     */
    public static function getAllNodes()
    {
        if (time() - self::$lastRegistryTime < self::CLUSTER_MAX_BOOT_TIME) {
            $nodes = self::getAllNodesFromRegistry();
            if (!empty($nodes)) {
                self::$cacheNodes = $nodes;
            }
        }
        return self::$cacheNodes;
    }

    /**
     * get real-time nodes
     * @return array
     */
    private static function getAllNodesFromRegistry()
    {
        return RedisPool::getConn()->sMembers(self::RDS_KEY_CLUSTER);
    }

    /**
     * remove node
     * @param $lanIP
     * @param $lanPort
     */
    public static function unregister($lanIP, $lanPort)
    {
        /**
         * because unregister invoked on worker stop, that time is not in coroutine context,
         * so we need to use sync redis io
         */
        $redis = new \Redis();
        $redis->connect(REDIS['host'], REDIS['port']);
        if (!empty(REDIS['passwd'])) {
            $redis->auth(REDIS['passwd']);
        }
        if (isset(REDIS['db']) && REDIS['db']) {
            $redis->select(REDIS['db']);
        }
        $redis->sRem(self::RDS_KEY_CLUSTER, "$lanIP:$lanPort");
    }
}