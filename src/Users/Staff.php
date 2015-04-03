<?php
/**
 * Created by PhpStorm.
 * User: LZ-Mac-Pro
 * Date: 15-03-29
 * Time: 9:00 AM
 */

namespace App\Users;
use RedBeanPHP\R;

class Staff extends Admin {

    protected $level = 7;


    public static function all()
    {

        return R::find('user', ' level = 7 ');

    }

}