<?php

use Fastmag\Exception;
use Fastmag\AttributeHelper;
use Fastmag\ArrayHelper;
use Fastmag\Connection;

use Fastmag\Product\Api;
use Fastmag\Product\Factory;
use Fastmag\Product\Collection;

use Fastmag\Product\Simple;
use Fastmag\Product\Bundle;
use Fastmag\Product\Virtual;
use Fastmag\Product\Grouped;
use Fastmag\Product\Downloadable;
use Fastmag\Product\Configurable;

use PHPUnit\Framework\TestCase;

use Fastmag\QB;

class ProductTest extends TestCase {
    /** @var Fastmag\Product\Api $api */
    protected static $api = null;
    /** @var Fastmag\AttributeHelper $helper */
    protected static $helper = null;
    /** @var \Fastmag\Fastmag */
    protected static $instance = null;
    /** @var \Fastmag\Product\Factory */
    protected static $factory = null;
    protected static $connection = null;

    protected $colors = [
        'white' => 3,
        'black' => 4,
    ];
    
    public static function setUpBeforeClass() {
        /** @var Fastmag\Connection $connection */
        self::$instance = Fastmag\Fastmag::getInstance();
        self::$connection = self::$instance->getModel('Fastmag\Connection');
        QB::query('SET foreign_key_checks = 0');
        $prefix = self::$connection->getPrefix();
        $tables = [
            'catalog_product_entity',
            'catalog_product_entity_int',
            'catalog_product_entity_varchar',
            'catalog_product_entity_text',
            'catalog_product_entity_decimal',
            'catalog_product_entity_datetime',
            'catalog_product_entity_gallery',
            'catalog_product_entity_group_price',
            'catalog_product_entity_tier_price',
            'catalog_product_entity_media_gallery',
            'catalog_product_entity_media_gallery_value',
            /** Bundle related */
            'catalog_product_bundle_option',
            'catalog_product_bundle_option_value',
            'catalog_product_bundle_selection',
            /** Configurable related */
            'catalog_product_super_link',
            'catalog_product_super_attribute',
            'catalog_product_super_attribute_label',
            /** Stock data */
            'cataloginventory_stock_item',
            /** Relation stuff */
            'catalog_product_link',
            'catalog_product_relation',
            'catalog_category_product',
            'catalog_product_website',
        ];
        foreach ($tables as $table) {
            $sql = "TRUNCATE {$prefix}{$table}";
            QB::query($sql);
        }
        QB::query('SET foreign_key_checks = 1');
        
        // Removing tmp and media folders
        /** @var Fastmag\Product\ProductAbstract $simple */
        self::$factory = self::$instance->getModel('Fastmag\Product\Factory');
        $simple = self::$factory->create(['type_id' => 'simple']);
        self::rmDir($simple->getBaseDir() . DIRECTORY_SEPARATOR . 'media');
        self::rmDir($simple->getBaseDir() . DIRECTORY_SEPARATOR . 'tmp');
        self::$api = self::$instance->getModel('Fastmag\Product\Api');
        self::$helper = self::$instance->getModel('Fastmag\AttributeHelper');
    }

    protected static function rmDir($path) {
        if (is_file($path)) {
            unlink($path);
        }
        else if (is_dir($path)) {
            array_map([__CLASS__, 'rmDir'], glob($path . '/*'));
            rmdir($path);
        }
    }

    /**
     * @return Simple
     */
    public function testCreateSimpleProduct() {
        $data = [
            'type_id' => 'simple',
            'website_ids' => [1],
            'category_ids' => [2],
            'attribute_set_id' => 1,
            'store_id' => 0,
            'created_at' => strtotime('now'),
            'updated_at' => strtotime('now'),
            'name' => 'Test Simple',
            'description' => 'Test Simple Description',
            'short_description' => 'Test Simple Short Description',
            'meta_title' => 'Test Simple Meta Title',
            'meta_description' => 'Test Simple Meta Description',
            'meta_keyword' => 'Test Simple Meta Keyword',
            'url_path' => 'test-simple.html',
            'url_key' => 'test-simple',
            'sku' => 'test-simple',
            'images' => [
                __DIR__ . '/image/test_product_image.png' => ['image', 'small_image', 'thumbnail']
            ],
            'tier_price' => [
                '0.7500' => 2,
                '0.5000' => 5,
                '0.2500' => 10
            ],
            'price' => 1.0000,
            'status' => 1,
            'visibility' => 4,
            'weight' => 1.0000,
            'entity_type_id' => 4,
            'tax_class_id' => 7,
            'qty' => 100,
            'stock_data' => [
                'stock_id' => 1,
                'use_config_manage_stock' => 0,
                'manage_stock' => 1,
                'min_sale_qty' => 1,
                'is_in_stock' => 1,
                'qty' => 100,
                'backorders' => 1,
                'use_config_backorders' => 0
            ],
        ];
        /** @var \Fastmag\Product\Simple $product */
        $product = self::$factory->create($data);
        $product->save();
        $this->assertNotNull($product->getId());
        $this->assertInstanceOf('\Fastmag\Product\Simple', $product);
        $this->assertEquals(self::$helper->getSkuById($product->getId()), 'test-simple');
        return $product;
    }

