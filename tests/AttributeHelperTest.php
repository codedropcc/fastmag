<?php

use PHPUnit\Framework\TestCase;
use Fastmag\AttributeHelper;
use Fastmag\Connection;
use Fastmag\QB;

class AttributeHelperTest extends TestCase {
    /** @var Fastmag\AttributeHelper $helper */
    protected static $helper = null;
    protected static $instance = null;

    public static function setUpBeforeClass() {
        /** @var Fastmag\Connection $connection */
        self::$instance = Fastmag\Fastmag::getInstance();
        self::$helper = self::$instance->getModel('Fastmag\AttributeHelper');
    }

    public function testGetAttributeDataForNonExistingAttribute() {
        $this->assertEquals(NULL, self::$helper->getAttributeData('notexistingattribute'));
    }

    public function testGetAttributeLabelForNonExistingAttribute() {
        $this->assertEquals(NULL, self::$helper->getAttributeLabel(-1));
    }

    public function testGetSkuByIdForNonExistingId() {
        $this->assertEquals(NULL, self::$helper->getSkuById(-1));
    }

    public function testGetIdBySkuForNonExistingSku() {
        $this->assertEquals(NULL, self::$helper->getIdBySku('notexistingsku'));
    }

    public function testGetOptionValuesForNonIntAttribute() {
        $this->assertEquals(NULL, self::$helper->getOptionValues('sku'));
    }
}
