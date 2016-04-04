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
        $this->assertTrue(Filter::isInteger([1]));
        $this->assertTrue(Filter::isInteger([1, 1]));
        $this->assertTrue(Filter::isInteger([1, 1, 2]));
        $this->assertTrue(Filter::isInteger([1, 1, 2, -1]));
        $this->assertTrue(Filter::isInteger([1, 1, 2, -1, 10000]));
        $this->assertTrue(Filter::isInteger([1, 1, 2, -1], -10));
        $this->assertTrue(Filter::isInteger([1, 1, 2, -1], -10, 10 / 3));
        $this->assertTrue(Filter::isInteger([-10, 1, 2, -1], -10, 10));
        $this->assertTrue(Filter::isInteger([-10, 1, 2, -1], -10));
        $this->assertTrue(Filter::isInteger([0, 1, 2, -1], null, 100 / 7));
        $this->assertTrue(Filter::isInteger([0, 1, '2', -1], null, 100 / 13));
        $this->assertTrue(Filter::isInteger([0, 1, '-2', -1], -100, 100 / 13));
        $this->assertTrue(Filter::isInteger([1, 1], '1'));
        $this->assertTrue(Filter::isInteger([1, 1], '0', '4'));
        $this->assertTrue(Filter::isInteger([1, 1], null, '1'));
        $this->assertTrue(Filter::isInteger([1, 1], '0.999', '1.001'));

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
        $this->assertFalse(Filter::isInteger(''));
        $this->assertFalse(Filter::isInteger('1a'));
        $this->assertFalse(Filter::isInteger('asdaa'));
        $this->assertFalse(Filter::isInteger(true));
        $this->assertFalse(Filter::isInteger(false));
        $this->assertFalse(Filter::isInteger(null));
        $this->assertFalse(Filter::isInteger([]));
        $this->assertFalse(Filter::isInteger(['-0.0', '1', '-200', '-1'], -1000, 100 / 13));
        $this->assertFalse(Filter::isInteger([-10, 1, 2, -1], 0, 10));
        $this->assertFalse(Filter::isInteger([0, 1, 2, 11], 0, 10));
        $this->assertFalse(Filter::isInteger([0, -1, 2, 11], 2, 10));
        $this->assertFalse(Filter::isInteger([true, -1, 2, 11], 2, 10));
        $this->assertFalse(Filter::isInteger([5, null, 2, 11], 2, 10));
        $this->assertFalse(Filter::isInteger([5, 2, '', 11], 2, 10));
        $this->assertFalse(Filter::isInteger([5, 2, 2.1, 11], 1, 10));
        $this->assertFalse(Filter::isInteger([5, 2, '2.1', 11], 1, 10));
        $this->assertFalse(Filter::isInteger([5, 2, '-2.1', 11], 1, 10));
        $this->assertFalse(Filter::isInteger([5, 2, '', 11], 2, 10));
        $this->assertFalse(Filter::isInteger([5, [2, 4], '', 11], 2, 10));
        $this->assertFalse(Filter::isInteger('one', 2, 10));
        $this->assertFalse(Filter::isInteger(new Filter(), 2, 10));
        $this->assertFalse(Filter::isInteger([1, 1], '1.999', '1.001'));
        $this->assertFalse(Filter::isInteger([1, 1, 0], 2, 1));

        $this->assertTrue(Filter::isInteger(range(1, 1000000), 0, 2000000));
    }



    public function testIsFloat()
    {
        $this->assertTrue(Filter::isNumeric(1));
        $this->assertTrue(Filter::isNumeric(0));
        $this->assertTrue(Filter::isNumeric(-1));
        $this->assertTrue(Filter::isNumeric(-0));
        $this->assertTrue(Filter::isNumeric(1, -1, 2));
        $this->assertTrue(Filter::isNumeric(1, 0, 2));
        $this->assertTrue(Filter::isNumeric(1, 0, 1));
        $this->assertTrue(Filter::isNumeric(1, 1, 2));
        $this->assertTrue(Filter::isNumeric(1, -100));
        $this->assertTrue(Filter::isNumeric(1, -100, null));
        $this->assertTrue(Filter::isNumeric(-100, null, 0));
        $this->assertTrue(Filter::isNumeric(1, null, 100));
        $this->assertTrue(Filter::isNumeric(10, 5));
        $this->assertTrue(Filter::isNumeric(10, 0));
        $this->assertTrue(Filter::isNumeric(10, 5, 15));
        $this->assertTrue(Filter::isNumeric(-100, -200, -50));
        $this->assertTrue(Filter::isNumeric(-100, -200, 50));
        $this->assertTrue(Filter::isNumeric(-100, -100, 50));
        $this->assertTrue(Filter::isNumeric(50, -100, 50));
        $this->assertTrue(Filter::isNumeric(-50, -50, 50));
        $this->assertTrue(Filter::isNumeric(-50, -150, -50));
        $this->assertTrue(Filter::isNumeric('1'));
        $this->assertTrue(Filter::isNumeric('1.2'));
        $this->assertTrue(Filter::isNumeric('200'));
        $this->assertTrue(Filter::isNumeric('200.45'));
        $this->assertTrue(Filter::isNumeric('200.50'));
        $this->assertTrue(Filter::isNumeric('12.80', 10, 110));
        $this->assertTrue(Filter::isNumeric('-12.80', -20.1, -10.3));
        $this->assertTrue(Filter::isNumeric([1.1]));
        $this->assertTrue(Filter::isNumeric([1, 1.1]));
        $this->assertTrue(Filter::isNumeric([1, 1, 2, -1.2]));
        $this->assertTrue(Filter::isNumeric([1, 1, 2, -1.1, 10000]));
        $this->assertTrue(Filter::isNumeric([1, 1.1, 2, -1.1], -10));
        $this->assertTrue(Filter::isNumeric([1.9, 1.1, 2.1, '-1.7'], -10, 10));
        $this->assertTrue(Filter::isNumeric([-10, 1, 2, -1], -10.8, 10.8));
        $this->assertTrue(Filter::isNumeric([-10, '1.7', 2, '-1.9'], -10));
        $this->assertTrue(Filter::isNumeric([0, 1, 2, -1], null, 100));
        $this->assertTrue(Filter::isNumeric([0, 1, '2', -1], null, 100));

        $this->assertFalse(Filter::isNumeric('12,80', 10, 110));
        $this->assertFalse(Filter::isNumeric('12,8', 10, 110));
        $this->assertFalse(Filter::isNumeric('50.23', 10, 30));
        $this->assertFalse(Filter::isNumeric(1, 2, 3));
        $this->assertFalse(Filter::isNumeric(5, 1, 3));
        $this->assertFalse(Filter::isNumeric(5, 1, 1));
        $this->assertFalse(Filter::isNumeric(5, 10, 10));
        $this->assertFalse(Filter::isNumeric(5, 10));
        $this->assertFalse(Filter::isNumeric(-5, 0));
        $this->assertFalse(Filter::isNumeric(5, 10.2, null));
        $this->assertFalse(Filter::isNumeric(5.7, null, 1.3));
        $this->assertFalse(Filter::isNumeric(-50, -40, -20));
        $this->assertFalse(Filter::isNumeric(null));
        $this->assertFalse(Filter::isNumeric(''));
        $this->assertFalse(Filter::isNumeric('1a'));
        $this->assertFalse(Filter::isNumeric('asdaa'));
        $this->assertFalse(Filter::isNumeric(true));
        $this->assertFalse(Filter::isNumeric(false));
        $this->assertFalse(Filter::isNumeric([]));
        $this->assertFalse(Filter::isNumeric([-10, 1, 2, -1], 0, 10));
        $this->assertFalse(Filter::isNumeric([0, 1, 2, 11.9], 0, 10));
        $this->assertFalse(Filter::isNumeric([0, -1.2, 2, 11], 2, 10));
        $this->assertFalse(Filter::isNumeric([true, -1, 2, 11], 2, 10));
        $this->assertFalse(Filter::isNumeric([5, null, 2, 11], 2, 10));
        $this->assertFalse(Filter::isNumeric([5, 2, '', 11], 2, 10));
        $this->assertFalse(Filter::isNumeric([5, 2, 2.1, 11], 2, 10));
        $this->assertFalse(Filter::isNumeric([5, 2, '', 11], 2, 10));
        $this->assertFalse(Filter::isNumeric([5, [2, 4], '', 11], 2, 10));
        $this->assertFalse(Filter::isNumeric('one', 2, 10));
        $this->assertFalse(Filter::isNumeric(new Filter(), 2, 10));

        $this->assertTrue(Filter::isNumeric(range(1, 1000000), 0, 2000000));
    }



    public function testIsNatural()
    {
        $this->assertTrue(Filter::isNatural(1));
        $this->assertTrue(Filter::isNatural(0));
        $this->assertTrue(Filter::isNatural(-0));
        $this->assertTrue(Filter::isNatural(1, -1, 2));
        $this->assertTrue(Filter::isNatural(1, 0, 2));
        $this->assertTrue(Filter::isNatural(1, 0, 1));
        $this->assertTrue(Filter::isNatural(1, 1, 1));
        $this->assertTrue(Filter::isNatural(0, 0, 0));
        $this->assertTrue(Filter::isNatural(1, 0.999, 1.0001));
        $this->assertTrue(Filter::isNatural(1, -100));
        $this->assertTrue(Filter::isNatural(1, -100, null));
        $this->assertTrue(Filter::isNatural(1, null, 100));
        $this->assertTrue(Filter::isNatural(10, 5));
        $this->assertTrue(Filter::isNatural(10, 0));
        $this->assertTrue(Filter::isNatural(10, 5, 15));
        $this->assertTrue(Filter::isNatural(50, -100, 50));
        $this->assertTrue(Filter::isNatural('-0'));
        $this->assertTrue(Filter::isNatural('1'));
        $this->assertTrue(Filter::isNatural('200'));
        $this->assertTrue(Filter::isNatural([1, '200']));
        $this->assertTrue(Filter::isNatural([1, '200']));

        $this->assertFalse(Filter::isNatural(-100, null, 0));
        $this->assertFalse(Filter::isNatural(-1));
        $this->assertFalse(Filter::isNatural('-200'));
        $this->assertFalse(Filter::isNatural('50.23', 10, 30));
        $this->assertFalse(Filter::isNatural('1.2'));
        $this->assertFalse(Filter::isNatural('200.45'));
        $this->assertFalse(Filter::isNatural('12.80', 10, 110));
        $this->assertFalse(Filter::isNatural('-12.80', -20.1, -10.3));
        $this->assertFalse(Filter::isNatural('12,8'));
        $this->assertFalse(Filter::isNatural('12,8', 10, 110));
        $this->assertFalse(Filter::isNatural(1, 2, 3));
        $this->assertFalse(Filter::isNatural(5.2, 1, 9));
        $this->assertFalse(Filter::isNatural(5, 1, 1));
        $this->assertFalse(Filter::isNatural(5, 10, 10));
        $this->assertFalse(Filter::isNatural(5, 10));
        $this->assertFalse(Filter::isNatural(5, 10.2, null));
        $this->assertFalse(Filter::isNatural(5.7, null, 1.3));
        $this->assertFalse(Filter::isNatural(''));
        $this->assertFalse(Filter::isNatural('1a'));
        $this->assertFalse(Filter::isNatural('asdaa'));
        $this->assertFalse(Filter::isNatural(true));
        $this->assertFalse(Filter::isNatural(false));
        $this->assertFalse(Filter::isNatural(null));
        $this->assertFalse(Filter::isNatural([]));
        $this->assertFalse(Filter::isNatural([1.1]));
        $this->assertFalse(Filter::isNatural([1, 1.1]));
        $this->assertFalse(Filter::isNatural([10, 1, 2, -1], -10.8, 10.8));
        $this->assertFalse(Filter::isNatural([-10, '1.7', 2, '-1.9'], -10));
        $this->assertFalse(Filter::isNatural([0, 1, 2, -1], null, 100));
        $this->assertFalse(Filter::isNatural([0, 1, '2', -1], null, 100));
        $this->assertFalse(Filter::isNatural([1, 1, 2, -1.2]));
        $this->assertFalse(Filter::isNatural([1, 1, 2, -1.1, 10000]));
        $this->assertFalse(Filter::isNatural([1, 1.1, 2, -1.1], -10));
        $this->assertFalse(Filter::isNatural([1.9, 1.1, 2.1, '-1.7'], -10, 10));
        $this->assertFalse(Filter::isNatural([-10, 1, 2, -1], 0, 10));
        $this->assertFalse(Filter::isNatural([0, 1, 2, 11.9], 0, 10));
        $this->assertFalse(Filter::isNatural([0, -1.2, 2, 11], 2, 10));
        $this->assertFalse(Filter::isNatural([true, -1, 2, 11], 2, 10));
        $this->assertFalse(Filter::isNatural([5, null, 2, 11], 2, 10));
        $this->assertFalse(Filter::isNatural([5, 2, '', 11], 2, 10));
        $this->assertFalse(Filter::isNatural([5, 2, 2.1, 11], 2, 10));
        $this->assertFalse(Filter::isNatural([5, 2, '', 11], 2, 10));
        $this->assertFalse(Filter::isNatural([5, [2, 4], '', 11], 2, 10));
        $this->assertFalse(Filter::isNatural('one', 2, 10));
        $this->assertFalse(Filter::isNatural(new Filter(), 2, 10));

        $this->assertTrue(Filter::isNatural(range(1, 1000000), 0, 2000000));
    }



    public function testIsString()
    {
        $this->assertTrue(Filter::isString(''));
        $this->assertTrue(Filter::isString(' '));
        $this->assertTrue(Filter::isString('1'));
        $this->assertTrue(Filter::isString('0'));
        $this->assertTrue(Filter::isString('1.1'));
        $this->assertTrue(Filter::isString('-1.1'));
        $this->assertTrue(Filter::isString('-1,1'));
        $this->assertTrue(Filter::isString('a'));
        $this->assertTrue(Filter::isString("\n"));
        $this->assertTrue(Filter::isString("\r"));
        $this->assertTrue(Filter::isString("\t"));
        $this->assertTrue(Filter::isString("\r\n"));
        $this->assertTrue(Filter::isString("asd\rasd\nasd"));
        $this->assertTrue(Filter::isString("asd\r\nasd"));
        $this->assertTrue(Filter::isString("\r\nasd"));
        $this->assertTrue(Filter::isString("gfghg\r\n"));
        $this->assertTrue(Filter::isString("\0"));
        $this->assertTrue(Filter::isString("\0asdas"));
        $this->assertTrue(Filter::isString("'"));
        $this->assertTrue(Filter::isString('"'));
        $ar = [777, 0, true, false, null, 'a', FILE_APPEND, 1.2, 'asdasd'];
        foreach ($ar as $a) {
            $this->assertTrue(Filter::isString("$a"));
        }
        $this->assertTrue(Filter::isString('asdasgfgdflkafsda'));
        $this->assertTrue(Filter::isString(str_repeat(' ', 1000)));
        $this->assertTrue(Filter::isString(['']));
        $this->assertTrue(Filter::isString(['', 's', '2', '6', '6', '1323234234234', 'sdfsdfsdfs dfsdf']));
        $this->assertTrue(Filter::isString(['', ' ', '2', 'g', '6', '1323234', 'sdfsdfs sdf']));

        $this->assertFalse(Filter::isString(true));
        $this->assertFalse(Filter::isString(false));
        $this->assertFalse(Filter::isString(null));
        $this->assertFalse(Filter::isString(1));
        $this->assertFalse(Filter::isString(0));
        $this->assertFalse(Filter::isString([]));
        $this->assertFalse(Filter::isString([2]));
        $this->assertFalse(Filter::isString([2, '']));
        $this->assertFalse(Filter::isString([true, '']));
        $this->assertFalse(Filter::isString([false, '']));
        $this->assertFalse(Filter::isString([null, '']));
        $this->assertFalse(Filter::isString(['', ' ', '2', 'g', '6', '1323234', 'sdfsdfs sdf', true]));

        $this->assertTrue(Filter::isString(str_repeat('0', 1000000)));
    }

}
 