    /**
     * @param Fastmag\Product\Simple $simple
     * @depends testCreateSimpleProduct
     * @return Fastmag\Product\Simple
     */
    public function testCreateSimpleTheSecondTimeNotActuallyCreatingProductTwice(Simple $simple) {
        $data = [
            'entity_id' => $simple->getId(),
            'type_id' => 'simple',
            'website_ids' => [1],
            'category_ids' => [2],
            'attribute_set_id' => 1,
            'store_id' => 0,
            'created_at' => strtotime('now'),
            'updated_at' => strtotime('now'),
            'name' => 'Test Simple',
            'description' => 'Test Simple Description',
            'short_description' => 'Test Simple Short Description',
            'meta_title' => 'Test Simple Meta Title',
            'meta_description' => 'Test Simple Meta Description',
            'meta_keyword' => 'Test Simple Meta Keyword',
            'url_path' => 'test-simple.html',
            'url_key' => 'test-simple',
            'sku' => 'test-simple',
            'images' => [
                __DIR__ . '/image/test_product_image.png' => ['image', 'small_image', 'thumbnail']
            ],
            'tier_price' => '2:0.7500;5:0.5000;10:0.2500',
            'price' => 1.0000,
            'status' => 1,
            'visibility' => 4,
            'weight' => 1.0000,
            'entity_type_id' => 4,
            'tax_class_id' => 7,
            'qty' => 100,
            'stock_data' => [
                'stock_id' => 1,
                'use_config_manage_stock' => 0,
                'manage_stock' => 1,
                'min_sale_qty' => 1,
                'is_in_stock' => 1,
                'qty' => 100,
                'backorders' => 1,
                'use_config_backorders' => 0
            ],
        ];
        /** @var \Fastmag\Product\Simple $product */
        $product = self::$factory->create($data);
        $product->save();
        $this->assertEquals($product->getId(), $simple->getId());
        return $product;
    }

    /**
     * @param Simple $simple
     * @depends testCreateSimpleProduct
     */
    public function testGetImageUrl(Simple $simple) {
        $this->assertEquals($simple->getImageUrl(), $simple->getBaseUrl() . '/media/catalog/product/t/e/test_product_image.png');
    }
    
    /**
     * @param Simple $simple
     * @depends testCreateSimpleProduct
     */
    public function testGetImagePath(Simple $simple) {
        $this->assertEquals($simple->getImagePath(), 'catalog/product/t/e/test_product_image.png');
    }

    /**
     * @param Simple $simple
     * @depends testCreateSimpleProduct
     */
    public function testGetNonExistingAttribute(Simple $simple) {
        $this->assertNull($simple->getNonExistingAttribute());
    }

    /**
     * @depends testCreateSimpleProduct
     */
    public function testGetCategoryIds() {
        $simple = self::$factory->createBySku('test-simple');
        $this->assertEquals($simple->getCategoryIds(), [2]);
    }

    /**
     * @depends testCreateSimpleProduct
     */
    public function testGetWebsiteIds() {
        $simple = self::$factory->createBySku('test-simple');
        $this->assertEquals($simple->getWebsiteIds(), [1]);
    }

    /**
     * @depends testCreateSimpleProduct
     */
    public function testGetTierPrice() {
        $simple = self::$factory->createBySku('test-simple');
        $this->assertEquals($simple->getTierPrice(), [
            [
                'qty' => 2,
                'price' => 0.7500
            ],
            [
                'qty' => 5,
                'price' => 0.5000
            ],
            [
                'qty' => 10,
                'price' => 0.2500
            ],
        ]);
        $this->assertEquals($simple->getFormatedTierPrice(), [
            [
                'price_qty' => 2,
                'price' => 0.7500
            ],
            [
                'price_qty' => 5,
                'price' => 0.5000
            ],
            [
                'price_qty' => 10,
                'price' => 0.2500
            ],
        ]);
    }

    /**
     * @depends testCreateSimpleProduct
     */
    public function testGetStockData() {
        $simple = self::$factory->createBySku('test-simple');
        $stockData = $simple->getStockData()[0];
        $this->assertEquals($stockData['qty'], 100.0000);
        $this->assertEquals($stockData['is_in_stock'], 1);
    }

    /**
     * @param Simple $simple
     * @depends testCreateSimpleProduct
     */
    public function testIsProductNew(Simple $simple) {
        $product = self::$factory->create($simple->getId());
        $this->assertEquals($product->isNew(), false);
        
        $product = self::$factory->create(['type_id' => 'simple']);
        $this->assertEquals($product->isNew(), true);
    }

    /**
     * @depends testCreateSimpleProduct
     */
    public function testGetImages() {
        $simple = self::$factory->createBySku('test-simple');
        $image = $simple->getImages()[0]['value'];
        $this->assertEquals($image, '/t/e/test_product_image.png');
    }
    
