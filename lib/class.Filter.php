<?php
/**
 * Filter сlass         (PHP 5 >= 5.3.0)
 * Special thanks to:   all, http://www.php.net
 * Copyright (c)        viktor, Belgorod, 2010-2016
 * Email		        vinjoy@bk.ru
 * version		        2.0.0
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the MIT License (MIT)
 * @see https://opensource.org/licenses/MIT
 */

require_once('class.BaseException.php');



/** Собственное исключение для класса */
class FilterException extends BaseException{ }



/**
 * Класс фильтрации параметров 
 * @author    Enjoy
 * @version   2.0.0
 * @package   Micr0
 */
class Filter {

    /**
     * Замена элементов массива $arg или всех параметров метода, начиная с [1], на результат применения к ним функции $func
     * @param callable $func
     * @param mixed $arg
     * @return mixed
     * @throws FilterException
     */
    public static function map(callable $func, $arg) {
        if (func_num_args() == 2) {
            if (is_array($arg)) {
                $result = array_map($func, $arg);
            } else {
                $result = $func($arg);
            }
        }else{
            $result = array_map($func, func_get_args());
        }
        return $result;
    }



    /**
     * Применение ко всем элементам массива $arg или всем параметрам метода, начиная с [1], функции $func и логическое сложение && результатов
     * Прерывается при получении первого false в результате выполнения $func
     * @param callable $func Функция вида bool function(mixed $el){...}
     * @param mixed $arg
     * @return bool
     * @throws FilterException
     */
    public static function mapBool(callable $func, $arg) {
        $map = function($arr) use ($func){
            $result = false;
            $i = 0;
            while ($i < count($arr) && $result){
                $result = $i == 0
                    ? $func($arr[$i])
                    : $result && $func($arr[$i]);
                $i++;
            }
            return $result;
        };

        if (func_num_args() == 2) {
            if (is_array($arg)) {
                $result = $map($arg);
            } else {
                $result = $func($arg);
            }
        }else{
            $result = $map(func_get_args());
        }
        return $result;
    }



    /**
     * Проверка первого параметра на принадлежность к типу, указанному во втором
     * @param mixed|array $var Переменная или массив переменных для проверки
     * @param mixed $type Тип данных
     * @return bool
     */
    public static function is($var, $type){
        return self::mapBool(
            function ($el) use ($type){
                return is_a($el, $type, false);
            },
            $var
        );
    }



    /** 
     * Проверка целочисленного числа на попадание в заданный отрезок
     * @param int|array $var Аргумент, или массив аргументов функции
     * @param int $from Начало диапозона допустимых значений
     * @param int $to Конец диапозона допустимых значений
     * @assert (0, 0, 0) == true
     * @assert (0, 0, 1) == true
     * @assert (1, 0, 1) == true
     * @assert (0, -1, 1) == true
     * @assert (-2, -3, -1) == true
     * @assert (2, 1.1, 2.1) == true
     * @assert (1, 0, 2) == true
     * @assert (1, 2, 3) == false
     * @assert (4, 1, 3) == false
     * @assert (-1, -3, -2) == false
     * @assert (1.2, 0, 3) == false
     * @return bool
     */
    public static function isIntegerBetween($var, $from, $to) {
        return self::mapBool(
            function ($el) use ($from, $to){
                return is_int($el) && ($el >= $from) && ($el <= $to);
            },
            $var
        );
    }



    /**
     * Проверка даты на попадание в интервал 
     * @param mixed|array $var Аргумент, или массив аргументов функции
     * @param datetime $from Начало диапозона допустимых значений
     * @param datetime $to Конец диапозона допустимых значений
     * @return bool
     */
    public static function isDateBetween($var, $from, $to) {
        /** @todo Дописать метод isDateBetween */
        return 1 / 0;
    }



    /**
     * Проверка одного параметра на строку
     * @param string|array $var Аргумент, или массив аргументов функции
     * @return bool
     */
    public static function isString($var) {
        return self::mapBool('is_string', $var);
    }



    /**
     * Проверка одного параметра на массив
     * @param array $var Аргумент, или массив аргументов функции
     * @return bool
     */
    public static function isArray($var) {
        return self::mapBool('is_array', $var);
    }



    /**
     * Проверка одного параметра на логическое значение
     * @param bool|array $var Аргумент, или массив аргументов функции
     * @return bool
     */
    public static function isBool($var) {
        return self::mapBool('is_bool', $var);
    }



    /**
     * Проверка одного числа на вещественное число
     * @param float|array $var Аргумент, или массив аргументов функции
     * @return bool
     */
    public static function isNumeric($var) {
        return self::mapBool('is_numeric', $var);
    }



