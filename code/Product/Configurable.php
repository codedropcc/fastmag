<?php

namespace Fastmag\Product;

use Fastmag\Exception;
use Fastmag\ArrayHelper;
use Fastmag\QB;
use Fastmag\Product\ProductAbstract;

class Configurable extends ProductAbstract
{
    protected function customOptionsSave() {
        // Nothing to do for configurable
        if (isset($this->data['configurable_attributes_data'])) {
            $this->saveConfigurableAttributesData($this->data['configurable_attributes_data']);
        }
        if (isset($this->data['configurable_products_data'])) {
            $this->saveConfigurableProductsData($this->data['configurable_products_data']);
        }
    }

    public function getRelationIds() {
        $items = QB::table('catalog_product_super_link')
            ->select('product_id')
            ->where('parent_id', $this->id)
            ->get();
        return ArrayHelper::map(function($item){
            return $item->product_id;
        }, $items);
    }
    
    protected function getProductSuperAttributeId($attribute_id) {
        $item = QB::table('catalog_product_super_attribute')
            ->select('product_super_attribute_id')
            ->where('product_id', $this->id)
            ->where('attribute_id', $attribute_id)
            ->first();
        if (!is_null($item)) {
            return $item->product_super_attribute_id;
        }
        return NULL;
    }
    
    protected function saveConfigurableAttributesData($value) {
        $i = 0;
        foreach ($value as $code => $attribute_id){
            $product_super_attribute_id = $this->getProductSuperAttributeId($attribute_id);
            if(is_null($product_super_attribute_id)) {
                $product_super_attribute_id = QB::table('catalog_product_super_attribute')
                    ->insert([
                        'product_id' => $this->id,
                        'attribute_id' => $attribute_id,
                        'position' => $i
                    ]);
                if($product_super_attribute_id){
                    $attribute_label = $this->attributeHelper->getAttributeLabel($attribute_id);
                    QB::table('catalog_product_super_attribute_label')
                        ->insert([
                            'product_super_attribute_id' => $product_super_attribute_id,
                            'store_id' => 0,
                            'use_default' => 1,
                            'value' => $attribute_label
                        ]);
                }
            }
            $i++;
        }
    }

    protected function saveConfigurableProductsData($value) {
        $newIds = array_keys($value);
        $oldIds = $this->getRelationIds();

        $delete = array_diff($oldIds, $newIds);
        $insert = array_diff($newIds, $oldIds);

        foreach ($delete as $id) {
            QB::table('catalog_product_super_link')
                ->where('parent_id', $this->id)
                ->where('product_id', $id)
                ->delete();
            QB::table('catalog_product_relation')
                ->where('parent_id', $this->id)
                ->where('child_id', $id)
                ->delete();
        }
        
        foreach ($insert as $id) {
            QB::table('catalog_product_super_link')
                ->insert([
                    'parent_id' => $this->id,
                    'product_id' => $id,
                ]);
            QB::table('catalog_product_relation')
                ->insert([
                    'parent_id' => $this->id,
                    'child_id' => $id,
                ]);
        }
    }
}