    /**
     * @depends testCreateSimpleProduct
     */
    public function testIsSaleable() {
        $simple = self::$factory->createBySku('test-simple');
        $this->assertEquals($simple->isSaleable(), true);
        $simple->setStatus(2);
        $this->assertEquals($simple->isSaleable(), false);
    }

    public function testCreateSimpleProductWithImageOverInternet() {
        $data = [
            'type_id' => 'simple',
            'website_ids' => [1],
            'category_ids' => [2],
            'attribute_set_id' => 1,
            'store_id' => 0,
            'created_at' => strtotime('now'),
            'updated_at' => strtotime('now'),
            'name' => 'Test Simple Product With Image Over Internet',
            'description' => 'Test Simple Internet Image Description',
            'short_description' => 'Test Simple Internet Image Short Description',
            'meta_title' => 'Test Simple Internet Image Meta Title',
            'meta_description' => 'Test Simple Internet Image Meta Description',
            'meta_keyword' => 'Test Simple Internet Image Meta Keyword',
            'url_path' => 'test-simple-internet-image.html',
            'url_key' => 'test-simple-internet-image',
            'sku' => 'test-simple-internet-image',
            'images' => [
                'https://www.dropbox.com/s/ao5mkzrnq93aik0/test_product_image.png?dl=0' => ['image', 'small_image', 'thumbnail']
            ],
            'tier_price' => [
                '0.7500' => 2,
                '0.5000' => 5,
                '0.2500' => 10
            ],
            'price' => 1.0000,
            'status' => 1,
            'visibility' => 4,
            'weight' => 1.0000,
            'entity_type_id' => 4,
            'tax_class_id' => 7,
            'qty' => 100,
            'stock_data' => [
                'stock_id' => 1,
                'use_config_manage_stock' => 0,
                'manage_stock' => 1,
                'min_sale_qty' => 1,
                'is_in_stock' => 1,
                'qty' => 100,
                'backorders' => 1,
                'use_config_backorders' => 0
            ],
        ];
        /** @var \Fastmag\Product\Simple $product */
        $product = self::$factory->create($data);
        $product->save();
        $this->assertNotNull($product->getId());
        $this->assertInstanceOf('\Fastmag\Product\Simple', $product);
        $this->assertEquals(self::$helper->getSkuById($product->getId()), 'test-simple-internet-image');
        return $product;
    }

    /**
     * @param Simple $simple
     * @afterClass
     * @depends testCreateSimpleProduct
     */
    public function testRemoveAllImagesByPath(Simple $simple) {
        $simple->removeImages([
            [
                'path' => ['/t/e/test_product_image.png']
            ]
        ]);
        $simple = self::$factory->create($simple->getId());
        $this->assertEquals($simple->getImages(), []);
        $this->assertEquals($simple->getImagePath(), false);
        $this->assertEquals($simple->getImageUrl(), false);
    }
    
    /**
     * @param Simple $simple
     * @afterClass
     * @depends testCreateSimpleProductWithImageOverInternet
     */
    public function testRemoveAllImagesByLabel(Simple $simple) {
        $simple->removeImages([
            [
                'label' => ['']
            ]
        ]);
        $simple = self::$factory->create($simple->getId());
        $this->assertEquals($simple->getImages(), []);
    }

    /**
     * @param Simple $simple
     * @depends testCreateSimpleProduct
     * @return Bundle
     */
    public function testCreateBundleProduct(Simple $simple) {
        $productData = [
            'type_id' => 'bundle',
            'website_ids' => [1],
            'attribute_set_id' => 1,
            'entity_type_id' => 4,
            'sku_type' => 1,
            'name' => 'Test Bundle',
            'sku' => 'test-bundle',
            'price' => 2.0000,
            'weight_type' => 0,
            'shipment_type' => 0,
            'url_path' => 'test-bundle.html',
            'url_key' => 'test-bundle',
            'status' => 1,
            'visibility' => 4,
            'price_type' => 1,
            'price_view' => 0,
            'tax_class_id' => 7,
            'description' => 'Test Bundle Description',
            'short_description' => 'Test Bundle Short Description',
            'meta_title' => 'Test Bundle Meta Title',
            'meta_description' => 'Test Bundle Meta Description',
            'meta_keyword' => 'Test Bundle Meta Keyword',
            'category_ids' => [2],
            'weight' => 2.0000,
            'stock_data' => [
                'stock_id' => 1,
                'use_config_manage_stock' => 0,
                'manage_stock' => 0,
                'is_in_stock' => 1,
                'qty' => 0,
            ],
            'images' => [
                __DIR__ . '/image/test_product_image.png' => ['image', 'small_image']
            ],
        ];
        $selections = [
            '0' => [
                '0' => [
                    'product_id' => $simple->getId(),
                    'selection_qty' => 2,
                    'selection_price_value' => 0,
                    'selection_price_type' => 0,
                    'selection_can_change_qty' => 0,
                    'position' => 0,
                    'is_default' => 1,
                ]
            ]
        ];
        $flags = [
            'setCanSaveCustomOptions' => true,
            'setCanSaveBundleSelections' => true,
            'setAffectBundleProductSelections' => true,
        ];
        $options = [
            '0' => [
                'title' => 'Bundle Options',
                'option_id' => '',
                'delete' => '',
                'type' => 'select',
                'required' => '1',
                'position' => '1',
            ]
        ];
        $data = [
            'bundle' => $productData,
            'options' => $options,
            'flags' => $flags,
            'selections' => $selections,
        ];
        /** @var \Fastmag\Product\Bundle $product */
        $product = self::$factory->create($data['bundle']);
        $product->setData('options', $options);
        $product->setData('selections', $selections);
        $product->save();
        $this->assertInstanceOf('\Fastmag\Product\Bundle', $product);
        $this->assertNotNull($product->getId());
        return $product;
    }

