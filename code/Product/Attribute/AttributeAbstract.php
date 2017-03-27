<?php

namespace Fastmag\Product\Attribute;

use Fastmag\Product\ProductAbstract;

interface AttributeAbstract {
    public function save(ProductAbstract $product);
    public function get(ProductAbstract $product);
    public function getAttributeCode();
}
