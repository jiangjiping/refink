<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/9
 */

namespace App\Models;


use Refink\Database\ORM\Model;

class UserModel extends Model
{
    protected $table = 'user';

    protected $primaryKey = 'user_id';

}