    /**
     * @param Bundle $bundle
     * @depends testCreateBundleProduct
     * @return Bundle
     */
    public function testCreateRelationBetweenBundleAndSimple(Bundle $bundle) {
        $simples = $bundle->getBundleItems();
        foreach ($simples as $simple) {
            self::$api->createRelation($bundle->getId(), $simple->getId());
            self::$api->createRelation($simple->getId(), $bundle->getId());
        }
        return $bundle;
    }

    /**
     * @param Bundle $bundle
     * @depends testCreateRelationBetweenBundleAndSimple
     */
    public function testGetRelationsFromProduct(Bundle $bundle) {
        /** @var Bundle $product */
        $product = self::$factory->create($bundle->getId());
        $relationIds = $product->getRelatedLinkCollection();
        $selectionIds = ArrayHelper::map(
            function ($item) {
                return $item->getId();
            },
            $product->getBundleItems()
        );
        $this->assertEquals($relationIds, $selectionIds);
    }

    public function testCreateSimpleForConfigurable() {
        /** @var AttributeHelper $attributeHelper */
        $attributeHelper = Fastmag\Fastmag::getInstance()->getModel('Fastmag\AttributeHelper');
        $data = [
            'type_id' => 'simple',
            'website_ids' => [1],
            'category_ids' => [2],
            'attribute_set_id' => 9,
            'store_id' => 0,
            'created_at' => strtotime('now'),
            'updated_at' => strtotime('now'),
            'name' => 'Test Simple For Configurable',
            'description' => 'Test Simple For Configurable Description',
            'short_description' => 'Test Simple For Configurable Short Description',
            'meta_title' => 'Test Simple For Configurable Meta Title',
            'meta_description' => 'Test Simple For Configurable Meta Description',
            'meta_keyword' => 'Test Simple For Configurable Meta Keyword',
            'url_path' => 'test-simple-for-configurable.html',
            'url_key' => 'test-simple-for-configurable',
            'sku' => 'test-simple-for-configurable',
            'images' => [
                __DIR__ . '/image/test_product_image.png' => ['image', 'small_image', 'thumbnail']
            ],
            'tier_price' => [
                '0.7500' => 2,
                '0.5000' => 5,
                '0.2500' => 10
            ],
            'price' => 1.0000,
            'status' => 1,
            'visibility' => 4,
            'weight' => 1.0000,
            'entity_type_id' => 4,
            'tax_class_id' => 7,
            'qty' => 100,
            'stock_data' => [
                'stock_id' => 1,
                'use_config_manage_stock' => 0,
                'manage_stock' => 1,
                'min_sale_qty' => 1,
                'is_in_stock' => 1,
                'qty' => 100,
                'backorders' => 1,
                'use_config_backorders' => 0
            ],
            'color' => $attributeHelper->getAttributeOptionValue('color', 'black'),
        ];
        /** @var \Fastmag\Product\Simple $product */
        $product = self::$factory->create($data);
        $product->save();
        $this->assertNotNull($product->getId());
        $this->assertInstanceOf('\Fastmag\Product\Simple', $product);
        return $product;
    }
    
    public function testCreateSecondSimpleForConfigurable() {
        /** @var AttributeHelper $attributeHelper */
        $attributeHelper = Fastmag\Fastmag::getInstance()->getModel('Fastmag\AttributeHelper');
        $data = [
            'type_id' => 'simple',
            'website_ids' => [1],
            'category_ids' => [2],
            'attribute_set_id' => 9,
            'store_id' => 0,
            'created_at' => strtotime('now'),
            'updated_at' => strtotime('now'),
            'name' => 'Test Simple 2 For Configurable',
            'description' => 'Test Simple 2 For Configurable Description',
            'short_description' => 'Test Simple 2 For Configurable Short Description',
            'meta_title' => 'Test Simple 2 For Configurable Meta Title',
            'meta_description' => 'Test Simple 2 For Configurable Meta Description',
            'meta_keyword' => 'Test Simple 2 For Configurable Meta Keyword',
            'url_path' => 'test-simple-2-for-configurable.html',
            'url_key' => 'test-simple-2-for-configurable',
            'sku' => 'test-simple-2-for-configurable',
            'images' => [
                __DIR__ . '/image/test_product_image.png' => ['image', 'small_image', 'thumbnail']
            ],
            'tier_price' => [
                '0.7500' => 2,
                '0.5000' => 5,
                '0.2500' => 10
            ],
            'price' => 1.0000,
            'status' => 1,
            'visibility' => 4,
            'weight' => 1.0000,
            'entity_type_id' => 4,
            'tax_class_id' => 7,
            'qty' => 100,
            'stock_data' => [
                'stock_id' => 1,
                'use_config_manage_stock' => 0,
                'manage_stock' => 1,
                'min_sale_qty' => 1,
                'is_in_stock' => 1,
                'qty' => 100,
                'backorders' => 1,
                'use_config_backorders' => 0
            ],
            'color' => $attributeHelper->getAttributeOptionValue('color', 'white'),
        ];
        /** @var \Fastmag\Product\Simple $product */
        $product = self::$factory->create($data);
        $product->save();
        $this->assertNotNull($product->getId());
        $this->assertInstanceOf('\Fastmag\Product\Simple', $product);
        return $product;
    }

