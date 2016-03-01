<?php

/**
 * 	Filter сlass (PHP 5 >= 5.0.0)
 * 	Special thanks to: http://www.php.su
 * 	Copyright (c)   Enjoy! Belgorod, 2010-2011
 * 	Email		vinjoy@bk.ru
 * 	version		1.2.13
 * 	Last modifed	23:42 11.10.2014
 * 	
 * 	 This library is free software; you can redistribute it and/or
 * 	modify it under the terms of the GNU Lesser General Public
 * 	License as published by the Free Software Foundation; either
 * 	version 2.1 of the License, or (at your option) any later version.
 * 	@see http://www.gnu.org/copyleft/lesser.html
 * 	
 * 	Не удаляйте данный комментарий, если вы хотите использовать скрипт! 
 * 	Do not delete this comment if you want to use the script! *
 */

/**
 * Класс фильтрации параметров 
 * @author    Enjoy
 * @version   1.2.12
 * @package   se-engine
 * @copyright Enjoy
 */
class Filter {


    /**
     * Проверка первого параметра на принадлежность к типу, указанному во втором
     * @param mixed $var Переменная для проверки
     * @param mixed $type Тип данных
     * @return bool
     */
    public static function is($var, $type){
        return $var instanceof $type;
    }



    /** 
     * Проверка целочисленного числа на попадание в заданный отрезок
     * @param int $argument Аргумент функции
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
    public static function isIntegerBetween($argument, $from, $to) {
        return self::isInteger($argument) && ($argument >= $from) && ($argument <= $to);
    }

    /**
     * Проверка даты на попадание в интервал 
     * @param datetime $argument Аргумент функции
     * @param datetime $from Начало диапозона допустимых значений
     * @param datetime $to Конец диапозона допустимых значений
     * @return bool
     */
    public static function isDateBetween($argument, $from, $to) {
        /** @todo Дописать метод isDateBetween */
        throw new Exception('Осеньнно реализации метода не хватает, насяльника!');
        return false;
    }

    /**
     * Проверка одного параметра на строку
     * @param string $argument Аргумент функции
     * @return bool
     */
    public static function isString($argument) {
        return is_string($argument);
    }


    /**
     * Проверка одного параметра на логическое значение
     * @param bool $argument Аргумент функции
     * @return bool
     */
    public static function isBool($argument) {
        return is_bool($argument);
    }


    /** 
     * Проверка одного числа на натуральность 
     * @param int $argument Аргумент функции
     * @return bool
     */
    public static function isNatural($argument) {
        return is_int($argument) && $argument >= 0;
    }

    /** 
     * Проверка одного числа на целочисленность 
     * @param int $argument Аргумент функции
     * @return bool
     */
    public static function isInteger($argument) {
        return is_int($argument);
    }

    /** 
     * Проверка одного числа на вещественное число 
     * @param float $argument Аргумент функции
     * @return bool
     */
    public static function isNumeric($argument) {
        return is_numeric($argument);
    }

    /** 
     * Проверка одного аргумента на правильну дату формата "yyyy-mm-dd"
     * @param datetime $argument Аргумент функции
     * @return bool
     */
    public static function isDate($argument) {
        if (preg_match('/^(\d{4})\-(\d{2})\-(\d{2})$/', $argument, $date)) {
            return checkdate($date[2], $date[3], $date[1]);
        } else {
            return false;
        }
    }

    /** 
     * Проверка одного аргумента на правильну дату и время формата "yy-mm-dd hh:mm:ss"
     * @param datetime $argument Аргумент функции
     * @return bool
     */
    public static function isDatetime($argument) {
        if (preg_match('/^(\d{4})\-(\d{2})\-(\d{2}) ([0-1][0-9]|[2][0-3]):([0-5][0-9]):([0-5][0-9])$/', $argument, $date)) {
            return checkdate($date[2], $date[3], $date[1]);
        } else {
            return false;
        }
    }

    /** Проверка всех параметров на целочисленность */
    public static function isInt() {
        $argsCount = func_num_args();
        $result = true;
        $i = 0;
        while ($result && ($i < $argsCount)) {
            $argument = func_get_arg($i++);
            $result = self::isInteger($argument);
        }
        return $result;
    }

    /** Проверка всех параметров на натуральность */
    public static function isNat() {
        $argsCount = func_num_args();
        $result = true;
        $i = 0;
        while ($result && ($i < $argsCount)) {
            $argument = func_get_arg($i++);
            $result = self::isNatural($argument);
        }
        return $result;
    }

