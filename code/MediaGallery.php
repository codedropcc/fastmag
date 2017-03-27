<?php

namespace Fastmag;

use Fastmag\Connection;
use Fastmag\AttributeHelper;

/**
 * @codeCoverageIgnore
 */
class MediaGallery {
    protected $conn;
    protected $product;
    protected $data;

    public function __construct($product = null) {
        $this->conn = Connection::getInstance();
        $this->data = [];
        $this->setProduct($product);
    }

    public function setProduct($product) {
        if ($product !== null) {
            $this->product = $product;
            $prefix = $this->conn->getPrefix();
            $sql = "
                SELECT * FROM {$prefix}catalog_product_entity_media_gallery
                WHERE entity_id = {$this->product->getId()}
            ";
            $data = $this->conn->fetchAll($sql);
            if (!empty($data)) {
                $data = array_shift($data);
                $valueSql = "
                    SELECT * FROM {$prefix}catalog_product_entity_media_gallery_value
                    WHERE value_id = {$data['value_id']}
                ";
                $data['value_data'] = $this->conn->fetchAll($valueSql);
            }
            if (!empty($data)) {
                $this->data = $data;
            }
        }
    }

    public function getData() {
        return $this->data;
    }

    public function __call($name, $args) {
        if (preg_match('/^get(.*)$/', $name, $matches)) {
            $attribute = $matches[1];
            $attribute = AttributeHelper::getInstance()->translateToAttributeName($attribute);
            if (isset($this->data[$attribute]))
                return $this->data[$attribute];
            else if (isset($this->data['value_data'][$attribute])) {
                return $this->data['value_data'][$attribute];
            }
        }
        else {
            throw new Exception("UNDEFINED FUNCTION " . $name);
        }
    }
}
