<?php

namespace Fastmag\Product;

use Fastmag\Product\ProductAbstract;

class Virtual extends ProductAbstract
{
    protected function customOptionsSave()
    {
        parent::customOptionsSave();
        // Nothing to do for virual, brah!
    }
}
