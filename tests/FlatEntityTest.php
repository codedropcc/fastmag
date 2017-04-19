<?php

use Fastmag\EntityType;
use Fastmag\Exception;

use PHPUnit\Framework\TestCase;

class FlatEntityTest extends TestCase {
    protected static $instance = null;

    public static function setUpBeforeClass() {
        self::$instance = Fastmag\Fastmag::getInstance()->getModel('Fastmag\EntityType')->load('catalog_product', 'entity_type_code');
    }

    public function testGetWholeData() {
        $this->assertEquals(self::$instance->getData(), ['entity_type_id' => '4']);
    }

    /**
     * @return EntityType
     */
    public function testGetEntityTypeCodeAndCheckThatIfSaved() {
        $entity_type_code = 'catalog_product';
        $loaded_entity_type_code = self::$instance->getEntityTypeCode();
        $data_sharing_key = 'default';
        $loaded_data_sharing_key = self::$instance->getDataSharingKey();
        $this->assertEquals($entity_type_code, $loaded_entity_type_code);
        $this->assertEquals($data_sharing_key, $loaded_data_sharing_key);
        $this->assertEquals(self::$instance->getData(), ['entity_type_id' => '4', 'entity_type_code' => $entity_type_code, 'data_sharing_key' => $data_sharing_key]);
        return self::$instance;
    }

    /**
     * @param Fastmag\EntityType $entityType
     * @depends testGetEntityTypeCodeAndCheckThatIfSaved
     * @return EntityType
     */
    public function testUnsetEntityTypeCodeAndSureThatIfRemoved(EntityType $entityType) {
        $entityType->unsetData(['entity_type_code']);
        $this->assertEquals($entityType->getData(), ['entity_type_id' => '4', 'data_sharing_key' => 'default']);
        return $entityType;
    }

    /**
     * @param Fastmag\EntityType $entityType
     * @depends testUnsetEntityTypeCodeAndSureThatIfRemoved
     */
    public function testUnsetEntityTypeByCode(EntityType $entityType) {
        $entityType->unsetDataSharingKey();
        $this->assertEquals($entityType->getData(), ['entity_type_id' => '4']);
    }

    public function testGetNonExistsColumn() {
        $this->expectException(\PDOException::class);
        self::$instance->getFromColumns('non_exists_column');
    }

    public function testGetEntityIdForExistsButNoData() {
        $entity_id = self::$instance->getEntityIdForColumn('data_sharing_key', 'non-default');
        $this->assertEquals($entity_id, null);
    }

    public function testGetEntityIdForNonExistingColumn() {
        $this->expectException(\PDOException::class);
        $entity_id = self::$instance->getEntityIdForColumn('non_exists_column', 'value');
    }
}
