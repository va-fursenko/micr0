<?php
/**
 * Templates to PHP translator сlass (PHP 5 >= 5.6.0)
 * Special thanks to: all, http://www.php.net
 * Copyright (c)    viktor Belgorod, 2016-2016
 * Email		    vinjoy@bk.ru
 * Version		    1.0.0
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the MIT License (MIT)
 * @see https://opensource.org/licenses/MIT
 */


require_once(__DIR__ . DIRECTORY_SEPARATOR . 'class.BaseException.php');




/** Собственное исключение класса */
class ViewTranslatorException extends BaseException
{
    # Языковые константы класса
    const L_TPL_FILE_UNREACHABLE = 'Файл с шаблоном недоступен';
}


/** @todo Трансляция шаблонов в PHP-код */




/**
 * Класс транслятора шаблонов в PHP-код
 * @author      viktor
 * @version     1.0
 * @package     Micr0
 */
class ViewTranslator
{

    /**
     * Замена в тексте шаблона $tplString строковых и числовых переменных PHP-кодом вставки данных из массива $dataItems
     * @param string $tplString Шаблон в строке
     * @param array  $dataItems Ассоциативный массив с контекстом шаблона
     * @return string
     */
    protected static function translateStrings($tplString, $dataItems)
    {
        /**
         * str_replace('{{ имя_переменной }}', "?> echo self::getVar('имя_переменной'); <?php", $tplString)
         * Вообще в классе имя_переменной ожидается из символов \w - буквы, цифры, подчёркивание,
         * но в данном методе для скорости используется str_replace, которая может заменить всё, что угодно
         */
        foreach ($dataItems as $varName => $value) {
            if (is_string($value) || is_numeric($value)) {
                $tplString = str_replace('{{ ' . $varName . ' }}', "?> echo self::getVar('$varName'); <?php", $tplString);
            }
        }
        return $tplString;
    }
} 