<?php

namespace Fastmag\Product\Attribute;

use Fastmag\Product\ProductAbstract;
use Fastmag\QB;
use Fastmag\ArrayHelper;

class Website implements AttributeAbstract {
    public function save(ProductAbstract $product) {
        $data = $product->getData($this->getAttributeCode());

        if (!is_array($data))
            $data = [$data];

        if (empty($data)) {
            QB::table('catalog_product_website')
                ->where('product_id', $product->getId())
                ->delete();
        }
        else {
            QB::table('catalog_product_website')
                ->whereNotIn('website_id', $data)
                ->where('product_id', $product->getId())
                ->delete();

            $current_data = ArrayHelper::map(function ($item) {
                $column = 'website_id';
                return $item->{$column};
            }, QB::table('catalog_product_website')
                ->select('website_id')
                ->where('product_id', $product->getId())
                ->get()
            );
            $data = array_diff($data, $current_data);
            $data = array_unique($data);
            if (!empty($data)) {
                QB::table('catalog_product_website')
                    ->insert(ArrayHelper::map(function ($item) use ($product) {
                        return [
                            'website_id' => $item,
                            'product_id' => $product->getId()
                        ];
                    }, $data));
            }
        }
        return $this;
    }

    public function get(ProductAbstract $product) {
        return ArrayHelper::map(
            function ($item) {
                return $item->website_id;
            },
            QB::table('catalog_product_website')
                ->select('website_id')
                ->where('product_id', $product->getId())
                ->get()
        );
    }

    public function getAttributeCode() {
        return 'website_ids';
    }
}
