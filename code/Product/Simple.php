<?php

namespace Fastmag\Product;

use Fastmag\Exception;

use Fastmag\Product\ProductAbstract;

/**
 * @Injectable(scope="prototype")
 */
class Simple extends ProductAbstract {
    protected function customOptionsSave() {
        parent::customOptionsSave();
        // Nothing to do for simple, brah!
    }
}

