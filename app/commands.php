<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/8
 */


//console run function or method
\Refink\Command::register("migrate", [\App\Console\Migrate::class, 'run'], "迁移数据");