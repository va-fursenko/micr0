<?php
/**
 * Use Db trait (PHP 5 >= 5.4.0)
 * Special thanks to: all, http://www.php.net
 * Copyright (c)    viktor Belgorod, 2016
 * Email            vinjoy@bk.ru
 * Version          1.0.0
 * Last modified    00:01 13.03.16
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the MIT License (MIT)
 * @see https://opensource.org/licenses/MIT
 */

require_once (__DIR__ . DIRECTORY_SEPARATOR . 'class.Db.php');

/**
 * Трейт для статического использования объекта Db
 * @author    viktor
 * @version   1.0.0
 * @package   Micr0
 */
trait UseDb
{

    /** @var Db Дескриптор соединения с БД */
    static protected $db = null;
    /** @var string Индекс инстанса соединения с БД */
    static protected $dbInstanceIndex = 0;



    /**
     * Геттер дескриптора соединения
     * @return Db
     */
    public static function db()
    {
        // Получаем дескриптор. Если он ешё не инициализован, пробуем получить его у Db::getInstance()
        if (self::$db === null) {
            self::$db = Db::getInstance(self::$dbInstanceIndex);
        }
        return self::$db;
    }



    /**
     * Сеттер индекса инстанса БД
     * @param string $index
     */
    public static function setDbInstanceIndex($index)
    {
        self::$dbInstanceIndex = $index;
    }
} 