    /** Проверка массива на целочисленность элементов */
    public static function isIntArray($arr) {
        $result = false;
        if (self::isArray($arr)) {
            $result = (bool) count($arr);
            foreach ($arr as $el) {
                $result = self::isInteger($el);
                if (!$result) {
                    break;
                }
            }
        }
        return $result;
    }

    /** Проверка параметров на вещественные числа */
    public static function isNumerics() {
        $argsCount = func_num_args();
        $result = true;
        $i = 0;
        while ($result && ($i < $argsCount)) {
            $argument = func_get_arg($i++);
            $result = self::isNumeric($argument);
        }
        return $result;
    }
    
    /** Проверка параметра на массив */
    public static function isArray($arg) {
        return is_array($arg);
    }    

    /** Проверка всех параметров на массив */
    public static function isArrays() {
        $argsCount = func_num_args();
        $result = true;
        $i = 0;
        while ($result && ($i < $argsCount)) {
            $argument = func_get_arg($i++);
            $result = self::isArray($argument);
        }
        return $result;
    }

    /** Проверка массива на вещественность элементов */
    public static function isNumericArray($arr) {
        $result = false;
        if (self::isArray($arr)) {
            $result = (bool) count($arr);
            $i = 0;
            $argsCount = count($arr);
            while ($result && ($i < $argsCount)) {
                $result = self::isNumeric($arr[$i++]);
            }
        }
        return $result;
    }

    /** Проверка всех параметров на правильные даты */
    public static function isDateAll() {
        $argsCount = func_num_args();
        $result = true;
        $i = 0;
        while ($result && ($i < $argsCount)) {
            $argument = func_get_arg($i++);
            $result = self::isDate($argument);
        }
        return $result;
    }

    /** Проверка элементов массива на правильные даты */
    public static function isDateArray($arr) {
        $result = false;
        if (self::isArray($arr)) {
            $result = (bool) count($arr);
            $i = 0;
            $argsCount = count($arr);
            while ($result && ($i < $argsCount)) {
                $result = self::isDate($arr[$i++]);
            }
        }
        return $result;
    }
    
    /** 
     * Замена указанной подстроки или указанных подстрок на другую подстроку(подстроки).
     * @param mixed $search Старая подстрока(подстроки)
     * @param mixed $replace Новая подстрока(подстроки)
     * @param string $subject Обрабатываемая строка
     * @return string|array
     */
    public static function strReplace($search, $replace, $subject){
        $result = $subject;
        if (self::isArray($search)){
            if (self::isArray($replace)){
                foreach ($search as $index => $searchItem){
                    $result = str_replace($searchItem, $replace[$index], $result);
                }
            }else{
                foreach ($search as $index => $searchItem){
                    $result = str_replace($searchItem, $replace, $result);
                }                
            }
        }else{        
            $result = str_replace($search, $replace, $result);
        }
        return $result;
    }

    /**
     * Экранирование спецсимволов SQL 
     * @param string $argument Обрабатываемая строка
     * @return string
     */
    public static function sqlFilter($argument) {
        return Db::quote($argument);
    }
    
    
    /**
     * Экранирование спецсимволов SQL в массиве
     * @param array $arr Обрабатываемый массив
     * @return string
     */
    public static function sqlFilterArray($arr) {
        if (self::isArray($arr)) {
            foreach ($arr as $i => $el) {
                $arr[$i] = self::sqlFilter($el);
            }
        }
        return $arr;
    }
      

    /** Нерекурсивное экранирование спецсимволов SQL */
    public static function sqlFilterAll() {
        $argsCount = func_num_args();
        if ($argsCount == 0) {
            trigger_error(Ex::E_BAD_DATA, E_USER_WARNING);
        } else {
            if ($argsCount > 1) {
                $result = func_get_args();
                foreach ($result as $key => $el) {
                    $result[$key] = self::sqlFilter($el);
                }
            } else {
                $result = func_get_arg(0);
                if (self::isArray($result)) {
                    foreach ($result as $key => $el) {
                        $result[$key] = self::sqlFilter($el);
                    }
                } else {
                    $result = self::sqlFilter($result);
                }
            }
        }
        return $result;
    }

