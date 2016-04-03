<?php
/**
 * Created by PhpStorm.
 * User: Виктор
 * Date: 03.04.2016
 * Time: 1:30
 */
require  '../config.php';
require  '../lib/autoload.php';
require  '../vendor/autoload.php';

class FilterTest extends PHPUnit_Framework_TestCase {

    public function testIsInteger()
    {
        $this->assertTrue(Filter::isInteger(1));
        $this->assertTrue(Filter::isInteger(0));
        $this->assertTrue(Filter::isInteger(-1));
        $this->assertTrue(Filter::isInteger(-0));
        $this->assertTrue(Filter::isInteger(1, -1, 2));
        $this->assertTrue(Filter::isInteger(1, 0, 2));
        $this->assertTrue(Filter::isInteger(1, 0, 1));
        $this->assertTrue(Filter::isInteger(1, 1, 2));
        $this->assertTrue(Filter::isInteger(1, -100));
        $this->assertTrue(Filter::isInteger(1, -100, null));
        $this->assertTrue(Filter::isInteger(-100, null, 0));
        $this->assertTrue(Filter::isInteger(1, null, 100));
        $this->assertTrue(Filter::isInteger(10, 5));
        $this->assertTrue(Filter::isInteger(10, 0));
        $this->assertTrue(Filter::isInteger(10, 5, 15));
        $this->assertTrue(Filter::isInteger(-100, -200, -50));
        $this->assertTrue(Filter::isInteger(-100, -200, 50));
        $this->assertTrue(Filter::isInteger(-100, -100, 50));
        $this->assertTrue(Filter::isInteger(50, -100, 50));
        $this->assertTrue(Filter::isInteger(-50, -50, 50));
        $this->assertTrue(Filter::isInteger(-50, -150, -50));

        $this->assertTrue(Filter::isInteger('1'));
        $this->assertTrue(Filter::isInteger('200'));
        $this->assertTrue(Filter::isInteger('80', 10, 110));

        $this->assertFalse(Filter::isInteger('500', 10, 90));
        $this->assertFalse(Filter::isInteger('1.2', 10, 90));

        $this->assertFalse(Filter::isInteger(1, 2, 3));
        $this->assertFalse(Filter::isInteger(5, 1, 3));
        $this->assertFalse(Filter::isInteger(5, 1, 1));
        $this->assertFalse(Filter::isInteger(5, 10, 10));
        $this->assertFalse(Filter::isInteger(5, 10));
        $this->assertFalse(Filter::isInteger(-5, 0));
        $this->assertFalse(Filter::isInteger(5, 10, null));
        $this->assertFalse(Filter::isInteger(5, null, 1));
        $this->assertFalse(Filter::isInteger(-50, -40, -20));

        $this->assertFalse(Filter::isInteger(null));
        $this->assertFalse(Filter::isInteger(''));
        $this->assertFalse(Filter::isInteger('1a'));
        $this->assertFalse(Filter::isInteger('asdaa'));
        $this->assertFalse(Filter::isInteger(true));
        $this->assertFalse(Filter::isInteger(false));
        $this->assertFalse(Filter::isInteger([]));

        $this->assertTrue(Filter::isInteger([1]));
        $this->assertTrue(Filter::isInteger([1, 1]));
        $this->assertTrue(Filter::isInteger([1, 1, 2]));
        $this->assertTrue(Filter::isInteger([1, 1, 2, -1]));
        $this->assertTrue(Filter::isInteger([1, 1, 2, -1, 10000]));
        $this->assertTrue(Filter::isInteger([1, 1, 2, -1], -10));
        $this->assertTrue(Filter::isInteger([1, 1, 2, -1], -10, 10));
        $this->assertTrue(Filter::isInteger([-10, 1, 2, -1], -10, 10));
        $this->assertTrue(Filter::isInteger([-10, 1, 2, -1], -10));
        $this->assertTrue(Filter::isInteger([0, 1, 2, -1], null, 100));
        $this->assertTrue(Filter::isInteger([0, 1, '2', -1], null, 100));

        $this->assertFalse(Filter::isInteger([-10, 1, 2, -1], 0, 10));
        $this->assertFalse(Filter::isInteger([0, 1, 2, 11], 0, 10));
        $this->assertFalse(Filter::isInteger([0, -1, 2, 11], 2, 10));

        $this->assertFalse(Filter::isInteger([true, -1, 2, 11], 2, 10));
        $this->assertFalse(Filter::isInteger([5, null, 2, 11], 2, 10));
        $this->assertFalse(Filter::isInteger([5, 2, '', 11], 2, 10));
        $this->assertFalse(Filter::isInteger([5, 2, 2.1, 11], 2, 10));
        $this->assertFalse(Filter::isInteger([5, 2, '', 11], 2, 10));
        $this->assertFalse(Filter::isInteger([5, [2, 4], '', 11], 2, 10));
        $this->assertFalse(Filter::isInteger('one', 2, 10));

        $this->assertTrue(Filter::isInteger(range(1, 1000000), 0, 2000000));
    }
}
 