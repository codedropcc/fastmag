<?php

namespace Fastmag;

use Fastmag\ArrayHelper;
use Fastmag\Exception;
use Fastmag\Connection;

class AttributeHelper {
    const TYPE_PRODUCT = 1;
    const TYPE_CATALOG = 2;

    const DIR_OPTION_TO_VALUE = 1;
    const DIR_VALUE_TO_OPTION = 2;

    protected $conn = null;
    protected $entityColumns = [];
    protected $arrayHelper = null;

    protected static $instance = null;

    // @codeCoverageIgnoreStart

    protected function __construct() {
        $this->conn = Connection::getInstance();
    }

    private function __clone() {
    }

    private function __wakeup() {
    }

    public static function getInstance() {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }
    // @codeCoverageIgnoreEnd

    public function getEntityIdsForAttribute($attribute_code, $value) {
        $result = $this->getAttributeData($attribute_code);
        if (!is_null($result)) {
            $id = $result['attribute_id'];
            $type = $result['backend_type'];
            $entity_id = QB::table('catalog_product_entity_'.$type)
                ->select('entity_id')
                ->where('attribute_id', $id)
                ->where('value', $value)
                ->get();
            if (!empty($entity_id)) {
                return ArrayHelper::map(function ($item) {
                    return $item->entity_id;
                }, $entity_id);
            }
        }
        return NULL;
    }

    public function translateToAttributeName($attribute) {
        $attribute_name = '';
        $i = 0;
        foreach (str_split($attribute) as $char) {
            if (ctype_upper($char) && $i != 0) {
                $attribute_name .= '_';
            }
            $attribute_name .= strtolower($char);
            $i++;
        }
        return $attribute_name;
    }

    public function getAttributeIdByCode($attribute_code) {
        $attribute = QB::table('eav_attribute')
            ->select('attribute_id')
            ->where('attribute_code', $attribute_code)
            ->where('entity_type_id', 4)
            ->first();
        if (!is_null($attribute))
            return $attribute->attribute_id;
        return NULL;
    }

    public function getAttributeData($attribute_code) {
        $result = QB::table('eav_attribute')
            ->select(['attribute_id', 'backend_type'])
            ->where('attribute_code', $attribute_code)
            ->where('entity_type_id', 4)
            ->first();
        if (!is_null($result)) {
            return [
                'attribute_id' => $result->attribute_id,
                'backend_type' => $result->backend_type
            ];
        }
        return NULL;
    }

    public function getAttribute($entity_id, $attribute_code, $store_id = 0) {
        $result = $this->getAttributeData($attribute_code);
        if ($result) {
            $id = $result["attribute_id"];
            $type = $result["backend_type"];
            $val = QB::table('catalog_product_entity_'.$type)
                ->where('attribute_id', $id)
                ->where('entity_id', $entity_id)
                ->where('store_id', $store_id)
                ->first();
            if (!is_null($val))
                return $val->value;
        }
        return NULL;
    }

    public function updateAttributes($entity_id, $data, $store_id = 0) {
        foreach ($data as $attribute => $value) {
            $this->setAttribute($entity_id, $attribute, $value, $store_id);
        }
    }

    public function setAttribute($entity_id, $attribute_code, $newValue, $store_id = 0) {
        $result = $this->getAttributeData($attribute_code);
        if ($result) {
            $id = $result["attribute_id"];
            $type = $result["backend_type"];
            $val = QB::table('catalog_product_entity_'.$type)
                ->select('value')
                ->where('attribute_id', $id)
                ->where('entity_id', $entity_id)
                ->where('store_id', $store_id)
                ->first();
            if (!is_null($val)) {
                $data = [
                    'value' => $newValue
                ];
                QB::table('catalog_product_entity_'.$type)
                    ->where('attribute_id', $id)
                    ->where('entity_id', $entity_id)
                    ->where('store_id', $store_id)
                    ->update($data);
                return true;
            }
            else {
                $data = [
                    'entity_type_id' => 4,
                    'attribute_id' => $id,
                    'store_id' => $store_id,
                    'entity_id' => $entity_id,
                    'value' => $newValue
                ];
                return QB::table('catalog_product_entity_'.$type)
                    ->insert($data);
            }
        }
        return NULL;
    }

