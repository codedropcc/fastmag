<?php

namespace Fastmag\Product;

use Fastmag\AttributeHelper;
use Fastmag\ArrayHelper;
use Fastmag\Connection;
use Fastmag\Exception;
use Fastmag\AttributeEntity;

use Fastmag\Product\Attribute\Factory as AttributeFactory;

use Fastmag\QB;
/**
 * @Injectable(scope="prototype")
 */
abstract class ProductAbstract extends AttributeEntity
{
    /** @var AttributeHelper $attributeHelper */
    protected $attributeHelper;
    /** @var Connection $conn */
    protected $conn;

    protected $attributes;

    protected $baseDir;
    protected $baseUrl;

    const RELATION_TYPE_WEBSITE = 1;
    const RELATION_TYPE_CATEGORY = 2;

    public function __construct(
        AttributeHelper $attributeHelper,
        Connection $conn
    ) {
        $this->attributeHelper = $attributeHelper;
        $this->conn = $conn;
        $this->attributes = [];
        foreach (['Website', 'Category', 'TierPrice', 'StockData', 'Image'] as $attribute) {
            $model = AttributeFactory::create($attribute);
            $this->attributes[$model->getAttributeCode()] = $model;
        }
        // @codeCoverageIgnoreStart
        if (class_exists('\Mage')) {
            $this->baseDir = \Mage::getBaseDir();
            $this->baseUrl = \Mage::getBaseUrl();
        }
        else {
            $this->baseDir = __DIR__;
            $this->baseUrl = __DIR__;
        }
        // @codeCoverageIgnoreEnd
        parent::__construct('catalog_product');
    }
    
    public function table() {
        return 'catalog_product_entity';
    }

    public function saveAttribute($column, $newValue, $store_id = 0) {
        if (key_exists($column, $this->attributes)) {
            $this->attributes[$column]->save($this);
        }
        else {
            parent::saveAttribute($column, $newValue, $store_id);
        }
    }

    public function getData($key = null) {
        if (!key_exists($key, $this->data) && key_exists($key, $this->attributes)) {
            $this[$key] = $this->attributes[$key]->get($this);
            return $this[$key];
        }
        return parent::getData($key);
    }

    public function getRelatedLinkCollection() {
        return ArrayHelper::map(function ($item) {
            return $item->linked_product_id;
        }, QB::table('catalog_product_link')
            ->select('linked_product_id')
            ->where('product_id', $this->getId())
            ->where('link_type_id', 1)
            ->get()
        );
    }

    public function getParentRelatedLinkCollection() {
        return ArrayHelper::map(function ($item) {
            return $item->product_id;
        }, QB::table('catalog_product_link')
            ->select('product_id')
            ->where('linked_product_id', $this->getId())
            ->where('link_type_id', 1)
            ->get()
        );
    }

    public function isSaleable() {
        return $this->getData('status') == 1;
    }

    public function getFormatedTierPrice() {
        $tier_price = $this->getData('tier_price');
        $data = [];
        foreach ($tier_price as $price) {
            $data[] = [
                "price_qty" => $price['qty'],
                "price" => $price['price'],
            ];
        }
        return $data;
    }

    public function getImageUrl() {
        if ($this->getData('image') && $this->getData('image') != '')
            return $this->baseUrl
                . DIRECTORY_SEPARATOR . 'media'
                . DIRECTORY_SEPARATOR . $this->getImagePath();
        else
            return false;
    }

    public function getImagePath() {
        if ($this->getData('image') && $this->getData('image') != '')
            return 'catalog' . DIRECTORY_SEPARATOR . 'product' . $this->getData('image');
        else
            return false;
    }
    
    public function getBaseDir() {
        return $this->baseDir;
    }
    
    public function getBaseUrl() {
        return $this->baseUrl;
    }
}

