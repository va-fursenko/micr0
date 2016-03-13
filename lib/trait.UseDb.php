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
 * Трейт для статического использования одной БД
 * @author    viktor
 * @version   1.0.0
 * @package   Micr0
 */
trait UseDb {

    /** @var Db Дескриптор соединения с БД */
    static protected $_db = null;
    /** @var string Индекс инстанса соединения с БД */
    static protected $_dbInstanceIndex = 0;



    /**
     * Геттер дескриптора соединения
     * @return Db
     */
    static public function db(){
        // Получаем дескриптор. Если он ешё не инициализован, пробуем получить его у Db::getInstance()
        if (self::$_db === null){
            self::$_db = Db::getInstance(self::$_dbInstanceIndex);
        }
        return self::$_db;
    }



    /**
     * Сеттер индекса инстанса БД
     * @param string $index
     */
    static public function setDbInstanceIndex($index){
        self::$_dbInstanceIndex = $index;
    }

} 