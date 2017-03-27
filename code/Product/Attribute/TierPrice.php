<?php

namespace Fastmag\Product\Attribute;

use Fastmag\Product\ProductAbstract;
use Fastmag\QB;
use Fastmag\ArrayHelper;
use Fastmag\Product\Attribute\AttributeAbstract;

/**
 * Depends on WebsiteId attribute, btw
 */
class TierPrice implements AttributeAbstract {
    public function save(ProductAbstract $product) {
        $data = $product->getData($this->getAttributeCode());
        if (!is_array($data)) {
            $tier_price = ArrayHelper::map(
                function ($item) {
                    $data = explode(':', $item);
                    $qty = $data[0];
                    $price = $data[1];
                    return [
                        'price' => $price,
                        'price_qty' => $qty
                    ];
                },
                explode(';', $data)
            );
        }
        else if (!isset($data[0]['price'])) {
            $tier_price = [];
            foreach ($data as $price => $qty) {
                $tier_price[] = [
                    'price' => $price,
                    'price_qty' => $qty
                ];
            }
        }
        else {
            $tier_price = $data;
        }

        $mappedFields = [
            'value' => 'price',
            'qty' => 'price_qty',
        ];
        QB::table('catalog_product_entity_tier_price')
            ->where('entity_id', $product->id)
            ->delete();
        if (!empty($tier_price)) {
            $website_ids = $product->getData('website_ids');
            if (!empty($website_ids)) {
                $datas = [];
                foreach ($website_ids as $website_id) {
                    foreach ($tier_price as $tp) {
                        $datas[] = [
                            'entity_id' => $product->id,
                            'value' => $tp[$mappedFields['value']],
                            'qty' => $tp[$mappedFields['qty']],
                            'website_id' => $website_id,
                        ];
                    }
                }
                QB::table('catalog_product_entity_tier_price')
                    ->insert($datas);
            }
        }
    }

    public function get(ProductAbstract $product) {
        return ArrayHelper::map(
            function ($item) {
                return [
                    'qty' => $item->qty,
                    'price' => $item->value
                ];
            },
            QB::table('catalog_product_entity_tier_price')
                ->select(['qty', 'value'])
                ->where('entity_id', $product->id)
                ->get()
        );
    }

    public function getAttributeCode() {
        return 'tier_price';
    }
}