    /** Удаление экранирования спецсимволов SQL у одного аргумента */
    public static function sqlUnfilter($argument) {
        /** @todo разобраться, почему всё так сложно в preg_replace */
        //return stripslashes(preg_replace("/(?<!\\\\)\\\\n/", "\n", $argument));
        return stripslashes($argument);
    }

    /** Удаление экранирования спецсимволов SQL */
    public static function sqlUnfilterAll() {
        $argsCount = func_num_args();
        if ($argsCount < 1) {
            return null;
        } else {
            if ($argsCount > 1) {
                $result = array();
                for ($i = 0; $i < $argsCount; $i++) {
                    $argument = func_get_arg($i);
                    $result[] = self::sqlUnfilter($argument);
                }
            } else {
                $result = func_get_arg(0);
                if (self::isArray($result)) {
                    foreach ($result as $key => $el) {
                        $result[$key] = self::sqlUnfilter($el);
                    }
                } else {
                    $result = self::sqlUnfilter($result);
                }
            }
        }
        return $result;
    }

    /**
     * Замена кавычек их ASCII-представлением
     * @param string $str Строка для экранирования
     * @return string
     */
    function filterQuotes($str) {
        return str_replace('"', '&#34;', str_replace("'", '&#39;', $str));
    }

    /**
     * Замена html-тегов и спецсимволов их html-сущностями
     * @param string $argument Обрабатываемая строка
     * @param int $quoteStyle,... Способ обработки кавычек, аналогичен второму параметру htmlspecialchars
     * @return string
     */
    public static function htmlFilter($argument, $quoteStyle = ENT_QUOTES) {
        return htmlspecialchars($argument, $quoteStyle);
    }

    /** Экранирвоание тегов и спецсимволов HTML */
    public static function htmlFilterAll() {
        $argsCount = func_num_args();
        if (!$argsCount) {
            return null;
        } else {
            if ($argsCount == 1) {
                $result = func_get_arg(0);
                if (self::isArray($result)) {
                    foreach ($result as $key => $el) {
                        $result[$key] = self::htmlFilter($result[$key]);
                    }
                } else {
                    $result = self::htmlFilter($result);
                }
            } else {
                $result = array();
                for ($i = 0; $i < $argsCount; $i++) {
                    $argument = func_get_arg($i);
                    $result[] = self::htmlFilter($argument);
                }
            }
        }
        return $result;
    }

    /**
     * Замена html-сущностей тегов их реальными символами 
     * @param string $argument Обрабатываемая строка
     * @param int $quoteStyle Способ обработки кавычек, аналогичен второму параметру htmlentities
     * @return string
     */
    public function htmlUnfilter($argument, $quoteStyle = ENT_QUOTES) {
        return htmlentities($argument, $quoteStyle);
    }

    /** Экранирование спесцимволов в стиле языка С одного аргумента */
    public static function cFilter($argument) {
        return addcslashes($argument, '');
    }

    /** Экранирование спесцимволов в стиле языка С */
    public static function cFilterAll() {
        $argsCount = func_num_args();
        if (!$argsCount) {
            return null;
        } else {
            if ($argsCount == 1) {
                $result = func_get_arg(1);
                if (self::isArray($result)) {
                    foreach ($result as $key => $el) {
                        $result[$key] = self::cFilter($result[$key]);
                    }
                } else {
                    $result = self::cFilter($result);
                }
            } else {
                $result = array();
                for ($i = 1; $i < $argsCount; $i++) {
                    $argument = func_get_arg($i);
                    $result[] = self::cFilter($argument);
                }
            }
        }
        return $result;
    }

    
    
    
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - Функции обработки данных - - - - - - - - - - - - - - - - - - - - - - - - - - */
    
    /** 
     * Переиндексация ассоциативного двухмерного массива по указанному индексу в строках
     * @param array $arr Переиндексовываемый массив
     * @param string $index Новый индекс - один из индексов во всех строках массива. Сохраняется первое вхождение всех дублируемых индексов
     */
    public static function arrayReindex($arr, $index){
        $result = array();
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
     */
    public static function arrayExtract($arr, $index, $arrayReindex = false){
        $result = array();
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
     * Ограничивает строку указанной длинной 
     * @param strint $str Обрабатываемая строка
     * @param int $length Длина, до которой сокращается строка
     */
    public static function trimString($str, $length, $strEnd = '..'){
        return mb_strimwidth($str, 0, $length, $strEnd, 'UTF8');
    }


}

?>