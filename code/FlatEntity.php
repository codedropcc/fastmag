<?php

namespace Fastmag;

use Fastmag\Entity;
use Fastmag\Exception;

/**
 * @Injectable(scope="prototype")
 */
abstract class FlatEntity extends Entity {
    public function getData($key = null) {
        if (is_null($key)) {
            return $this->data;
        }

        if (!isset($this[$key])) {
            if (in_array($key, $this->getColumns())) {
                $this[$key] = $this->getFromColumns($key);
            }
        }
        return $this[$key] ?: null;
    }

    public function setData($key, $value = NULL) {
        if (is_array($key)) {
            foreach ($key as $internal_key => $value) {
                $this->setData($internal_key, $value);
            }
        }
        else {
            $this[$key] = $value;
        }
        return $this;
    }

    public function unsetData($key) {
        if (is_array($key)) {
            foreach ($key as $_ => $value) {
                $this->unsetData($value);
            }
        }
        else {
            unset($this[$key]);
        }
    }

    public function load($id, $column = null) {
        if (!is_null($column)) {
            $id = $this->getEntityIdForColumn($column, $id);
        }
        $this[$this->primary_key] = $id;
        $this->is_new = false;
        return $this;
    }

    protected function customOptionsSave() {}
}