    /**
     * @param Simple $simple
     * @depends testCreateSimpleForConfigurable
     * @return Configurable
     */
    public function testCreateConfigurableProduct(Simple $simple) {
        /** @var AttributeHelper $attributeHelper */
        $attributeHelper = Fastmag\Fastmag::getInstance()->getModel('Fastmag\AttributeHelper');
        $configurableAttributes = [
            'color' => $attributeHelper->getAttributeIdByCode('color')
        ];
        $configData = [
            'website_ids' => 1,
            'entity_type_id' => 4,
            'attribute_set_id' => 9,
            'type_id' => 'configurable',
            'sku' => 'test-configurable',
            'url_key' => 'test_configurable',
            'url_path' => 'test_configurable.html',
            'name' => 'Test Configurable',
            'description' => 'Test Configurable Description',
            'short_description' => 'Test Configurable Short Description',
            'meta_title' => 'Test Configurable Meta Title',
            'meta_description' => 'Test Configurable Meta Description',
            'meta_keyword' => 'Test Configurable Meta Keyword',
            'tax_class_id' => 7,
            'status' => 1,
            'visibility' => 4,
            'price' => 0,
            'stock_data' => [
                'stock_id' => 1,
                'use_config_manage_stock' => 0,
                'manage_stock' => 0,
                'qty' => 100,
                'is_in_stock' => 1,
            ],
            'category_ids' => 2,
            'configurable_attributes_data' => $configurableAttributes,
            'images' => [
                $simple->getData('image') => ['image', 'small_image', 'thumbnail'],
            ],
        ];

        foreach ($configurableAttributes as $attribute => $attributeId) {
            $configData['configurable_products_data'][$simple->getId()][] = [
                'label' => $attributeHelper->getAttributeOptionValue('color', $simple->getData($attribute)),
                'attribute_id' => $attributeId,
                'value_index' => $simple->getData($attribute),
                'is_percent' => 0,
            ];
        }

        /** @var \Fastmag\Product\Configurable $product */
        $product = self::$factory->create($configData);
        $product->save();
        
        $this->assertNotNull($product);
        $this->assertInstanceOf('\Fastmag\Product\Configurable', $product);
        
        return $product;
    }

    /**
     * @depends testCreateConfigurableProduct
     */
    public function testCheckThatProductsWasAssignedRightToConfigurable(Configurable $config) {
        $product = self::$factory->create($config->getId());

        $this->assertEquals(count($product->getRelationIds()), 1);
    }

    /**
     * @depends testCreateConfigurableProduct
     * @depends testCreateSecondSimpleForConfigurable
     */
    public function testResaveAttributesAndAddProduct(Configurable $config, Simple $simple) {
        $attributeHelper = Fastmag\Fastmag::getInstance()->getModel('Fastmag\AttributeHelper');
        $attributeId = $attributeHelper->getAttributeIdByCode('color');

        $configData = [
            'configurable_products_data' => [
                $simple->getId() => []
            ]
        ];
        $configData['configurable_products_data'][$simple->getId()][] = [
            'label' => $attributeHelper->getAttributeOptionValue('color', $simple->getData('color')),
            'attribute_id' => $attributeId,
            'value_index' => $simple->getData('color'),
            'is_percent' => 0,
        ];

        $config->setData($configData);
        $config->save();

        $this->assertEquals(count($config->getRelationIds()), 1);
    }

