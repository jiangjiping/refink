<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/1
 */

namespace Refink\Database\Pool;


trait Common
{

    private static $coroutineContext;
    private static $pools;


    private static function getConnection($name = "default")
    {
        $cid = \co::getCid();
        //multi times invoke at the same coroutine context
        if (isset(self::$coroutineContext[$cid])) {
            /** @var Connection $conn */
            $conn = self::$coroutineContext[$cid];
            //check expired for when the process only have on coroutine
            if ($conn->isExpired(time())) {
                self::$pools[$name]->connect(time());
                goto GET_NEW_CONN;
            }
            return $conn->getDbCli();
        }

        GET_NEW_CONN:
        /** @var Connection $conn */
        $conn = self::$pools[$name]->pop();
        self::$coroutineContext[$cid] = $conn;
        defer(function () use ($conn, $name, $cid) {
            self::$pools[$name]->push($conn);
            unset(self::$coroutineContext[$cid]);
        });
        return $conn->getDbCli();
    }
}