    public function removeAttribute($entity_id, $attribute_code, $store_id = 0) {
        $result = $this->getAttributeData($attribute_code);
        if ($result) {
            $id = $result['attribute_id'];
            $type = $result['backend_type'];
            QB::table('catalog_product_entity_'.$type)
                ->where('attribute_id', $id)
                ->where('entity_id', $entity_id)
                ->where('store_id', $store_id)
                ->delete();
        }
        return NULL;
    }

    public function getAttributeOptionValue($attribute_code, $value) {
        $attribute_id = QB::table('eav_attribute')
            ->select('attribute_id')
            ->where('entity_type_id', 4)
            ->where('attribute_code', $attribute_code)
            ->first();
        if (!is_null($attribute_id)) {
            $attribute_id = $attribute_id->attribute_id;
            $options = QB::table('eav_attribute_option')
                ->select('option_id')
                ->where('attribute_id', $attribute_id)
                ->get();
            if (!empty($options)) {
                $options = ArrayHelper::map(function ($item) {
                    return $item->option_id;
                }, $options);
                $option_id = QB::table('eav_attribute_option_value')
                    ->select('option_id')
                    ->where('value', $value)
                    ->whereIn('option_id', $options)
                    ->groupBy('option_id')
                    ->first();
                if (!is_null($option_id)) {
                    return $option_id->option_id;
                }
            }
        }
        return NULL;
    }
    public function getAttributeLabel($attribute_id){
        $result = QB::table('eav_attribute_label')
            ->select('value')
            ->where('attribute_id', $attribute_id)
            ->groupBy('value')
            ->first();

        if (!is_null($result))
            return $result->value;

        return NULL;
    }

    public function getOptionLabel($option_id, $store_id = 0) {
        $result = QB::table('eav_attribute_option_value')
            ->select('value')
            ->where('option_id', $option_id)
            ->where('store_id', $store_id)
            ->groupBy('value')
            ->first();

        if (!is_null($result))
            return $result->value;

        return NULL;
    }

    public function getEntityColumns($entity_type) {
        if (!isset($this->entityColumns[$entity_type])) {
            $this->entityColumns[$entity_type] = $this->getColumns(
                $this->conn->getPrefix().'catalog_product_entity'
            );
        }
        return $this->entityColumns[$entity_type];
    }

    public function getColumns($table) {
        $sql = "SHOW COLUMNS FROM {$table}";
        return ArrayHelper::map(function ($item) {
            return $item->Field;
        }, QB::query($sql)->get());
    }

    public function getOptionValues($attribute_code, $direction = self::DIR_VALUE_TO_OPTION) {
        $subquery_attribute_id = QB::table('eav_attribute')
            ->select('attribute_id')
            ->where('entity_type_id', 4)
            ->where('attribute_code', $attribute_code);
        $subquery_option_id = QB::table('eav_attribute_option')
            ->select('option_id')
            ->where(QB::raw('attribute_id IN (' . $subquery_attribute_id->getQuery()->getRawSql() . ')'));
        $result = QB::table('eav_attribute_option_value')
            ->where('store_id', 0)
            ->where(QB::raw('option_id IN (' . $subquery_option_id->getQuery()->getRawSql() . ')'))
            ->get();

        if (!empty($result)) {
            switch ($direction) {
                case self::DIR_VALUE_TO_OPTION:
                    $build = function ($result, $item) {
                        $result[$item->value] = $item->option_id;
                        return $result;
                    };
                    break;
                case self::DIR_OPTION_TO_VALUE:
                    $build = function ($result, $item) {
                        $result[$item->option_id] = $item->value;
                        return $result;
                    };
                    break;
                default:
                    throw new Exception("Unknown direction!");
            }
            return ArrayHelper::reduce(
                $build,
                $result,
                array()
            );
        }
        return NULL;
    }

    public function getSkuById($entity_id) {
        $result = QB::table('catalog_product_entity')
            ->select('sku')
            ->where('entity_id', $entity_id)
            ->first();
        if (!is_null($result))
            return $result->sku;
        return NULL;
    }

    public function getIdBySku($sku) {
        $result = QB::table('catalog_product_entity')
            ->select('entity_id')
            ->where('sku', $sku)
            ->first();
        if (!is_null($result))
            return $result->entity_id;
        return NULL;
    }
}
