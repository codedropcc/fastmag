<?php

namespace Fastmag\Product;

use Fastmag\Connection;
use Fastmag\AttributeHelper;
use Fastmag\ArrayHelper;
use Fastmag\QB;

use Fastmag\Product\Factory;

class Collection {
    public $products = [];
    
    /** @var AttributeHelper $attributeHelper */
    protected $attributeHelper;
    /** @var Connection $attributeHelper */
    protected $conn;
    protected $filters = [];
    protected $prefix;

    protected $isFilterChanged;
    protected $isCacheValid;

    // Do not load every product bro, that's stupid.
    public function __construct($products = array()) {
        $this->conn = Connection::getInstance();
        $this->attributeHelper = AttributeHelper::getInstance();
        $this->isFilterChanged = false;
        $this->prefix = $this->conn->getPrefix();
        if (empty($products)) {
            $this->products = null;
            $this->isCacheValid = false;
        }
        else {
            $this->products = $products;
            $this->isCacheValid = true;
        }
    }

    public function addFieldToFilter($attribute, $value) {
        if (isset($this->filters[$attribute]))
            $this->isFilterChanged = true;
        $this->filters[$attribute] = $value;
        $this->isCacheValid = false;
        return $this;
    }

    public function getItems() {
        if (!$this->isCacheValid) {
            if (count($this->filters) == 0) {
                $this->products = ArrayHelper::map(function ($item) {
                    return Factory::create($item->entity_id);
                }, QB::table('catalog_product_entity')
                    ->select('entity_id')
                    ->get()
                );
                return $this->products;
            }
            $entityColumns = $this->attributeHelper->getEntityColumns(AttributeHelper::TYPE_PRODUCT);
            $wheres = [];
            $tables = [];
            $counter = 1;
            foreach ($this->filters as $attributeCode => $value) {
                $attribute = $this->attributeHelper->getAttributeData($attributeCode);
                if (in_array($attributeCode, $entityColumns)) {
                    $wheres['c0.'.$attributeCode] = $value;
                } else {
                    $table = 'c'.$counter++;
                    $tables[$table] = 'catalog_product_entity_'.$attribute['backend_type'];
                    $wheres[$table.'.attribute_id'] = $attribute['attribute_id'];
                    $wheres[$table.'.value'] = $value;
                }
            }
            $buildedSql = $this->buildSql($wheres, $tables);
            if ($buildedSql) {
                $this->products = ArrayHelper::map(function ($item) {
                    return Factory::create($item->entity_id);
                }, $buildedSql->get());
                $this->isCacheValid = true;
                $this->isFilterChanged = false;
            }
        }
        return $this->products;
    }

    protected function buildSql($wheres, $tables) {
        $query = QB::table(['catalog_product_entity' => 'c0'])
            ->select('c0.entity_id');

        foreach ($wheres as $name => $value) {
            $query->where($name, $value);
        }
        
        foreach ($tables as $tableAlias => $table) {
            $query->leftJoin([$table, $tableAlias], $tableAlias.'.entity_id', '=', 'c0.entity_id');
        }
        
        if (count($this->products) && !$this->isFilterChanged) {
            $ids = ArrayHelper::map(function ($item) {
                return $item->id;
            }, $this->products);
            $query->whereIn('c0.entity_id', $ids);
        }
        
        return $query;
    }
}

