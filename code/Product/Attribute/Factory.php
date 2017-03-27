<?php

namespace Fastmag\Product\Attribute;

class Factory {
    public function create($attribute) {
        $className = 'Fastmag\\Product\\Attribute\\'.$attribute;
        if (class_exists($className))
            return new $className();
        else
            return NULL;
    }
}
