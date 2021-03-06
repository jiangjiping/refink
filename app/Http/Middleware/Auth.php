<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/2
 */

namespace App\Http\Middleware;


use Refink\Http\AbstractMiddleware;

class Auth extends AbstractMiddleware
{
    public function handle(&$request)
    {
        //var_dump($request['888']);
        if (empty($request['user_id'])) {
            $this->terminate("bad userId");
        }
        $request['auth_success'] = true;

    }
}