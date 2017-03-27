<?php

namespace Fastmag\Product\Attribute;

use Fastmag\Product\ProductAbstract;
use Fastmag\QB;
use Fastmag\ArrayHelper;

class Category implements AttributeAbstract {
    public function save(ProductAbstract $product) {
        $data = $product->getData($this->getAttributeCode());

        if (!is_array($data))
            $data = [$data];

        if (empty($data)) {
            QB::table('catalog_category_product')
                ->where('product_id', $product->id)
                ->delete();
        }
        else {
            QB::table('catalog_category_product')
                ->whereNotIn('category_id', $data)
                ->where('product_id', $product->id)
                ->delete();

            $current_data = ArrayHelper::map(function ($item) {
                $column = 'category_id';
                return $item->{$column};
            }, QB::table('catalog_category_product')
                ->select('category_id')
                ->where('product_id', $product->id)
                ->get()
            );
            $data = array_diff($data, $current_data);
            $data = array_unique($data);
            if (!empty($data)) {
                QB::table('catalog_category_product')
                    ->insert(ArrayHelper::map(function ($item) use ($product) {
                        return [
                            'category_id' => $item,
                            'product_id' => $product->id
                        ];
                    }, $data));
            }
        }
        return $this;
    }

    public function get(ProductAbstract $product) {
        return ArrayHelper::map(
            function ($item) {
                return $item->category_id;
            },
            QB::table('catalog_category_product')
                ->select('category_id')
                ->where('product_id', $product->id)
                ->get()
        );
    }

    public function getAttributeCode() {
        return 'category_ids';
    }
}
