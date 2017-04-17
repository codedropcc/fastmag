<?php

namespace Fastmag;

use Fastmag\FlatEntity;
use Fastmag\QB;
use Fastmag\Exception;

/**
 * @Injectable(scope="prototype")
 */
abstract class AttributeEntity extends FlatEntity {
    public function getData($key = null) {
        if (is_null($key)) {
            return $this->data;
        }

        if (!isset($this[$key])) {
            $value = parent::getData($key);
            if (!is_null($value)) {
                $this[$key] = $value;
            }
            else {
                $this[$key] = $this->getAttribute($key);
            }
        }
        return $this[$key] ?: null;
    }

    public function getAttributeData($key) {
        $result = QB::table('eav_attribute')
            ->select(['attribute_id', 'backend_type'])
            ->where('attribute_code', $key)
            ->where('entity_type_id', $this->entity_type->getId())
            ->first();
        if (!is_null($result) && $result->backend_type !== 'static') {
            return [
                'attribute_id' => $result->attribute_id,
                'backend_type' => $result->backend_type
            ];
        }
        return NULL;
    }

    public function getAttribute($key, $store_id = 0) {
        $result = $this->getAttributeData($key);
        if ($result) {
            $id = $result["attribute_id"];
            $type = $result["backend_type"];
            $val = QB::table($this->table().'_'.$type)
                ->where('attribute_id', $id)
                ->where('entity_id', $this->getId())
                ->where('store_id', $store_id)
                ->first();
            if (!is_null($val))
                return $val->value;
        }
        return NULL;
    }

    public function getEntityIdForColumn($column, $value) {
        if (key_exists($column, $this->getColumns())) {
            return parent::getEntityIdForColumn($column, $value);
        }
        $result = $this->getAttributeData($column);
        if (!is_null($result)) {
            $id = $result['attribute_id'];
            $type = $result['backend_type'];
            $entity_id = QB::table($this->table().'_'.$type)
                ->select('entity_id')
                ->where('attribute_id', $id)
                ->where('value', $value)
                ->first();
            if (!is_null($entity_id)) {
                return $entity_id->entity_id;
            }
            return NULL;
        }
        throw new Exception("Attribute {$column} not found!");
    }

    protected function customOptionsSave() {
        foreach ($this->data as $column => $value) {
            $this->saveAttribute($column, $value);
        }
    }

    public function saveAttribute($column, $newValue, $store_id = 0) {
        $result = $this->getAttributeData($column);
        if ($result) {
            $id = $result["attribute_id"];
            $type = $result["backend_type"];
            $val = QB::table($this->table().'_'.$type)
                ->select('value')
                ->where('attribute_id', $id)
                ->where('entity_id', $this->getId())
                ->where('store_id', $store_id)
                ->first();
            if (!is_null($val)) {
                $data = [
                    'value' => $newValue
                ];
                QB::table($this->table().'_'.$type)
                    ->where('attribute_id', $id)
                    ->where('entity_id', $this->getId())
                    ->where('store_id', $store_id)
                    ->update($data);
                return true;
            }
            else {
                $data = [
                    'entity_type_id' => $this->entity_type->getId(),
                    'attribute_id' => $id,
                    'store_id' => $store_id,
                    'entity_id' => $this->getId(),
                    'value' => $newValue
                ];
                return QB::table($this->table().'_'.$type)
                    ->insert($data);
            }
        }
        return NULL;
    }
}
