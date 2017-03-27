<?php

namespace Fastmag\Product;

use Fastmag;
use Fastmag\AttributeHelper;
use Fastmag\ArrayHelper;
use Fastmag\Connection;
use Fastmag\QB;
use Fastmag\Exception;

/** Product classes */
use Fastmag\Product\Factory;
use Fastmag\Product\Simple;
use Fastmag\Product\Bundle;
use Fastmag\Product\Virtual;
use Fastmag\Product\Grouped;
use Fastmag\Product\Downloadable;
use Fastmag\Product\Configurable;

class Api {
    public $id;
    
    /** @var AttributeHelper $attributeHelper */
    protected $attributeHelper;
    /** @var Connection $attributeHelper */
    protected $conn;
    protected $prefix;

    const DEFAULT_BUNDLE_OPTION_TYPE = 'select';
    const DEFAULT_BUNDLE_SELECTION_QTY = 1;
    const LINK_TYPE_RELATED = 1;
    const LINK_TYPE_GROUPED = 3;
    const LINK_TYPE_UPSELL = 4;
    const LINK_TYPE_CROSSSELL = 5;

    public function __construct(
        AttributeHelper $attributeHelper,
        Connection $conn
    ) {
        $this->attributeHelper = $attributeHelper;
        $this->conn = $conn;
        $this->prefix = $this->conn->getPrefix();
    }

    /**
     * Create simple product or update if isset($data['entity_id'])
     * $data is simple assoc array [ [attribute_name=>value], ... ]
     * @param $data
     * @throws Exception
     * @throws \Exception
     */
    public function createSimple($data)
    {
        if (!is_array($data) || empty($data))
            throw new Exception('Data should be set.');

        $simple = Fastmag\Fastmag::getInstance()->getModel('Fastmag\Product\Simple');

        if (isset($data['entity_id']) && $data['entity_id']) {
            $simple->load($data['entity_id']);
        } else {
            $simple->setTypeId('simple')
                ->setCreatedAt(strtotime('now'));
        }


        $simple->setData($data)
            ->setUpdatedAt(strtotime('now'));

        // We except or array [price => qty, ]

        $simple->save();
        return $simple;
    }

    /**
     * TODO@Dunkon we'll should be defined data array.
     * Remove this then.
     * $products = ['selection' => [ option_id => [product_id, product_qty, etc]]
     * $data = ['bundle', 'images', 'options', 'flags']
     *
     * @param array $data
     * @throws Fastmag_Exception
     * @throws Exception
     */
    public function createBundle(array $data) {
        if (empty($data['bundle']))
            throw new Exception('Data is empty, please set data for bundle.');

        $bundle = Factory::create($data['bundle']);

        /** Set bundle data */
        $bundle->setData($data);
        $bundle->save();

        return $bundle;
    }

    public function createConfigurable(array $data) {
        if (empty($data)) {
            throw new Exception('Data is empty, please set data for configurable.');
        }
        
        $config = Factory::create($data);
        $config->save();
        
        return $config;
    }

    public function createGrouped(array $data) {
        if (empty($data)) {
            throw new Exception('Data is empty, please set data for grouped.');
        }

        $grouped = Factory::create($data);
        $grouped->save();

        return $grouped;
    }
    
    public function createVirtual(array $data) {
        if (empty($data)) {
            throw new Exception('Data is empty, please set data for virtual.');
        }

        $virtual = Factory::create($data);
        $virtual->save();

        return $virtual;
    }
    
    public function createDownloadable(array $data) {
        if (empty($data)) {
            throw new Exception('Data is empty, please set data for downloadable.');
        }

        $downloadable = Factory::create($data);
        $downloadable->save();

        return $downloadable;
    }

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
