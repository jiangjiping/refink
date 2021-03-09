<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/8
 */

namespace Refink;


use Refink\Exception\CommandException;

class Command
{
    private static $commands;

    /**
     * route the php-cli command to function
     * @param string $command
     * @param callable $func
     * @param string $desc
     */
    public static function bind(string $command, $func, string $desc)
    {
        if (!isset(self::$commands[$command])) {
            self::$commands[$command] = [
                'func' => $func,
                'desc' => $desc
            ];
        }
    }


    /**
     * @param $command
     * @return mixed
     * @throws \Exception
     */
    public static function getCommandCallback($command)
    {
        if (!isset(self::$commands[$command])) {
            throw new CommandException("command: \"{$command}\" was not found!\n");
        }
        return self::$commands[$command]['func'];
    }

    public static function help()
    {
        $help = "supported commands are:\n";
        if (empty(self::$commands)) {
            return $help;
        }
        foreach (self::$commands as $command => $item) {
            $help .= str_pad("", 2) . str_pad($command, 20) . $item['desc'];
        }
        return $help . PHP_EOL;
    }
}