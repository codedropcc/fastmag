<?php

namespace Fastmag\Product\Attribute;

use Fastmag;

class Factory {
    public static function create($attribute) {
        $className = 'Fastmag\Product\Attribute\\'.$attribute;
        return Fastmag\Fastmag::getInstance()->getModel($className);
    }
}
