<?php

use PHPUnit\Framework\TestCase;
use Fastmag\ArrayHelper;

class Test {
    private $items = [];
    
    public function __construct($items) {
        $this->items = $items;
    }

    public function getItems() {
        return $this->items;
    }
}

class ArrayHelperTest extends TestCase {
    public function testMap() {
        $array = [1, 2, 3, 4];
        $newArray = ArrayHelper::map(function ($item) {
            return $item*2;
        }, $array);
        $this->assertEquals($array, [1, 2, 3, 4]);
        $this->assertEquals($newArray, [2, 4, 6, 8]);
        
        $array = [
            'a' => 1,
            'b' => 2,
            'c' => 3,
            'd' => 4,
        ];
        $newArray = ArrayHelper::map(function ($item) {
            return $item * 2;
        }, $array);
        $this->assertEquals($array, ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
        $this->assertEquals($newArray, [2, 4, 6, 8]);
    }

    public function testShortest() {
        $array1 = [1, 2, 3, 4];
        $array2 = [1, 2, 3, 4, 5];
        $array3 = [1, 2, 3, 4, 5, null];
        $array4 = [1, 2, 3, 4];
        $array5 = [1, 2, 3, 4, 5];
        
        $this->assertEquals(ArrayHelper::shortest($array1, $array2, $array3, $array4, $array5), 4);
    }

    public function testZip() {
        $array1 = [1, 2, 3, 4];
        $array2 = [10, 20, 30, 40];

        $newArray = ArrayHelper::zip($array1, $array2);

        $this->assertEquals($newArray, [[1, 10], [2, 20], [3, 30], [4, 40]]);

        $array1 = [1, 2, 3, 4];
        $array2 = ['a', 'b', 'c', 'd', 'e'];

        $newArray = ArrayHelper::zip($array1, $array2);
        
        $this->assertEquals($newArray, [[1, 'a'], [2, 'b'], [3, 'c'], [4, 'd']]);
    }

    public function testZip3() {
        $array1 = [1, 2, 3, 4];
        $array2 = [10, 20, 30, 40];

        $newArray = ArrayHelper::zip3(null, $array1, $array2);

        $this->assertEquals($newArray, [[1, 10], [2, 20], [3, 30], [4, 40]]);

        $array1 = [1, 2, 3, 4];
        $array2 = ['a', 'b', 'c', 'd', 'e'];

        $newArray = ArrayHelper::zip3(null, $array1, $array2);
        
        $this->assertEquals($newArray, [[1, 'a'], [2, 'b'], [3, 'c'], [4, 'd'], [null, 'e']]);
    }

    public function testFlatMap() {
        $data = [
            new Test([1, 2, 3]),
            new Test([4, 5, 6]),
            new Test([4, 5, 2]),
            new Test(1),
            new Test(4),
            new Test(7),
            new Test(null),
            new Test('string'),
        ];
        
        $newArray = ArrayHelper::flatMap(function ($item) {
            /** @var Test $item */
            return $item->getItems();
        }, $data);
        
        $this->assertEquals([1, 2, 3, 4, 5, 6, 4, 5, 2, 1, 4, 7, null, 'string'], $newArray);
    }
    
    public function testFilter() {
        $array = [1, 2, 3, 4];
        $newArray = ArrayHelper::filter(function ($item) {
            return $item % 2 == 0;
        }, $array);
        $this->assertEquals($array, [1, 2, 3, 4]);
        $this->assertEquals($newArray, [2, 4]);
        
        $array = [1, false, true, false];
        $newArray = ArrayHelper::filter('true', $array);
        $this->assertEquals($array, [1, false, true, false]);
        $this->assertEquals($newArray, [1, true]);

        $array = [
            'a' => 1,
            'b' => 2,
            'c' => 3,
            'd' => 4,
        ];
        $newArray = ArrayHelper::filter(function ($item) {
            return $item % 2 == 0;
        }, $array);
        $this->assertEquals($array, ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
        $this->assertEquals($newArray, [2, 4]);
    }

    public function testReduce() {
        $array = [1, 2, 3, 4];
        $value = ArrayHelper::reduce(function ($result, $item) {
            return $result + $item;
        }, $array);
        $this->assertEquals($array, [1, 2, 3, 4]);
        $this->assertEquals($value, 10);

        $array = [
            'a' => 1,
            'b' => 2,
            'c' => 3,
            'd' => 4,
        ];
        $value = ArrayHelper::reduce(function ($result, $item) {
            return $result + $item;
        }, $array);
        $this->assertEquals($array, ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
        $this->assertEquals($value, 10);
    }
    
    public function testReduceBuildingAssocArray() {
        $array = [1, 2, 3, 4];
        
        $newArray = ArrayHelper::reduce(
            function ($result, $item) {
                $result[$item*2] = $item;
                return $result;
            },
            $array,
            array()
        );
        
        $this->assertEquals($newArray, [2 => 1, 4 => 2, 6 => 3, 8 => 4]);
    }
    
    public function testLast() {
        $array = [1, 2, 3, 4];
        $value = ArrayHelper::last($array);
        $this->assertEquals($array, [1, 2, 3, 4]);
        $this->assertEquals($value, 4);
        
        $array = [
            'a' => 1,
            'b' => 2,
            'c' => 3,
            'd' => 4,
        ];
        $value = ArrayHelper::last($array);
        $this->assertEquals($array, ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
        $this->assertEquals($value, 4);
    }
    
    public function testFirst() {
        $array = [1, 2, 3, 4];
        $value = ArrayHelper::first($array);
        $this->assertEquals($array, [1, 2, 3, 4]);
        $this->assertEquals($value, 1);
        
        $array = [
            'a' => 1,
            'b' => 2,
            'c' => 3,
            'd' => 4,
        ];
        $value = ArrayHelper::first($array);
        $this->assertEquals($array, ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
        $this->assertEquals($value, 1);
    }
    
    public function testAny() {
        $array = [1, 2, 3, 4];
        $value = ArrayHelper::any(function ($item) {
            return $item % 2 == 0;
        }, $array);
        $this->assertEquals($array, [1, 2, 3, 4]);
        $this->assertEquals($value, true);
        
        $array = [1, 2, 3, 4];
        $value = ArrayHelper::any(function ($item) {
            return $item % 5 == 0;
        }, $array);
        $this->assertEquals($array, [1, 2, 3, 4]);
        $this->assertEquals($value, false);

        $array = [
            'a' => 1,
            'b' => 2,
            'c' => 3,
            'd' => 4,
        ];
        $value = ArrayHelper::any(function ($item) {
            return $item % 2 == 0;
        }, $array);
        $this->assertEquals($array, ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
        $this->assertEquals($value, true);
    }
    
    public function testWalk() {
        $array = [1, 2, 3, 4];
        ArrayHelper::walk(function (&$item) {
            $item *= 2;
        }, $array);
        $this->assertEquals($array, [2, 4, 6, 8]);

        $array = [
            'a' => 1,
            'b' => 2,
            'c' => 3,
            'd' => 4,
        ];
        ArrayHelper::walk(function (&$item) {
            $item *= 2;
        }, $array);
        $this->assertEquals($array, ['a' => 2, 'b' => 4, 'c' => 6, 'd' => 8]);
    }

    public function testFlatWithLimitOne() {
        $array = [1, [3, 4, 5], 3, 4];
        $newArray = ArrayHelper::flat($array, 1);
        $this->assertEquals($array, [1, [3, 4, 5], 3, 4]);
        $this->assertEquals($newArray, [1, 3, 4, 5, 3, 4]);
        
        $array = [1, [3, [4, 5], 5], 3, 4];
        $newArray = ArrayHelper::flat($array, 1);
        $this->assertEquals($array, [1, [3, [4, 5], 5], 3, 4]);
        $this->assertEquals($newArray, [1, 3, [4, 5], 5, 3, 4]);
    }
    
    public function testFlatWithLimitTwo() {
        $array = [1, [3, [4, 5], 5], 3, 4];
        $newArray = ArrayHelper::flat($array, 2);
        $this->assertEquals($array, [1, [3, [4, 5], 5], 3, 4]);
        $this->assertEquals($newArray, [1, 3, 4, 5, 5, 3, 4]);
    }
    
    public function testFlatWithLimitTwoAndDeepOne() {
        $array = [1, [3, [4, 5], 5], 3, 4];
        $newArray = ArrayHelper::flat($array, 2, 1);
        $this->assertEquals($array, [1, [3, [4, 5], 5], 3, 4]);
        $this->assertEquals($newArray, [1, 3, [4, 5], 5, 3, 4]);
    }
    
    public function testIsMulti() {
        $array = [1, [3, 4, 5], 3, 4];
        $value = ArrayHelper::is_multi($array);
        $this->assertEquals($value, true);
        
        $array = [1, 3, 4, 5, 3, 4];
        $value = ArrayHelper::is_multi($array);
        $this->assertEquals($value, false);
    }
    
    public function testIsAssoc() {
        $array = [1, 2, 3, 4];
        $this->assertEquals(ArrayHelper::is_assoc($array), false);
        
        $array = [1, 2, [3, 4]];
        $this->assertEquals(ArrayHelper::is_assoc($array), false);
        
        $array = ['kek' => 1, 'kek2' => 2];
        $this->assertEquals(ArrayHelper::is_assoc($array), true);
    }
}
