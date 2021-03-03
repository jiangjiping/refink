<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/3
 */

namespace Refink;


interface Job
{
    public function getGroupId();

    public function handle();
}