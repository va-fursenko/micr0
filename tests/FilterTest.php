
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

class FilterTest extends PHPUnit_Framework_TestCase
{
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
        $this->assertTrue(Filter::isInteger([1, 1, 0, -1, -1, 0, '-1', '1', '0', '-0'], '-1.001', '1.001'));
        $this->assertTrue(Filter::isInteger([1, 1, 3, 4, 5, 6, 3, 1, 2, 4, 3, 4], '0.999', '6.001'));

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
        $this->assertFalse(Filter::isInteger(function ($num) { return $num * 2;}));

        //$this->assertTrue(Filter::isInteger(range(1, 1000000), 0, 2000000));
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
        $this->assertFalse(Filter::isNumeric(function ($num) { return $num * 2;}));

        //$this->assertTrue(Filter::isNumeric(range(1, 1000000), 0, 2000000));
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
        $this->assertTrue(Filter::isNatural([1, '2', 4, 4, '3', '200']));

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
        $this->assertFalse(Filter::isNatural(function ($num) { return $num * 2;}));

        //$this->assertTrue(Filter::isNatural(range(1, 1000000), 0, 2000000));
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
        $this->assertFalse(Filter::isString(function ($num) { return $num * 2;}));

        //$this->assertTrue(Filter::isString(str_repeat('0', 1000000)));
    }


    public function testIsArray()
    {
        $this->assertTrue(Filter::isArray([]));
        $this->assertTrue(Filter::isArray([1]));
        $this->assertTrue(Filter::isArray([2 => 1]));
        $this->assertTrue(Filter::isArray(['']));
        $this->assertTrue(Filter::isArray(['a']));
        $this->assertTrue(Filter::isArray(['a' => 1]));
        $this->assertTrue(Filter::isArray(['a' => '1', 'b' => 2]));
        $this->assertTrue(Filter::isArray(['aasdasda']));
        $this->assertTrue(Filter::isArray([true]));
        $this->assertTrue(Filter::isArray([false]));
        $this->assertTrue(Filter::isArray([null]));
        $this->assertTrue(Filter::isArray([new Filter()]));
        $this->assertTrue(Filter::isArray([1, 2]));
        $this->assertTrue(Filter::isArray([1, 2, []]));
        $this->assertTrue(Filter::isArray([1, 2, [1, 2]]));
        $this->assertTrue(Filter::isArray([true, 'asdas', 1]));
        $this->assertTrue(Filter::isArray([[]]));
        $this->assertTrue(Filter::isArray([[], []]));
        $this->assertTrue(Filter::isArray([[1]]));
        $this->assertTrue(Filter::isArray([['a']]));
        $this->assertTrue(Filter::isArray(['a' => '1', 2, 3]));
        $this->assertTrue(Filter::isArray(['a' => null, 'b' => null]));
        $this->assertTrue(Filter::isArray(['a' => 3, 1 => 2]));
        $this->assertTrue(Filter::isArray(['array' => ['a' => 3, 1 => 2]]));
        $this->assertTrue(Filter::isArray([true => 1]));
        $this->assertTrue(Filter::isArray([false => 1]));
        $this->assertTrue(Filter::isArray([null => 1]));
        $this->assertTrue(Filter::isArray(array(1, 2, 3, 5)));
        $this->assertTrue(Filter::isArray(range(1, 100)));
        $a = range(1, 10);
        $b = &$a;
        $this->assertTrue(Filter::isArray($a));
        $this->assertTrue(Filter::isArray($b));
        $this->assertTrue(Filter::isArray($a + $b));
        $this->assertTrue(Filter::isArray(array_keys($a)));
        $this->assertTrue(Filter::isArray([], []));
        $this->assertTrue(Filter::isArray([], $a + $b));
        $this->assertTrue(Filter::isArray(['1', '2', '3'], [2, 3, 5], [3, true, null, false, new Filter()], []));

        $this->assertFalse(Filter::isArray(0));
        $this->assertFalse(Filter::isArray(1));
        $this->assertFalse(Filter::isArray(true));
        $this->assertFalse(Filter::isArray(false));
        $this->assertFalse(Filter::isArray(null));
        $this->assertFalse(Filter::isArray(''));
        $this->assertFalse(Filter::isArray('a'));
        $this->assertFalse(Filter::isArray('1'));
        $this->assertFalse(Filter::isArray('[1]'));
        $this->assertFalse(Filter::isArray(new Filter()));
        $this->assertFalse(Filter::isArray(function ($num) { return $num * 2;}));
        $this->assertFalse(Filter::isArray(function ($num) { return [$num, $num];}));
        $this->assertFalse(Filter::isArray(['1', '2', '3'], [2, 3, 5], [3, true, null, false, new Filter()], [], 2));

        //$this->assertTrue(Filter::isArray(range(1, 1000000)));
    }


    public function testIsBool()
    {
        $this->assertTrue(Filter::isBool(true));
        $this->assertTrue(Filter::isBool(false));
        $this->assertTrue(Filter::isBool([false]));
        $this->assertTrue(Filter::isBool([true]));
        $this->assertTrue(Filter::isBool([true, false, true, false, true, false, true, false]));

        $this->assertFalse(Filter::isBool(null));
        $this->assertFalse(Filter::isBool(1));
        $this->assertFalse(Filter::isBool(0));
        $this->assertFalse(Filter::isBool(-1));
        $this->assertFalse(Filter::isBool(-0));
        $this->assertFalse(Filter::isBool(2));
        $this->assertFalse(Filter::isBool(''));
        $this->assertFalse(Filter::isBool([]));
        $this->assertFalse(Filter::isBool([1]));
        $this->assertFalse(Filter::isBool([0]));
        $this->assertFalse(Filter::isBool([null]));
        $this->assertFalse(Filter::isBool(['']));
        $this->assertFalse(Filter::isBool([true, 1]));
        $this->assertFalse(Filter::isBool([1, true]));
        $this->assertFalse(Filter::isBool([1, 2, 3, 4, 5, 6, true]));
        $this->assertFalse(Filter::isBool([true, 1, 2, 3, 4, 5, 6]));
        $this->assertFalse(Filter::isBool([false, '1', '2', '3', '4', '5', '6']));
        $this->assertFalse(Filter::isBool(['1', '2', '3', '4', '5', '6', false]));
        $this->assertFalse(Filter::isBool([true, false, true, false, true, false, false, null]));
        $this->assertFalse(Filter::isBool([false, false, false, true, false, false, 1, true, false]));
        $this->assertFalse(Filter::isBool([true, false, true, true, false, false, '', true, true]));

        //$this->assertTrue(Filter::isBool(array_merge(array_pad([], 500000, true), array_pad([], 500000, false))));
        //$this->assertFalse(Filter::isBool(array_merge(array_pad([], 500000, true), [1])));
    }


    public function testIsEmail()
    {
        $this->assertTrue(Filter::isEmail('a@b.c'));
        $this->assertTrue(Filter::isEmail('a1@b1.c1'));
        $this->assertTrue(Filter::isEmail('1@b.c'));
        $this->assertTrue(Filter::isEmail('vasya.ivanov@gmail.com'));
        $this->assertTrue(Filter::isEmail('vasya-ivanov@gmail.com'));
        $this->assertTrue(Filter::isEmail('a-b@c-d.ef'));
        $this->assertTrue(Filter::isEmail('a.-b@c-d.ef'));
        $this->assertTrue(Filter::isEmail('a.b-c@d-e.fg'));
        $this->assertTrue(Filter::isEmail('aa.bb-cc@dd-ee.fgfg'));
        $this->assertTrue(Filter::isEmail('aa.bb-cc.aa.bb-cc@dd-ee.fgfg'));
        $this->assertTrue(Filter::isEmail('a.-.b@c-d.ef'));
        $this->assertTrue(Filter::isEmail('i.-.-.-.-.-.i@yandex.ru'));
        $this->assertTrue(Filter::isEmail('aa.bb-cc-@dd-ee.fgfgfgfgfgfgfgfgfgfgfgfgfgfgfgfgfgfgfgfgfg'));
        $this->assertTrue(Filter::isEmail('aa-@d.f'));
        $this->assertTrue(Filter::isEmail('ac@d.fg.fg'));
        $this->assertTrue(Filter::isEmail('ac@d.fg-fg'));
        $this->assertTrue(Filter::isEmail(['a@b.c']));
        $this->assertTrue(Filter::isEmail(['a@b.c', 'ac@d.fg.fg', 'vasya-ivanov@gmail.com', 'a.-.b@c-d.ef']));

        $this->assertFalse(Filter::isEmail(0));
        $this->assertFalse(Filter::isEmail(1));
        $this->assertFalse(Filter::isEmail([]));
        $this->assertFalse(Filter::isEmail(true));
        $this->assertFalse(Filter::isEmail(false));
        $this->assertFalse(Filter::isEmail(null));
        $this->assertFalse(Filter::isEmail([1]));
        $this->assertFalse(Filter::isEmail([1, true, null]));
        $this->assertFalse(Filter::isEmail(''));
        $this->assertFalse(Filter::isEmail('1'));
        $this->assertFalse(Filter::isEmail('asdadas'));
        $this->assertFalse(Filter::isEmail('aa@-d.f'));
        $this->assertFalse(Filter::isEmail('ac@d.-fg'));
        $this->assertFalse(Filter::isEmail('ac@d.fg-'));
        $this->assertFalse(Filter::isEmail('ac@d..fg'));
        $this->assertFalse(Filter::isEmail('ac@d.fg.'));
        $this->assertFalse(Filter::isEmail('ac@d-fg-fg'));
        $this->assertFalse(Filter::isEmail('acd-fg-fg'));
        $this->assertFalse(Filter::isEmail('@acd-fg-fg'));
        $this->assertFalse(Filter::isEmail('acd@g'));
        $this->assertFalse(Filter::isEmail('acd@g-'));
        $this->assertFalse(Filter::isEmail('acd@'));
        $this->assertFalse(Filter::isEmail('acd@.'));
        $this->assertFalse(Filter::isEmail('acd@-t'));
        $this->assertFalse(Filter::isEmail('acd@-t.'));
        $this->assertFalse(Filter::isEmail('d@t'));
        $this->assertFalse(Filter::isEmail('d@t.'));
        $this->assertFalse(Filter::isEmail('1@2.3'));
        $this->assertFalse(Filter::isEmail('a@a.3'));
        $this->assertFalse(Filter::isEmail(['a@a.3']));
        $this->assertFalse(Filter::isEmail(['a@a.3', 'd@t', 'acd@', 'ac@d.fg.']));
        $this->assertFalse(Filter::isEmail(['a@b.c', 'ac@d.fg.fg', 'vasya-ivanov@gmail.com', 'a.-.b@c-d.ef', 'a@a.3']));
    }


    public function testIsIP()
    {
        $this->assertTrue(Filter::isIP('192.168.1.1'));
        $this->assertTrue(Filter::isIP('10.0.0.1'));
        $this->assertTrue(Filter::isIP('127.0.0.1'));
        $this->assertTrue(Filter::isIP('255.255.255.0'));
        $this->assertTrue(Filter::isIP('255.255.255.255'));
        $this->assertTrue(Filter::isIP('255.255.255.255'));
        $this->assertTrue(Filter::isIP('10.0.0.0'));
        $this->assertTrue(Filter::isIP('192.168.0.0'));
        $this->assertTrue(Filter::isIP('172.16.0.0'));
        $this->assertTrue(Filter::isIP('0.0.0.0'));
        $this->assertTrue(Filter::isIP('169.254.0.0'));
        $this->assertTrue(Filter::isIP('192.0.2.0'));
        $this->assertTrue(Filter::isIP('224.0.0.0'));
        $this->assertTrue(Filter::isIP(['224.0.0.0']));
        $this->assertTrue(Filter::isIP(['224.0.0.0', '192.168.1.1', '10.0.0.0']));

        $this->assertFalse(Filter::isIP('0.0.0.0', FILTER_FLAG_NO_RES_RANGE));
        $this->assertFalse(Filter::isIP('169.254.0.0', FILTER_FLAG_NO_RES_RANGE));
        $this->assertFalse(Filter::isIP('192.0.2.0', FILTER_FLAG_NO_RES_RANGE));
        $this->assertFalse(Filter::isIP('224.0.0.0', FILTER_FLAG_NO_RES_RANGE));
        $this->assertFalse(Filter::isIP('10.0.0.0', FILTER_FLAG_NO_PRIV_RANGE));
        $this->assertFalse(Filter::isIP('192.168.0.0', FILTER_FLAG_NO_PRIV_RANGE));
        $this->assertFalse(Filter::isIP('172.16.0.0', FILTER_FLAG_NO_PRIV_RANGE));
        $this->assertFalse(Filter::isIP('224.0.0.0/24'));
        $this->assertFalse(Filter::isIP(0));
        $this->assertFalse(Filter::isIP(1));
        $this->assertFalse(Filter::isIP([]));
        $this->assertFalse(Filter::isIP(true));
        $this->assertFalse(Filter::isIP(false));
        $this->assertFalse(Filter::isIP(null));
        $this->assertFalse(Filter::isIP([1]));
        $this->assertFalse(Filter::isIP([1, true, null]));
        $this->assertFalse(Filter::isIP(''));
        $this->assertFalse(Filter::isIP('1'));
        $this->assertFalse(Filter::isIP('asdadas'));
        $this->assertFalse(Filter::isIP('0xAA.0xBB.0xCC.0xDD'));
        $this->assertFalse(Filter::isIP(['0xAA.0xBB.0xCC.0xDD']));
        $this->assertFalse(Filter::isIP(['192.168.1.1', '0xAA.0xBB.0xCC.0xDD']));
    }


    public function testIsMAC()
    {
        $this->assertTrue(Filter::isMAC('00-15-F2-20-4D-6B'));
        $this->assertTrue(Filter::isMAC('00-00-00-00-00-00'));
        $this->assertTrue(Filter::isMAC('01-01-01-01-01-01'));
        $this->assertTrue(Filter::isMAC('B9-A0-D9-F9-C9-E9'));
        $this->assertTrue(Filter::isMAC('FF-FF-FF-FF-FF-FF'));
        $this->assertTrue(Filter::isMAC('0F-0F-0F-0F-0F-0F'));
        $this->assertTrue(Filter::isMAC(['0F-0F-0F-0F-0F-0F']));
        $this->assertTrue(Filter::isMAC(['0F-0F-0F-0F-0F-0F', 'B9-A0-D9-F9-C9-E9', '00-15-F2-20-4D-6B']));

        $this->assertFalse(Filter::isMAC(0));
        $this->assertFalse(Filter::isMAC(1));
        $this->assertFalse(Filter::isMAC([]));
        $this->assertFalse(Filter::isMAC(true));
        $this->assertFalse(Filter::isMAC(false));
        $this->assertFalse(Filter::isMAC(null));
        $this->assertFalse(Filter::isMAC([1]));
        $this->assertFalse(Filter::isMAC([1, true, null]));
        $this->assertFalse(Filter::isMAC(''));
        $this->assertFalse(Filter::isMAC('1'));
        $this->assertFalse(Filter::isMAC(['1']));
        $this->assertFalse(Filter::isMAC('asdadas'));
        $this->assertFalse(Filter::isMAC('0xAA.0xBB.0xCC.0xDD'));
        $this->assertFalse(Filter::isMAC('FF-FF-FF-F-FF-FF'));
        $this->assertFalse(Filter::isMAC('FF-FF-0-F-FF-FF'));
        $this->assertFalse(Filter::isMAC('FF-FF-F0-F-FF'));
        $this->assertFalse(Filter::isMAC('FF-FF-F0-F-FF-1'));
        $this->assertFalse(Filter::isMAC('F-FF-F0-F-FF-1A'));
        $this->assertFalse(Filter::isMAC('F-FF-F0-Fs-FF-1A'));
        $this->assertFalse(Filter::isMAC('F-FF-F0-1q-FF-1A'));
        $this->assertFalse(Filter::isMAC('0-0-0-0-0-0'));
        $this->assertFalse(Filter::isMAC(['0-0-0-0-0-0']));
        $this->assertFalse(Filter::isMAC(['F-FF-F0-1q-FF-1A', '0-0-0-0-0-0', 'FF-FF-0-F-FF-FF']));
        $this->assertFalse(Filter::isMAC(['0F-0F-0F-0F-0F-0F', 'B9-A0-D9-F9-C9-E9', '00-15-F2-20-4D-6B', 'F-FF-F0-1q-FF-1A']));
    }


    public function testIsUrl()
    {
        $this->assertTrue(Filter::isUrl('http://google.ru'));
        $this->assertTrue(Filter::isUrl('http://www.google.ru'));
        $this->assertTrue(Filter::isUrl('http://vk.com'));
        $this->assertTrue(Filter::isUrl('http://new-site.com'));
        $this->assertTrue(Filter::isUrl('http://www.new-site.com'));
        $this->assertTrue(Filter::isUrl('http://subdomain.new-site.com'));
        $this->assertTrue(Filter::isUrl('http://sub2.new-site.com'));
        $this->assertTrue(Filter::isUrl('http://sub2.site2.com'));
        $this->assertTrue(Filter::isUrl('http://sub2.site2-dev.com'));
        $this->assertTrue(Filter::isUrl('https://sub2.site2-dev.com2'));
        $this->assertTrue(Filter::isUrl('https://sub2.site2-dev.info'));
        $this->assertTrue(Filter::isUrl('https://www2.sub2.site2-dev.info'));
        $this->assertTrue(Filter::isUrl('https://www2.sub2.site2-dev.info'));
        $this->assertTrue(Filter::isUrl('https://a.b'));
        $this->assertTrue(Filter::isUrl('https://a.b.c'));
        $this->assertTrue(Filter::isUrl('http://192.168.1.1'));
        $this->assertTrue(Filter::isUrl('https://www2.sub2.site2-dev.'));
        $this->assertTrue(Filter::isUrl(['https://a.b.c']));
        $this->assertTrue(Filter::isUrl(['https://a.b.c', 'https://a.b', 'http://vk.com']));

        $this->assertFalse(Filter::isUrl(0));
        $this->assertFalse(Filter::isUrl(1));
        $this->assertFalse(Filter::isUrl([]));
        $this->assertFalse(Filter::isUrl(true));
        $this->assertFalse(Filter::isUrl(false));
        $this->assertFalse(Filter::isUrl(null));
        $this->assertFalse(Filter::isUrl([1]));
        $this->assertFalse(Filter::isUrl([1, true, null]));
        $this->assertFalse(Filter::isUrl(''));
        $this->assertFalse(Filter::isUrl('1'));
        $this->assertFalse(Filter::isUrl(['1']));
        $this->assertFalse(Filter::isUrl('asdadas'));
        $this->assertFalse(Filter::isUrl('0xAA.0xBB.0xCC.0xDD'));
        $this->assertFalse(Filter::isUrl('FF-FF-FF-F-FF-FF'));
        $this->assertFalse(Filter::isUrl('https://www2.sub2.site2-dev..'));
        $this->assertFalse(Filter::isUrl('dev.info'));
        $this->assertFalse(Filter::isUrl('www.dev.info'));
        $this->assertFalse(Filter::isUrl('http://www..info'));
        $this->assertFalse(Filter::isUrl('http://..info.a'));
        $this->assertFalse(Filter::isUrl(['http://..info.a']));
        $this->assertFalse(Filter::isUrl(['http://..info.a', 'www.dev.info']));
        $this->assertFalse(Filter::isUrl(['https://a.b.c', 'https://a.b', 'http://vk.com', 'www.dev.info']));
    }


    public function testDateRus()
    {
        $this->assertEquals('01 января 2000',   Filter::getDatetime(new DateTime('2000-01-01'), '%d %bg %Y'));
        $this->assertEquals('01 января 2000',   Filter::getDatetime((new DateTime('2000-01-01'))->getTimestamp(), '%d %bg %Y'));
        $this->assertEquals(' 1 января 2000',   Filter::getDatetime('2000-01-01', '%e %bg %Y'));
        $this->assertEquals(' 2 февраля 2001',  Filter::getDatetime('2001-02-02', '%e %bg %Y'));
        $this->assertEquals(' 4 марта 2002',    Filter::getDatetime('2002-03-04', '%e %bg %Y'));
        $this->assertEquals(' 6 апреля 2003',   Filter::getDatetime('2003-04-06', '%e %bg %Y'));
        $this->assertEquals(' 8 мая 2004',      Filter::getDatetime('2004-05-08', '%e %bg %Y'));
        $this->assertEquals('10 июня 2005',     Filter::getDatetime('2005-06-10', '%e %bg %Y'));
        $this->assertEquals('12 июля 2006',     Filter::getDatetime('2006-07-12', '%e %bg %Y'));
        $this->assertEquals('14 августа 2007',  Filter::getDatetime('2007-08-14', '%e %bg %Y'));
        $this->assertEquals('16 сентября 2008', Filter::getDatetime('2008-09-16', '%e %bg %Y'));
        $this->assertEquals('18 октября 2009',  Filter::getDatetime('2009-10-18', '%e %bg %Y'));
        $this->assertEquals('20 ноября 2010',   Filter::getDatetime('2010-11-20', '%e %bg %Y'));
        $this->assertEquals('31 декабря 2011',  Filter::getDatetime('2011-12-31', '%e %bg %Y'));
        $this->assertEquals(' 9 мая',           Filter::getDatetime('2015-05-09', '%e %bg'));

        $this->assertEquals(' 5 апреля 2016',   Filter::getDatetime('1459855490', '%e %bg %Y'));
        $this->assertEquals('01 января 1970',   Filter::getDatetime('1000'));
        $this->assertEquals('01 января 1970',   Filter::getDatetime('2000'));
        $this->assertEquals('01 января 1970',   Filter::getDatetime('9000'));
        $this->assertEquals('01 августа 2005',  Filter::getDatetime('2005-08'));
        $this->assertEquals('31 октября 1966',  Filter::getDatetime(-100000000));
        $this->assertEquals('10 февраля 1653',  Filter::getDatetime(-1000000000));
    }


    public function testIsDatetime()
    {
        $this->assertTrue(Filter::isDatetime('2005-08'));
        $this->assertTrue(Filter::isDatetime('2005-08-24'));
        $this->assertTrue(Filter::isDatetime('2005-08-24 12:35'));
        $this->assertTrue(Filter::isDatetime('2005-08-24 23:35:48'));
        $this->assertTrue(Filter::isDatetime('1000'));
        $this->assertTrue(Filter::isDatetime('9000'));
        $this->assertTrue(Filter::isDatetime('1459855490'));
        $this->assertTrue(Filter::isDatetime(1459855490));
        $this->assertTrue(Filter::isDatetime([1459855490, '1459755490', 1259855490, '2005-08-24 23:35:48', '2005-08-24']));


        $this->assertFalse(Filter::isDatetime('2005-08-24 23'));
        $this->assertFalse(Filter::isDatetime('2'));
        $this->assertFalse(Filter::isDatetime('200'));
        $this->assertFalse(Filter::isDatetime('999'));
        $this->assertFalse(Filter::isDatetime(''));
        $this->assertFalse(Filter::isDatetime('a'));
        $this->assertFalse(Filter::isDatetime(true));
        $this->assertFalse(Filter::isDatetime(false));
        $this->assertFalse(Filter::isDatetime(null));
    }
}
 