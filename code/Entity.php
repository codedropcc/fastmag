<?php

namespace Fastmag;

use Fastmag\Exception;
use Fastmag\Fastmag;

/**
 * @Injectable(scope="prototype")
 */
abstract class Entity implements \ArrayAccess {
    protected $data = [];
    protected $columns;
    protected $primary_key = null;
    protected $is_new = true;

    public function __construct() {
        $this->columns = $this->getColumns();
        if (is_null($this->primary_key)) {
            throw new Exception("Table {$this->table()} has no primary key!");
        }
    }

    public function __call($name, $args) {
        if (preg_match('/^get(.*)$/', $name, $matches)) {
            $key = $matches[1];
            return $this->getData($this->translateToKey($key));
        }
        else if (preg_match('/^set(.*)$/', $name, $matches)) {
            $key = $matches[1];
            return $this->setData($this->translateToKey($key), $args[0]);
        }
        else if (preg_match('/^unset(.*)$/', $name, $matches)) {
            $key = $matches[1];
            return $this->unsetData($this->translateToKey($key));
        }
        else {
            throw new Exception("UNDEFINED FUNCTION " . $name);
        }
    }

    public function getId() {
        return $this[$this->primary_key] ?: null;
    }

    abstract public function load($id, $column = null);

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

    public function getColumns() {
        if (is_null($this->columns)) {
            $prefix = Fastmag::getInstance()->getModel('Fastmag\Connection')->getPrefix();
            $sql = "SHOW COLUMNS FROM {$prefix}{$this->table()}";
            $this->columns = ArrayHelper::map(function ($item) {
                if ($item->Key === 'PRI') {
                    $this->primary_key = $item->Field;
                }
                return $item->Field;
            }, QB::query($sql)->get());
        }
        return $this->columns;
    }

    public function getFromColumns($key) {
        $data = QB::table($this->table())
            ->select($key)
            ->where($this->primary_key, $this->getId())
            ->first();
        return $data->{$key};
    }

    public function getEntityData() {
        $data = [];
        foreach ($this->data as $column => $value) {
            if (in_array($column, $this->getColumns())) {
                $data[$column] = $value;
            }
        }
        return $data;
    }

    public function getEntityIdForColumn($column, $value) {
        $id = QB::table($this->table())
            ->select($this->primary_key)
            ->where($column, $value)
            ->first();
        if (!is_null($id)) {
            return $id->{$this->primary_key};
        }
        return null;
    }

    public function save() {
        $returnData = null;
        QB::transaction(function ($qb) use (&$returnData) {
            try {
                $entityData = $this->getEntityData();
                if ($this->getId() === NULL) {
                    // Insert new product
                    $this->is_new = true;
                    $data_to_insert = $entityData;
                    $data_to_insert['created_at'] = $data_to_insert['updated_at'] = date('Y-m-d H:i:s');
                    $this[$this->primary_key] = QB::table($this->table())->insert($data_to_insert);
                }
                else {
                    $this->is_new = false;
                    $data_to_update = $entityData;
                    unset($data_to_update[$this->primary_key]);
                    unset($data_to_update['created_at']);
                    $data_to_update['updated_at'] = date('Y-m-d H:i:s');
                    QB::table($this->table())
                        ->where($this->primary_key, $this->getId())
                        ->update($data_to_update);
                }
                
                /** Let the nested class the realize custom options */
                $this->customOptionsSave();
                $returnData = $this;
                $qb->commit();
            }
            catch (Exception $e) {
                $returnData = $e->getMessage();
                $qb->rollback();
            }
        });
        return $returnData;
    }

    abstract protected function customOptionsSave();

    abstract public function table();

    abstract public function getData($key);
    abstract public function setData($key, $value);
    abstract public function unsetData($key);

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
        if (!is_null($offset)) {
            $this->data[$offset] = $value;
        }
    }

    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }

    public function isNew() {
        return $this->is_new;
    }
}