    /**
     * @return Grouped
     */
    public function testCreateGroupedProduct() {
        $data = [
            'type_id' => 'grouped',
            'website_ids' => [1],
            'category_ids' => [2],
            'attribute_set_id' => 1,
            'store_id' => 0,
            'created_at' => strtotime('now'),
            'updated_at' => strtotime('now'),
            'name' => 'Test Grouped',
            'description' => 'Test Grouped Description',
            'short_description' => 'Test Grouped Short Description',
            'meta_title' => 'Test Grouped Meta Title',
            'meta_description' => 'Test Grouped Meta Description',
            'meta_keyword' => 'Test Grouped Meta Keyword',
            'url_path' => 'test-grouped.html',
            'url_key' => 'test-grouped',
            'sku' => 'test-grouped',
            'images' => [
                __DIR__ . '/image/test_product_image.png' => ['image', 'small_image', 'thumbnail']
            ],
            'tier_price' => [
                '0.7500' => 2,
                '0.5000' => 5,
                '0.2500' => 10
            ],
            'price' => 1.0000,
            'status' => 1,
            'visibility' => 4,
            'weight' => 1.0000,
            'entity_type_id' => 4,
            'tax_class_id' => 7,
            'qty' => 100,
            'stock_data' => [
                'stock_id' => 1,
                'use_config_manage_stock' => 0,
                'manage_stock' => 1,
                'min_sale_qty' => 1,
                'is_in_stock' => 1,
                'qty' => 100,
                'backorders' => 1,
                'use_config_backorders' => 0
            ],
        ];
        /** @var \Fastmag\Product\Grouped $product */
        $product = self::$factory->create($data);
        $product->save();
        
        $this->assertNotNull($product);
        $this->assertInstanceOf('\Fastmag\Product\Grouped', $product);
        
        return $product;
    }
    
    public function testGetExceptionWhenCreatingSimpleProductWithoutData() {
        $this->expectException(Exception::class);
        self::$factory->create([])->save();
    }
    
    public function testGetExceptionWhenCreatingBundleProductWithoutData() {
        $this->expectException(Exception::class);
        self::$factory->create([])->save();
    }
    public function testGetExceptionWhenCreatingConfigurableProductWithoutData() {
        $this->expectException(Exception::class);
        self::$factory->create([])->save();
    }
    public function testGetExceptionWhenCreatingGroupedProductWithoutData() {
        $this->expectException(Exception::class);
        self::$factory->create([])->save();
    }
    public function testGetExceptionWhenCreatingVirtualProductWithoutData() {
        $this->expectException(Exception::class);
        self::$factory->create([])->save();
    }
    public function testGetExceptionWhenCreatingDownloadableProductWithoutData() {
        $this->expectException(Exception::class);
        self::$factory->create([])->save();
    }

    /**
     * @return Downloadable
     */
    public function testCreateDownloadableProduct() {
        $data = [
            'type_id' => 'downloadable',
            'website_ids' => [1],
            'category_ids' => [2],
            'attribute_set_id' => 1,
            'store_id' => 0,
            'created_at' => strtotime('now'),
            'updated_at' => strtotime('now'),
            'name' => 'Test Downloadable',
            'description' => 'Test Downloadable Description',
            'short_description' => 'Test Downloadable Short Description',
            'meta_title' => 'Test Downloadable Meta Title',
            'meta_description' => 'Test Downloadable Meta Description',
            'meta_keyword' => 'Test Downloadable Meta Keyword',
            'url_path' => 'test-downloadable.html',
            'url_key' => 'test-downloadable',
            'sku' => 'test-downloadable',
            'images' => [
                __DIR__ . '/image/test_product_image.png' => ['image', 'small_image', 'thumbnail']
            ],
            'tier_price' => [
                '0.7500' => 2,
                '0.5000' => 5,
                '0.2500' => 10
            ],
            'price' => 1.0000,
            'status' => 1,
            'visibility' => 4,
            'weight' => 1.0000,
            'entity_type_id' => 4,
            'tax_class_id' => 7,
            'qty' => 100,
            'stock_data' => [
                'stock_id' => 1,
                'use_config_manage_stock' => 0,
                'manage_stock' => 1,
                'min_sale_qty' => 1,
                'is_in_stock' => 1,
                'qty' => 100,
                'backorders' => 1,
                'use_config_backorders' => 0
            ],
        ];
        /** @var \Fastmag\Product\Downloadable $product */
        $product = self::$factory->create($data);
        $product->save();

        $this->assertNotNull($product);
        $this->assertInstanceOf('\Fastmag\Product\Downloadable', $product);

        return $product;
    }

    /**
     * @return Virtual
     */
    public function testCreateVirtualProduct() {
        $data = [
            'type_id' => 'virtual',
            'website_ids' => [1],
            'category_ids' => [2],
            'attribute_set_id' => 1,
            'store_id' => 0,
            'created_at' => strtotime('now'),
            'updated_at' => strtotime('now'),
            'name' => 'Test Virtual',
            'description' => 'Test Virtual Description',
            'short_description' => 'Test Virtual Short Description',
            'meta_title' => 'Test Virtual Meta Title',
            'meta_description' => 'Test Virtual Meta Description',
            'meta_keyword' => 'Test Virtual Meta Keyword',
            'url_path' => 'test-virtual.html',
            'url_key' => 'test-virtual',
            'sku' => 'test-virtual',
            'images' => [
                __DIR__ . '/image/test_product_image.png' => ['image', 'small_image', 'thumbnail']
            ],
            'tier_price' => [
                '0.7500' => 2,
                '0.5000' => 5,
                '0.2500' => 10
            ],
            'price' => 1.0000,
            'status' => 1,
            'visibility' => 4,
            'weight' => 1.0000,
            'entity_type_id' => 4,
            'tax_class_id' => 7,
            'qty' => 100,
            'stock_data' => [
                'stock_id' => 1,
                'use_config_manage_stock' => 0,
                'manage_stock' => 1,
                'min_sale_qty' => 1,
                'is_in_stock' => 1,
                'qty' => 100,
                'backorders' => 1,
                'use_config_backorders' => 0
            ],
        ];
        /** @var \Fastmag\Product\Virtual $product */
        $product = self::$factory->create($data);
        $product->save();

        $this->assertNotNull($product);
        $this->assertInstanceOf('\Fastmag\Product\Virtual', $product);

        return $product;
    }

