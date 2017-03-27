<?php

namespace Fastmag\Product;

use Fastmag\AttributeHelper;
use Fastmag\ArrayHelper;
use Fastmag\Connection;
use Fastmag\Exception;

use Fastmag\Product\Attribute\Factory as AttributeFactory;

use Fastmag\QB;
/**
 * @Injectable(scope="prototype")
 */
abstract class ProductAbstract
{
    public $id;
    public $data;
    /** @var AttributeHelper $attributeHelper */
    protected $attributeHelper;
    /** @var Connection $conn */
    protected $conn;

    protected $category_ids;
    protected $website_ids;
    protected $tier_prices;
    protected $_isNew = true;

    protected $attributes;

    protected $prefix;

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
        $this->prefix = $this->conn->getPrefix();
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
    }

    public function loadByAttribute($attribute, $value) {
        $id = $this->attributeHelper->getEntityIdsForAttribute($attribute, $value);
        if (is_array($id) && !empty($id))
            $this->load(array_pop($id));
        else
            throw new Exception("No product with $attribute = $value");
        return $this;
    }

    public function load($id) {
        $this->id = $id;
        $this->_isNew = false;
        return $this;
    }

    public function __call($name, $args) {
        if (preg_match('/^get(.*)$/', $name, $matches)) {
            $attribute = $matches[1];
            $attribute_name = $this->attributeHelper->translateToAttributeName($attribute);
            return $this->getData($attribute_name);
        }
        else if (preg_match('/^set(.*)$/', $name, $matches)) {
            $attribute = $matches[1];
            $attribute_name = $this->attributeHelper->translateToAttributeName($attribute);
            return $this->setData($attribute_name, $args[0]);
        }
        else if (preg_match('/^unset(.*)$/', $name, $matches)) {
            $attribute = $matches[1];
            $attribute_name = $this->attributeHelper->translateToAttributeName($attribute);
            return $this->unsetData($attribute_name);
        }
        else if (preg_match('/remove(.*)$/', $name, $matches)) {
            $attribute = $matches[1];
            $attribute_name = $this->attributeHelper->translateToAttributeName($attribute);
            return $this->removeData($attribute_name);
        }
        else {
            throw new Exception("UNDEFINED FUNCTION " . $name);
        }
    }

    public function getData($attribute = null) {
        if ($attribute === null) {
            return $this->data;
        }
        if (!isset($this->data[$attribute])) {
            if (in_array($attribute, $this->attributeHelper->getEntityColumns(AttributeHelper::TYPE_PRODUCT))) {
                $this->data[$attribute] = $this->_getFromEntity($attribute);
            }
            else if ($attribute == 'id') {
                if ($this->id)
                    $this->data['id'] = $this->id;
                else if ($this->data['sku'])
                    $this->data['id'] = $this->attributeHelper->getIdBySku($this->data['sku']);
            }
            else if (in_array($attribute, array_keys($this->attributes))) {
                $this->data[$attribute] = $this->attributes[$attribute]->get($this);
            }
            else {
                $this->data[$attribute] = $this->attributeHelper->getAttribute($this->id, $attribute);
            }
        }
        return $this->data[$attribute];
    }

    public function setData($attribute, $value = NULL) {
        if (is_array($attribute)) {
            foreach ($attribute as $attribute_code => $value) {
                $this->setData($attribute_code, $value);
            }
        }
        else {
            if ($attribute == 'id' || $attribute == 'entity_id')
                $this->id = $value;
            $this->data[$attribute] = $value;
        }
        return $this;
    }
    
    public function unsetData($attribute) {
        if (is_array($attribute)) {
            foreach ($attribute as $attribute_code => $value) {
                $this->unsetData($attribute_code);
            }
        }
        else {
            unset($this->data[$attribute]);
        }
        return $this;
    }

    public function removeData($attribute) {
        $this->unsetData($attribute);
        if (is_array($attribute)) {
            foreach ($attribute as $attribute_code => $value) {
                $this->removeData($attribute_code);
            }
        }
        else {
            $this->attributeHelper->removeAttribute($this->id, $attribute);
        }
        return $this;
    }

    public function save() {
        try {
            $entityData = $this->getEntityData();
            if ($this->id === NULL) {
                // Insert new product
                $this->_isNew = true;
                $data_to_insert = $entityData;
                $data_to_insert['created_at'] = $data_to_insert['updated_at'] = date('Y-m-d H:i:s');
                $this->id = QB::table('catalog_product_entity')->insert($data_to_insert);
            }
            else {
                $this->_isNew = false;
                $data_to_insert = $entityData;
                unset($data_to_insert['entity_id']);
                unset($data_to_insert['created_at']);
                $data_to_insert['updated_at'] = date('Y-m-d H:i:s');
                QB::table('catalog_product_entity')
                    ->where('entity_id', $this->id)
                    ->update($data_to_insert);
            }
            
            foreach ($entityData as $column => $value) {
                unset($this->data[$column]);
            }
            
            foreach ($this->data as $attribute => $value) {
                if (in_array($attribute, array_keys($this->attributes))) {
                    $this->attributes[$attribute]->save($this);
                }
                else {
                    $this->attributeHelper->setAttribute($this->id, $attribute, $value);
                }
            }
            
            /** Let the nested class the realize custom options */
            $this->customOptionsSave();
            
        }
        catch (Exception $e) {
            var_dump("Error: " . $e->getMessage());
        }
    }
    
    abstract protected function customOptionsSave();

    /*
        ['label'] delete where label's equal
        ['path'] delete where path's equal
     */
    public function removeImages($images) {
        foreach ($images as $image) {
            $valueIds = [];
            if (isset($image['path'])) {
                $values = QB::table('catalog_product_entity_media_gallery')
                    ->select('value_id')
                    ->where('entity_id', $this->id)
                    ->whereIn('value', $image['path']);
                $valueIds = ArrayHelper::map(function ($item) {
                    return $item->value_id;
                }, $values->get());
            }
            else if (isset($image['label'])) {
                $subQuery = QB::table('catalog_product_entity_media_gallery')
                    ->select('value_id')
                    ->where('entity_id', $this->id);
                
                $values = QB::table('catalog_product_entity_media_gallery_value')
                    ->select('value_id')
                    ->where(QB::raw('value_id = (' . $subQuery->getQuery()->getRawSql() . ')'))
                    ->whereIn('label', $image['label']);

                $valueIds = ArrayHelper::map(function ($item) {
                    return $item->value_id;
                }, $values->get());
            }
            if (!empty($valueIds)) {
                ArrayHelper::walk(function ($valueId) {
                    QB::table('catalog_product_entity_media_gallery')
                        ->where('value_id', $valueId)
                        ->delete();
                    QB::table('catalog_product_entity_media_gallery_value')
                        ->where('value_id', $valueId)
                        ->delete();
                }, $valueIds);
                if (isset($image['path'])) {
                    $attributes = [
                        'image',
                        'small_image',
                        'thumbnail'
                    ];
                    foreach ($image['path'] as $path) {
                        foreach ($attributes as $attribute) {
                            if ($this->getData($attribute) == $path) {
                                $this->removeData($attribute);
                            }
                        }
                    }
                }
            }
        }
    }
    
    protected function getEntityData() {
        $entityColumns = $this->attributeHelper->getEntityColumns(AttributeHelper::TYPE_PRODUCT);
        $data = [];
        foreach ($this->data as $attribute => $value) {
            if (in_array($attribute, $entityColumns)) {
                $data[$attribute] = $value;
            }
        }
        return $data;
    }

    protected function _getFromEntity($column) {
        $data = QB::table('catalog_product_entity')
            ->select($column)
            ->where('entity_id', $this->id)
            ->first();
        if (!is_null($data))
            return $data->{$column};
        return NULL;
    }
    
    public function isNew() {
        return $this->_isNew;
    }

    public function getRelatedLinkCollection() {
        return ArrayHelper::map(function ($item) {
            return $item->linked_product_id;
        }, QB::table('catalog_product_link')
            ->select('linked_product_id')
            ->where('product_id', $this->id)
            ->where('link_type_id', 1)
            ->get()
        );
    }

    public function getParentRelatedLinkCollection() {
        return ArrayHelper::map(function ($item) {
            return $item->product_id;
        }, QB::table('catalog_product_link')
            ->select('product_id')
            ->where('linked_product_id', $this->id)
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

