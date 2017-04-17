<?php

namespace Fastmag\Product;

use Fastmag;
use Fastmag\Exception;
use Fastmag\Connection;
use Fastmag\QB;

use Fastmag\Product\ProductAbstract;
use Fastmag\Product\Simple;
use Fastmag\Product\Bundle;
use Fastmag\Product\Virtual;
use Fastmag\Product\Grouped;
use Fastmag\Product\Downloadable;
use Fastmag\Product\Configurable;

class Factory
{
    /**
     * @param $data
     * @return ProductAbstract
     * @throws Exception
     */
    public static function create($data) {
        $typeId = null;
        $id = null;
        $setData = false;
        // first of all, let's check if it's int or string, probably it's id
        if (is_int($data) || is_string($data)) {
            $typeId = self::_getProductTypeId($data);
        }
        // if not, let's check, if it's array and has id | sku
        else if (is_array($data)) {
            if ($id = self::getId($data)) {
                $typeId = self::_getProductTypeId($id);
            }
            else if (isset($data['sku'])) {
                $id = self::_getProductIdBySku($data['sku']);
                $typeId = self::_getProductTypeId($id);
            }
            // If nothing of it was found, let's create empty product
            if (($typeId === null || $typeId === false) && isset($data['type_id'])) {
                $typeId = $data['type_id'];
            }
            $setData = true;
        }
        if ($typeId === null || $typeId === false) {
            throw new Exception('No have data for creation product');
        }
        $possibleProducts = [
            'simple',
            'bundle',
            'configurable',
            'grouped',
            'virtual',
            'downloadable',
        ];
        if (!in_array($typeId, $possibleProducts)) {
            throw new Exception("Unknown product type: {$typeId}.");
        }
        $typeId = ucfirst($typeId);
        $product = Fastmag\Fastmag::getInstance()->getModel('Fastmag\Product\\' . $typeId);

        if ($id)
            $product->load($id);

        if ($setData)
            $product->setData($data);
        else
            $product->load($data);

        return $product;
    }

    protected static function getId($data) {
        if (isset($data['entity_id']))
            return $data['entity_id'];
        else if (isset($data['id']))
            return $data['id'];
        return false;
    }

    public static function createBySku($sku) {
        return self::create(self::_getProductIdBySku($sku));
    }

    private static function _getProductTypeId($id) {
        $type_id = QB::table('catalog_product_entity')
            ->select('type_id')
            ->where('entity_id', $id)
            ->first();
        if (!is_null($type_id)) {
            return $type_id->type_id;
        }
        return NULL;
    }

    private static function _getProductIdBySku($sku) {
        $id = QB::table('catalog_product_entity')
            ->select('entity_id')
            ->where('sku', $sku)
            ->first();
        if (!is_null($id)) {
            return $id->entity_id;
        }
        return NULL;
    }
}
