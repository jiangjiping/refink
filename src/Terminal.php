<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/2/26
 */

namespace Refink;


class Terminal
{

    const RESET = "\033[0m";
    /**
     * Black
     */
    const BLACK = "\033[30m";

    /**
     * Red
     */
    const RED = "\033[31m";
    /**
     * Green
     */
    const GREEN = "\033[32m";

    /**
     * Yellow
     */
    const YELLOW = "\033[33m";
    /**
     * Blue
     */
    const BLUE = "\033[34m";
    /**
     * Magenta
     */
    const MAGENTA = "\033[35m";
    /**
     * Cyan
     */
    const CYAN = "\033[36m";
    /**
     * White
     */
    const WHITE = "\033[37m";
    /**
     * Bold Black
     */
    const BOLD_BLACK = "\033[1m\033[30m";
    /**
     * Bold Red
     */
    const BOLD_RED = "\033[1m\033[31m";
    /**
     * Bold Green
     */
    const BOLD_GREEN = "\033[1m\033[32m";
    /**
     * Bold Yellow
     */
    const BOLD_YELLOW = "\033[1m\033[33m";
    /**
     * Bold Blue
     */
    const BOLD_BLUE = "\033[1m\033[34m";
    /**
     * Bold Magenta
     */
    const BOLD_MAGENTA = "\033[1m\033[35m";
    /**
     * Bold Cyan
     */
    const BOLD_CYAN = "\033[1m\033[36m";
    /**
     * Bold White
     */
    const BOLD_WHITE = "\033[1m\033[37m";


    public static function getColoredText($text, $color = self::WHITE)
    {
        return $color . $text . self::RESET;
    }

    public static function echoTableLine()
    {
        echo str_pad("*", 20, "*") . str_pad("*", 30, "*") . PHP_EOL;
    }


}