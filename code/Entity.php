<?php

namespace Fastmag;

use Fastmag\Exception;

abstract class Entity implements \ArrayAccess {
    protected $data = [];

    public function __construct() {
        // Do nothing.
    }

    public function __call($name, $args) {
        if (preg_match('/^get(.*)$/', $name, $matches)) {
            $key = $matches[1];
            return $this->getData($this->translateToKey($key));
        }
        else if (preg_match('/set(.*)$/', $name, $matches)) {
            $key = $matches[1];
            return $this->setData($this->translateToKey($key), $args[0]);
        }
        else if (preg_match('/^unset(.*)$/', $name, $matches)) {
            $key = $matches[1];
            return $this->unsetData($this->translateToKey($key));
        }
        else if (preg_match('/^remove(.*)$/', $name, $matches)) {
            $key = $matches[1];
            return $this->removeData($this->translateToKey($key));
        }
        else {
            throw new Exception("UNDEFINED FUNCTION " . $name);
        }
    }

    public function translateToKey($key) {
        $new_key = '';
        $i = 0;
        foreach (str_split($key) as $char) {
            if (ctype_upper($char) && $i != 0) {
                $new_key .= '_';
            }
            $new_key .= strtolower($char);
            $i++;
        }
        return $new_key;
    }
    
    abstract public function getData($key);
    abstract public function setData($key, $value);
    abstract public function unsetData($key);
    abstract public function removeData($key);

    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset) {
        if ($this->offsetExists($offset)) {
            return $this->data[$offset];
        }
        return null;
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->data[] = $value;
        }
        else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }
}