    /**
     * @depends testCreateSimpleProduct
     * @param Simple $simple
     */
    public function testGetSimpleProduct(Simple $simple) {
        $product = self::$factory->create($simple->getId());
        
        $this->assertEquals($product->getId(), $simple->getId());
        $this->assertEquals($product->getSku(), $simple->getSku());
    }

    /**
     * @depends testCreateSimpleForConfigurable
     * @param Simple $simple
     */
    public function testGetSimpleProductByAttribute(Simple $simple) {
        $product = Fastmag\Fastmag::getInstance()->getModel('Fastmag\Product\Simple');
        $product->loadByAttribute('color', self::$helper->getAttributeOptionValue('color', 'black'));

        $this->assertEquals($product->getId(), $simple->getId());
        $this->assertEquals($product->getSku(), $simple->getSku());
    }
    
    /**
     * @depends testCreateSimpleForConfigurable
     * @param Simple $simple
     */
    public function testGetSimpleProductByAttributeNoProducts(Simple $simple) {
        $this->expectException(Exception::class);
        $product = Fastmag\Fastmag::getInstance()->getModel('Fastmag\Product\Simple');
        $product->loadByAttribute('color', self::$helper->getAttributeOptionValue('color', 'cyan'));
    }

    public function testGetNonExistingProduct() {
        $this->expectException(Exception::class);
        $product = self::$factory->create(999999);
    }
    
    public function testCreateNonExistingTypeIdProduct() {
        $this->expectException(Exception::class);
        $product = self::$factory->create(['type_id' => 'nonexistingtype']);
    }

    /**
     * @param Simple $simple
     * @depends testCreateSimpleProduct
     */
    public function testGetSimpleProductBySku(Simple $simple) {
        $product = self::$factory->createBySku($simple->getSku());
        $this->assertEquals($product->getSku(), $simple->getSku());
        $this->assertEquals($product->getId(), $simple->getId());
        
        $product = self::$factory->create(['sku' => $simple->getSku()]);
        $this->assertEquals($product->getSku(), $simple->getSku());
        $this->assertEquals($product->getId(), $simple->getId());
    }

    /**
     * @param Simple $simple
     * @depends testCreateSimpleProduct
     */
    public function testGetSimpleProductWithoutData(Simple $simple) {
        $product = self::$factory->create(['id' => $simple->getId()]);
        $this->assertEquals($product->getId(), $simple->getId());
        $this->assertEquals($product->getSku(), $simple->getSku());
    }

    public function testGetSimpleProductByData() {
        $data = [
            'type_id' => 'simple',
        ];
        $product = self::$factory->create($data);
        $this->assertEquals($product->getData(), ['type_id' => 'simple']);
        $this->assertInstanceOf('\Fastmag\Product\Simple', $product);
    }

    public function testCallTheUndefinedFunctionOfProduct() {
        $this->expectException(Exception::class);
        $product = self::$factory->create(['type_id' => 'simple']);
        $product->callTheUndefined();
    }

    /**
     * @param Bundle $bundle
     * @depends testCreateBundleProduct
     * @return Bundle
     */
    public function testGetBundleProduct(Bundle $bundle) {
        $product = self::$factory->create($bundle->getId());
        $this->assertEquals($product->getId(), $bundle->getId());
        $this->assertEquals($product->getSku(), $bundle->getSku());
        $this->assertInstanceOf('\Fastmag\Product\Bundle', $product);
    }

    /**
     * @depends testCreateConfigurableProduct
     * @param Configurable $configurable
     */
    public function testGetConfigurableProduct(Configurable $configurable) {
        $product = self::$factory->create($configurable->getId());
        $this->assertEquals($product->getId(), $configurable->getId());
        $this->assertEquals($product->getSku(), $configurable->getSku());
        $this->assertInstanceOf('\Fastmag\Product\Configurable', $product);
    }

    public function testThrowExceptionBecauseNoDataForCreation() {
        $this->expectException(Exception::class);
        $product = self::$factory->create(['color' => 'black']);
    }

    /**
     * @param Bundle $bundle
     * @depends testCreateBundleProduct
     */
    public function testBundleRelations(Bundle $bundle) {
        $items = $bundle->getBundleItems();
        $this->assertEquals(count($items), 1);
    }
    
    /**
     * @param Bundle $bundle
     * @depends testCreateBundleProduct
     */
    public function testBundleSelections(Bundle $bundle) {
        $selections = $bundle->getSelections();
        $this->assertEquals($selections, [
            [
                'selection_id' => 1,
                'option_id' => 1,
                'parent_product_id' => $bundle->getId(),
                'product_id' => 1,
                'position' => 0,
                'is_default' => 1,
                'selection_price_type' => 0,
                'selection_price_value' => 0.0000,
                'selection_qty' => 2.0000,
                'selection_can_change_qty' => 0,
            ]
        ]);
    }
    
