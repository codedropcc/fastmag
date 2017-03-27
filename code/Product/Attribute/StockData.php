<?php

namespace Fastmag\Product\Attribute;

use Fastmag\Product\ProductAbstract;
use Fastmag\QB;
use Fastmag\ArrayHelper;
use Fastmag\AttributeHelper;

class StockData implements AttributeAbstract {
    protected $attributeHelper;

    public function __construct(
        AttributeHelper $attributeHelper
    ) {
        $this->attributeHelper = $attributeHelper;
    }

    public function save(ProductAbstract $product) {
        if (ArrayHelper::is_multi($product->getData($this->getAttributeCode()))) {
            foreach ($stock_data as $key => $value) {
                $product->setData($this->getAttributeCode(), $value);
                $this->save($product);
            }
        }
        else {
            $data = $product->getData($this->getAttributeCode());
            $data['product_id'] = $product->id;
            
            $alreadyExistingStocks = $this->get($product);
            foreach ($alreadyExistingStocks as $stock) {
                if ($stock['stock_id'] == $data['stock_id']) return;
            }
            $table = 'cataloginventory_stock_item';

            $fields = $this->attributeHelper->getColumns($table);

            $stock_data_fields = array_keys($data);
            $issetFields = ArrayHelper::filter(function ($field) use ($stock_data_fields) {
                return in_array($field, $stock_data_fields);
            }, $fields);

            if (!empty($issetFields)) {
                QB::table($table)->insert($data);
            }
        }
    }

    public function get(ProductAbstract $product) {
        $data = ArrayHelper::map(function ($item) {
            return (array)$item;
        }, QB::table('cataloginventory_stock_item')
            ->select('*')
            ->findAll('product_id', $product->id)
        );
        return (array)$data;
    }

    public function getAttributeCode() {
        return 'stock_data';
    }
}