    /**
     * Проверка одного числа на целочисленность
     * @param int|array $var Аргумент, или массив аргументов функции
     * @return bool
     */
    public static function isInteger($var) {
        return self::mapBool('is_int', $var);
    }



    /** 
     * Проверка одного числа на натуральность 
     * @param int|array $var Аргумент, или массив аргументов функции
     * @return bool
     */
    public static function isNatural($var) {
        return self::mapBool(
            function($el){
                return is_int($el) && $el >= 0;
            },
            $var
        );
    }



    /** 
     * Проверка одного аргумента на правильну дату формата "yyyy-mm-dd"
     * @param datetime|array $var Аргумент, или массив аргументов функции
     * @param string $formatExpr Регулярное выражение для проверки формата даты
     * @return bool
     */
    public static function isDate($var, $formatExpr = '/^(\d{4})\-(\d{2})\-(\d{2})$/') {
        return self::mapBool(
            function($el) use ($formatExpr){
                return (preg_match($formatExpr, $el, $d))
                && checkdate($d[2], $d[3], $d[1]);
            },
            $var
        );
    }



    /** 
     * Проверка одного аргумента на правильну дату и время формата "yy-mm-dd hh:mm:ss"
     * @param datetime|array $var Аргумент, или массив аргументов функции
     * @return bool
     */
    public static function isDatetime($var) {
        $func = function ($el){
            function checktime($hour, $min, $sec) {
                if (strlen($hour) == 2 && $hour{0} == '0'){ $hour = $hour{1}; }
                if ($hour < 0 || $hour > 23 || !is_int($hour)) {
                    return false;
                }
                if (strlen($min) == 2 && $min{0} == '0'){ $min = $min{1}; }
                if ($min < 0 || $min > 59 || !is_int($min)) {
                    return false;
                }
                if (strlen($sec) == 2 && $sec{0} == '0'){ $sec = $sec{1}; }
                if ($sec < 0 || $sec > 59 || !is_int($sec)) {
                    return false;
                }
                return true;
            }
            return preg_match('/^(\d{4})\-(\d{2})\-(\d{2}) ([0-1][0-9]|[2][0-3]):([0-5][0-9]):([0-5][0-9])$/', $el, $d)
                && checkdate($d[2], $d[3], $d[1])
                && checktime($d[4], $d[5], $d[6]);
        };
        return self::mapBool($func, $var);
    }






# - - - - - - - - - - - - - - - - - - - - - - - - - - - Функции экранирования - - - - - - - - - - - - - - - - - - - - - - - - - - - - #

    /**
     * Замена html-тегов и спецсимволов их html-сущностями
     * @param string|array $var Обрабатываемая строка или массив строк
     * @param int $flags Способ обработки кавычек, аналогичен второму параметру htmlspecialchars
     * @return string
     */
    public static function htmlEncode($var, $flags = ENT_QUOTES) {
        if ($flags === null){
            $flags = ENT_COMPAT | ENT_HTML401;
        }
        return self::map(
            function ($el) use ($flags){
                return htmlspecialchars($el, $flags);
            },
            $var
        );
    }

    /**
     * Замена html-сущностей тегов их реальными символами 
     * @param string|array $var Обрабатываемая строка или массив строк
     * @param int $flags Способ обработки кавычек, аналогичен второму параметру htmlspecialchars_decode
     * @return string
     */
    public function htmlDecode($var, $flags = ENT_QUOTES) {
        if ($flags === null){
            $flags = ENT_COMPAT | ENT_HTML401;
        }
        return self::map(
            function ($el) use ($flags){
                return htmlspecialchars_decode($el, $flags);
            },
            $var
        );
    }



    /**
     * Экранирование спесцимволов в стиле языка С
     * @param string|array $var Обрабатываемая строка или массив строк
     * @param string $charList Список экранируемых символов
     * @return mixed
     * @throws FilterException
     */
    public static function slashesAdd($var, $charList = '') {
        return self::map(
            function ($el) use ($charList){
                return addcslashes($el, $charList);
            },
            $var
        );
    }

    /**
     * Отмена экранирования спесцимволов в стиле языка С
     * @param string|array $var Обрабатываемая строка или массив строк
     * @param string $charList Список экранируемых символов
     * @return mixed
     * @throws FilterException
     */
    public static function slashesStrip($var, $charList = '') {
        return self::map(
            function ($el) use ($charList){
                return stripcslashes($el, $charList);
            },
            $var
        );
    }



    
    
# - - - - - - - - - - - - - - - - - - - - - - - Функции обработки строк и массивов - - - - - - - - - - - - - - - - - - - - - - - - - #
    
