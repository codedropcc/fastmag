<?php

namespace Fastmag\Product;

use Fastmag;
use Fastmag\QB;

class Api {
    const LINK_TYPE_RELATED = 1;
    const LINK_TYPE_GROUPED = 3;
    const LINK_TYPE_UPSELL = 4;
    const LINK_TYPE_CROSSSELL = 5;

    /**
     * Create relations for product
     * @param int $parent_product
     * @param int $linked_product
     */
    public function createRelation($parent_product, $linked_product) {
        $data = [
            'product_id' => $parent_product,
            'linked_product_id' => $linked_product,
            'link_type_id' => self::LINK_TYPE_RELATED,
        ];
        return QB::table('catalog_product_link')->insert($data);
    }
}
