<?php

use Fastmag\Csv;
use Fastmag\Exception;
use PHPUnit\Framework\TestCase;

class CsvTest extends TestCase {
    public function testArray2CsvWithOneRowWithoutHeader() {
        $array = [[
            'header1' => 'first',
            'header2' => 'second',
            'header3' => 'third'
        ]];
        Csv::array2csv($array, 'here.csv');
        $this->assertFileExists('here.csv');
        $this->assertEquals(file_get_contents('here.csv'), "header1;header2;header3\nfirst;second;third\n");
        unlink('here.csv');
    }

    public function testArray2CsvWithOneRowWithHeader() {
        $data = [[
            'first', 'second', 'third'
        ]];
        $header = [
            'header1', 'header2', 'header3'
        ];
        Csv::array2csv($data, 'here.csv', $header);
        $this->assertFileExists('here.csv');
        $this->assertEquals(file_get_contents('here.csv'), "header1;header2;header3\nfirst;second;third\n");
        unlink('here.csv');
    }

    public function testArray2CsvWithTwoRowsWithHeaderAnotherDelimiter() {
        $data = [[
            'first', 'second', 'third'
        ]];
        $header = [
            'header1', 'header2', 'header3'
        ];
        Csv::array2csv($data, 'here.csv', $header, '|');
        $this->assertFileExists('here.csv');
        $this->assertEquals(file_get_contents('here.csv'), "header1|header2|header3\nfirst|second|third\n");
        unlink('here.csv');
    }
    
    public function testArray2CsvEmptyArray() {
        $array = [];
        $data = Csv::array2csv($array, 'here.csv');
        $this->assertFileNotExists('here.csv');
        $this->assertEquals(null, $data);
    }
    
    public function testCsv2ArrayWithDefaultDelimiter() {
        // Preparing CSV at first
        $data = [[
            'first', 'second', 'third'
        ]];
        $header = [
            'header1', 'header2', 'header3'
        ];
        Csv::array2csv($data, 'here.csv', $header, ',');
        $this->assertFileExists('here.csv');
        $this->assertEquals(file_get_contents('here.csv'), "header1,header2,header3\nfirst,second,third\n");

        $validData = [[
            'header1' => 'first',
            'header2' => 'second',
            'header3' => 'third'
        ]];
        $data = Csv::csvToArray('here.csv');
        $this->assertEquals($data, $validData);
        unlink('here.csv');
    }

    public function testCsv2ArrayWithSpecificDelimiter() {
        // Preparing CSV at first
        $data = [[
            'first', 'second', 'third'
        ]];
        $header = [
            'header1', 'header2', 'header3'
        ];
        Csv::array2csv($data, 'here.csv', $header);
        $this->assertFileExists('here.csv');
        $this->assertEquals(file_get_contents('here.csv'), "header1;header2;header3\nfirst;second;third\n");

        $validData = [[
            'header1' => 'first',
            'header2' => 'second',
            'header3' => 'third'
        ]];
        $data = Csv::csvToArray('here.csv', ';');
        $this->assertEquals($data, $validData);
        unlink('here.csv');
    }

    public function testCsv2ArrayWithNoExistingFile() {
        $this->expectException(Exception::class);
        $this->assertFileNotExists('here.csv');
        $data = Csv::csvToArray('here.csv');
    }
}