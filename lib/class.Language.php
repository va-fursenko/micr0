<?php
/**
 * Interface language class (PHP 5 >= 5.0.0)
 * Special thanks to: all, http://www.php.net
 * Copyright (c)    viktor Belgorod, 2011-2016
 * Email            vinjoy@bk.ru
 * Version            2.4.0
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the MIT License (MIT)
 * @see https://opensource.org/licenses/MIT
 */


require_once(__DIR__ . DIRECTORY_SEPARATOR . 'class.Filter.php');
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'class.BaseException.php');


/** @todo Оживить Франкенштейна */

/** Собственное исключение класса */
class LanguageException extends BaseException
{
    # Языковые константы класса
    const L_LANGUAGE_FILE_UNREACHABLE = 'Файл с языковыми данными недоступен';
}


/**
 * Класс для работы с языками интерфейса
 * @version   1.2
 * @copyright viktor
 * @package   Micr0
 */
class Language
{
    /** Текущий язык [RU, UA, EN ...] */
    protected static $language = '';

    /** Массив языковых констант для текущего языка */
    protected static $data = [];


    /**
     * Установка массива языковых констант для выбранного языка
     * @param string $varsArray Массив языковых констант
     */
    public static function setVars($varsArray)
    {
        /** @todo Сделать array_merge для обработки ситуаций, когда применяемый язык имеет не все константы базового */
        self::$data = $varsArray;
    }


    /**
     * Установка языка интерфейса
     * @param string $languageAbbr Аббривеатура устанавливаемого языка RU, EN, UA...
     */
    public static function set($languageAbbr)
    {
        self::$language = $languageAbbr;
    }


    /**
     * Получение аббривеатуры языка интерфейса
     * @return string
     */
    public static function get()
    {
        return self::$language;
    }
}