    /** 
     * Переиндексация ассоциативного двухмерного массива по указанному индексу в строках
     * @param array $arr Переиндексовываемый массив
     * @param string $index Новый индекс - один из индексов во всех строках массива. Сохраняется первое вхождение всех дублируемых индексов
     * @return array
     */
    public static function arrayReindex($arr, $index){
        $result = [];
        foreach ($arr as $el){
            if (isset($el[$index]) && !isset($result[$el[$index]])){
                $result[$el[$index]] = $el;
            }
        }
        return $result;
    }    



    /**
     * Выбирает из двухмерного массива множество значений столбца
     * @param array $arr Исходный массив
     * @param string $index 
     * @param bool $arrayReindex Флаг, указывающий та то, что индексация результата будет проведена значениями полученного массива
     * @return array
     */
    public static function arrayExtract($arr, $index, $arrayReindex = false){
        $result = [];
        if ($arrayReindex){
             foreach ($arr as $el){
                if (isset($el[$index]) && !isset($result[$el[$index]])){
                    $result[$el[$index]] = $el[$index];
                }
            }
        }else{
            foreach ($arr as $el){
                if (isset($el[$index]) && (array_search($el[$index], $result) === false)){
                    $result[] = $el[$index];
                }
            }
        }
        return $result;
    }



    /**
     * Замена указанной подстроки или указанных подстрок на другую подстроку(подстроки).
     * @param string|array $search Старая подстрока(подстроки)
     * @param string|array $replacement Новая подстрока(подстроки)
     * @param string|array $subject Обрабатываемая строка, или массив строк
     * @return string|array
     */
    public static function strReplace($search, $replacement, $subject){
        $func = function ($el) use ($search, $replacement){
            return str_replace($search, $replacement, $el);
        };
        return is_array($subject)
            ? $func($subject)
            : self::map($func, $subject);
    }



    /** 
     * Ограничивает строку указанной длинной 
     * @param string|array $var Обрабатываемая строка, или массив строк
     * @param int $length Длина, до которой сокращается строка
     * @param string $strEnd Окончание укорачиваемой строки
     * @param string $encoding Кодировка
     * @return string
     */
    public static function strTrim($var, $length, $strEnd = '..', $encoding = null){
        $encoding = $encoding === null ? mb_internal_encoding() : $encoding;
        $func = function ($el) use ($length, $strEnd, $encoding){
            return mb_strimwidth($el, 0, $length, $strEnd, $encoding);
        };
        return is_array($var)
            ? $func($var)
            : self::map($func, $var);
    }



    /**
     * Получение подстроки $str, заключенной между $sMarker и $fMarker
     * @param string $str Строка, в которой ищется подстрока
     * @param string $sMarker Маркер начала
     * @param string $fMarker Маркер конца
     * @param int $initOffset
     * @return string
     * Похоже, что тут вызов от массива строк не нужен
     */
    public static function strBetween($str, $sMarker, $fMarker, $initOffset = 0) {
        $result = '';
        $s = stripos($str, $sMarker, $initOffset);
        if ($s !== false) {
            $s += strlen($sMarker);
            $f = stripos($str, $fMarker, $s);
            if ($f !== false)
                $result = substr($str, $s, $f - $s);
        }
        return $result;
    }



    /**
     * Увеличение строки до $padLength символов. Многобайтовая версия
     * @param string|array $var Исходная строка, или массив строк
     * @param int $padLength Длина, до которой будет дополняться исходная строка
     * @param string $padStr Строка, которой будет дополняться исходная строка
     * @param int $direct Направление дополнения - STR_PAD_RIGHT, STR_PAD_LEFT, STR_PAD_BOTH
     * @param string $encoding Кодировка
     * @return string
     * @see http://php.net/manual/ru/function.str-pad.php#116244
     */
    public static function strPad($var, $padLength, $padStr = ' ', $direct = STR_PAD_RIGHT, $encoding = null){
        $encoding = $encoding === null ? mb_internal_encoding() : $encoding;
        $func = function ($el) use ($padLength, $padStr, $direct, $encoding) {
            $padBefore = $direct === STR_PAD_BOTH || $direct === STR_PAD_LEFT;
            $padAfter = $direct === STR_PAD_BOTH || $direct === STR_PAD_RIGHT;
            $padLength -= mb_strlen($el, $encoding);
            $targetLen = $padBefore && $padAfter ? $padLength / 2 : $padLength;
            $strToRepeatLen = mb_strlen($padStr, $encoding);
            $repeatTimes = ceil($targetLen / $strToRepeatLen);
            $repeatedString = str_repeat($padStr, max(0, $repeatTimes)); // safe if used with valid utf-8 strings
            $before = $padBefore ? mb_substr($repeatedString, 0, floor($targetLen), $encoding) : '';
            $after = $padAfter ? mb_substr($repeatedString, 0, ceil($targetLen), $encoding) : '';
            return $before . $el . $after;
        };
        return is_array($var)
            ? $func($var)
            : self::map($func, $var);
    }

}

