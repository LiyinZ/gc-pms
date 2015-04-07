<?php
/**
 * Created by PhpStorm.
 * User: LZ-Mac-Pro
 * Date: 15-03-29
 * Time: 8:59 AM
 */

namespace App;
use RedBeanPHP\R;
use Slim\Slim;
use \Michelf\Markdown;

class Property {

    public function create()
    {
        if ($this->is_valid_property) {
            $property = R::dispense('property');
            $property->address = $this->address;
            $property->city = $this->city;
            $property->province = $this->province;
            $property->postal = $this->postal;
            $property->type = $this->type;

        }
    }

}