    /**
     * @afterClass
     */
    public function testGetAllItems() {
        $collection = self::$instance->getModel('Fastmag\Product\Collection');
        $this->assertEquals(count($collection->getItems()), 9);
    }

    /**
     * @afterClass
     */
    public function testGetAllSimplesProducts() {
        $collection = self::$instance->getModel('Fastmag\Product\Collection');
        $collection->addFieldToFilter('type_id', 'simple');
        $this->assertEquals(count($collection->getItems()), 4);
        return $collection;
    }

    /**
     * @afterClass
     */
    public function testGetCollectionDublicateFilter() {
        $collection = self::$instance->getModel('Fastmag\Product\Collection');
        $collection->addFieldToFilter('type_id', 'simple');
        $this->assertEquals(count($collection->getItems()), 4);
        $collection->addFieldToFilter('type_id', 'bundle');
        $this->assertEquals(count($collection->getItems()), 1);
    }

    /**
     * @afterClass
     */
    public function testGetAllItemsByAttribute() {
        $collection = self::$instance->getModel('Fastmag\Product\Collection');
        $collection->addFieldToFilter('type_id', 'simple');
        $this->assertEquals(count($collection->getItems()), 4);
        $collection->addFieldToFilter('price', 1.0000);
        $this->assertEquals(count($collection->getItems()), 4);
        $collection->addFieldToFilter('weight', 10.0000);
        $this->assertEquals(count($collection->getItems()), 0);
    }

    /**
     * @afterClass
     */
    public function testGetEntityIdsForAttribute() {
        $ids = self::$helper->getEntityIdsForAttribute('status', 1);
        $this->assertEquals(count($ids), 9);
    }
    
    /**
     * @afterClass
     */
    public function testGetEntityIdsForNonExistingAttribute() {
        $ids = self::$helper->getEntityIdsForAttribute('notexistingattribute', 1);
        $this->assertNull($ids);
    }

    /**
     * @afterClass
     * @param Collection $collection
     * @depends testGetAllSimplesProducts
     */
    public function testUpdateStatusAttributeForAllSimpleProducts(Collection $collection) {
        $ids = ArrayHelper::map(function ($item) {
            return $item->getId();
        }, $collection->getItems());

        foreach ($ids as $id) {
            self::$helper->updateAttributes($id, ['status' => 2]);
        }
        
        $this->assertEquals(count($collection->getItems()), 4);
        
        $collection = self::$instance->getModel('Fastmag\Product\Collection');
        $collection->addFieldToFilter('type_id', 'simple');
        $collection->addFieldToFilter('status', 2);
        $this->assertEquals(count($collection->getItems()), 4);
        
        $collection->addFieldToFilter('status', 1);
        $this->assertEquals(count($collection->getItems()), 0);
    }

    /**
     * @afterClass
     */
    public function testGetOptionLabel() {
        $this->assertEquals(
            'black',
            self::$helper->getOptionLabel(self::$helper->getAttributeOptionValue('color', 'black'))
        );
    }

    public function testGetOptionValuesInValueToOptionDir() {
        $this->assertEquals(self::$helper->getOptionValues('color'), $this->colors);
    }

    public function testGetOptionValuesInOptionToValueDir() {
        $this->assertEquals(
            self::$helper->getOptionValues('color', AttributeHelper::DIR_OPTION_TO_VALUE),
            array_flip($this->colors)
        );
    }

    public function testGetOptionValuesInWrongDir() {
        $this->expectException(Exception::class);
        self::$helper->getOptionValues('color', 'dunno');
    }

    public function testGetAttributeIdForUnexistingAttribute() {
        $this->assertNull(self::$helper->getAttributeIdByCode('unexisting_attribute'));
    }
    
    public function testGetAttributeDataForUnexistingAttribute() {
        $this->assertNull(self::$helper->getAttributeData('unexisting_attribute'));
    }
    
    public function testGetOptionLabelForUnexistingStore() {
        $this->assertNull(self::$helper->getOptionLabel(4, 2));
    }

    /**
     * @afterClass
     * @param Simple $simple
     * @depends testCreateSimpleProduct
     * @return Simple
     */
    public function testAddCategoryToRelation(Simple $simple) {
        $simple->setCategoryIds([2, 3, 4]);
        $simple->save();
        $simple = self::$factory->create($simple->getId());
        $this->assertEquals($simple->getCategoryIds(), [2, 3, 4]);
        return $simple;
    }
    
    /**
     * @afterClass
     * @param Simple $simple
     * @depends testAddCategoryToRelation
     */
    public function testDeleteAllCategoryRelationFromSimple(Simple $simple) {
        $simple->setCategoryIds([]);
        $simple->save();
        $simple = self::$factory->create($simple->getId());
        $this->assertEquals($simple->getCategoryIds(), []);
    }
}
