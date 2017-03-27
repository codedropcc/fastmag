<?php

namespace Fastmag\Product\Attribute;

use Fastmag\Product\ProductAbstract;
use Fastmag\QB;
use Fastmag\Exception;
use Fastmag\ArrayHelper;
use Fastmag\AttributeHelper;

class Image implements AttributeAbstract {
    protected $attributeHelper;

    public function __construct() {
        $this->attributeHelper = AttributeHelper::getInstance();
    }
    
    public function save(ProductAbstract $product) {
        $imagesDir = $product->getBaseDir() . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . 'catalog' . DIRECTORY_SEPARATOR . 'product';
        $params = $product->getData($this->getAttributeCode());

        $countOfImages = count($this->get($product));
        $attributeIds = [
            'media_gallery' => $this->attributeHelper->getAttributeIdByCode('media_gallery'),
            'image' => $this->attributeHelper->getAttributeIdByCode('image'),
            'small_image' => $this->attributeHelper->getAttributeIdByCode('small_image'),
            'thumbnail' => $this->attributeHelper->getAttributeIdByCode('thumbnail'),
        ];
        $mediaPath = $product->getBaseDir()
            . DIRECTORY_SEPARATOR . 'media'
            . DIRECTORY_SEPARATOR . 'catalog'
            . DIRECTORY_SEPARATOR . 'product';

        foreach ($params as $key => $imageInfo) {
            if ($key == '') continue;

            if (substr($key, 0, 1) == '/' && file_exists($mediaPath . $key)) {
                $filename = $mediaPath . $key;
            }
            else if (substr($key, 0, 1) == '/' && file_exists($key)) {
                $filename = $key;
            }
            else if (substr($key, 0, 4) == 'http') {
                $filecontent = file_get_contents($key);
                if ($filecontent !== "" && $filecontent !== false) {
                    $path_info = explode('/', $key);
                    $filename = $product->getBaseDir()
                        . DIRECTORY_SEPARATOR . 'tmp'
                        . DIRECTORY_SEPARATOR . $path_info[count($path_info)-1];
                    $dir_to_create = $product->getBaseDir() . DIRECTORY_SEPARATOR . 'tmp';
                    if (!is_dir($dir_to_create)) {
                        if (!mkdir($dir_to_create, 0777, true)) {
                            throw new Exception("Cannot create TMP folder for images");
                        }
                    }
                    file_put_contents($filename, $filecontent);
                }
                else {
                    throw new Exception('Cannot download picture: ' . $key);
                }
            }
            else {
                throw new Exception('Unknown image ' . $key);
            }

            if ($filename && file_exists($filename)) {
                $path_info = explode('/', $filename);

                $file = $path_info[count($path_info) - 1];

                $image_path = DIRECTORY_SEPARATOR . substr($file, 0, 1) . DIRECTORY_SEPARATOR . substr($file, 1, 1) . DIRECTORY_SEPARATOR . $file;

                $full_image_path = $imagesDir . $image_path;

                $dirs_to_create = [
                    $imagesDir . DIRECTORY_SEPARATOR . substr($file, 0, 1) . DIRECTORY_SEPARATOR . substr($file, 1, 1),
                ];
                
                foreach ($dirs_to_create as $dir) {
                    if (!is_dir($dir)) {
                        if (!mkdir($dir, 0777, true)) {
                            throw new Exception("Cannot create dir: " . $dir);
                        }
                    }
                }

                if (!file_exists($full_image_path) && !copy($filename, $full_image_path)) {
                    throw new Exception("Cannot move file!");
                }

                /**
                 * Before already saving values in media_gallery, we should check what exists there
                 */
                $data = QB::table('catalog_product_entity_media_gallery')
                    ->where('entity_id', $product->id)
                    ->where('attribute_id', $attributeIds['media_gallery'])
                    ->where('value', $image_path)
                    ->first();
                if (is_null($data)) {
                    $position = $countOfImages++;
                    
                    $gallery_value_id = QB::table('catalog_product_entity_media_gallery')
                        ->insert([
                            'attribute_id' => $attributeIds['media_gallery'],
                            'entity_id' => $product->id,
                            'value' => $image_path
                        ]);
                    
                    if (!$gallery_value_id) {
                        throw new Exception("Cannot save image in media gallery!");
                    }
                    
                    QB::table('catalog_product_entity_media_gallery_value')
                        ->insert([
                            'value_id' => $gallery_value_id,
                            'store_id' => 0,
                            'label' => '',
                            'position' => $position,
                            'disabled' => 0
                        ]);
                }
                foreach ($imageInfo as $code) {
                    $this->attributeHelper->setAttribute($product->id, $code, $image_path);
                }
            }
        }

    }

    public function get(ProductAbstract $product) {
        $attr = $this->attributeHelper->getAttributeIdByCode('media_gallery');
        return (array)ArrayHelper::map(function ($item) {
            return (array)$item;
        }, QB::table(['catalog_product_entity_media_gallery' => 'g'])
            ->select([
                'g.value_id',
                'g.value',
                'label',
                'position',
                'disabled',
                'store_id'
            ])
            ->leftJoin(
                ['catalog_product_entity_media_gallery_value', 'gv'],
                'gv.value_id',
                '=',
                'g.value_id'
            )
            ->where('g.attribute_id', $attr)
            ->where('g.entity_id', $product->id)
            ->get()
        );
    }

    public function getAttributeCode() {
        return 'images';
    }
}
