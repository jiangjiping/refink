<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/2/27
 */

namespace Refink\Http;


abstract class AbstractMiddleware
{
    public abstract function handle($request): bool;
}