<?php

use PHPUnit\Framework\TestCase;
use Fastmag\Exception;

class FastmagTest extends TestCase {
    public function testGetModelByName() {
        $fastmag = Fastmag\Fastmag::getInstance();
        $this->assertInstanceOf('\Fastmag\AttributeHelper', $fastmag->getModel('Fastmag\AttributeHelper'));
    }

    public function testReturnExceptionFromGetModel() {
        $this->expectException(Exception::class);
        $fastmag = Fastmag\Fastmag::getInstance();
        $fastmag->getModel(['Fastmag\AttributeHelper']);
    }

    public function testReturnExceptionByNonExistModel() {
        $this->expectException(\Exception::class);
        $fastmag = Fastmag\Fastmag::getInstance();
        $fastmag->getModel('Fastmag\NonExistsModel');
    }
}
