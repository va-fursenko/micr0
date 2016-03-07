<?php
/**
 * Interface language class (PHP 5 >= 5.0.0)
 * Special thanks to: all, http://www.php.net
 * Copyright (c)    viktor Belgorod, 2011-2016
 * Email		    vinjoy@bk.ru
 * Version		    2.4.0
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the MIT License (MIT)
 * @see https://opensource.org/licenses/MIT
 */


require_once(__DIR__ . DIRECTORY_SEPARATOR . 'class.Db.php');
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'class.Filter.php');
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'class.BaseException.php');


/** @todo Оживить Франкенштейна */
/** @todo Добавить скрипт создания связанной таблицы БД */


/**
 * Класс для работы с мультиязычностью интерфейса web-приложений.
 * Использует подключение к БД, название таблицы с языковыми данными.
 * Таблица с языковыми константами должна иметь следующую структуру:
 * -----------------------------------------------------------------------------
 * id | sysname | RU | EN | UA |....
 * -----------------------------------------------------------------------------
 * sysname - название константы
 * RU, EN,... - значение на Русском языке (например)
 * Пример запроса получения всех русскоязычных констант:
 * SELECT sysname, RU FROM tbl_language;
 * -----------------------------------------------------------------------------
 * @version   1.2
 * @copyright viktor
 * @package   Micr0
 */
class Language {


    /** Текущий язык [RU, UA, EN ...] */
    protected $language = '';
    /** Дискриптор соединения с БД */
    protected $db = null;


    /** @const Таблица в бд с описанием языковых констант */
    const LANGUAGE_DB_TABLE = CONFIG::TPL_LANGUAGE_DB_TABLE;
    /** @const Таблица в бд со справочником доступных языков интерфейса */
    const LANGUAGES_DB_DICTIONARY = CONFIG::TPL_LANGUAGES_DB_DICTIONARY;


    /**
     * Конструктор класса
     * @param object $db Дискриптор БД
     * @param string $language Язык
     */
    public function __construct($db, $language){
        $this->db = $db;
        $this->language = $language;
    }

    /**
     * Деструктор класса
     * @return void
     */
    public function __destruct() {
        $this->db = null;
        $this->language = null;
    }

    /**
     * Определяет, поддерживает ли БД указаный язык. Проверяется наличие столбца с таким именем в таблице констант
     * @param string $lang Обозначение проверяемого языка (RU, EN, UA..)
     * @return bool
     */
    public function hasLanguage($lang) {
        /** @todo Определить, содержит ли таблица self::LANGUAGE_TABLE_NAME столбец $lang */
    }

    /**
     * Получает из БД список поддерживаемых языков
     * @param bool $visibleOnly Флаг - выбирать только видимые языки или нет
     * @return array
     */
    public function getLanguagesList($visibleOnly = true){
        return $this->db()->associateQuery("SELECT `id`, `code`, `name`, `caption`, `flag`, `visible`, `order`, `description` FROM " . self::LANGUAGES_DB_DICTIONARY . ($visibleOnly ? " WHERE `visible` = 1 ORDER BY `order`" : ''));
    }

    /**
     * Получает значение языковых констант
     * @param array $params Список запрашиваемых констант  array(CONST1, CONST2, ...)
     * @return array
     */
    public function getLanguageValues($params) {
        $result = [];
        if (!count($params)) {
            return $result;
        }
        // Массив преобразовываем в строку для запроса к БД
        $whereArr = "'" . implode("', '", $params) . "'";
        $currLang = $this->getLanguage();
        $result = $this->db()->associateQuery(
            "SELECT `name`, `" . $currLang . "` FROM " . self::LANGUAGE_DB_TABLE . " WHERE `name` IN (" . $whereArr . ")",
            0
        );
        // Формируем данные в массив в виде [имя константы => значение]
        return Filter::arrayReindex($result, 'name');
    }


    /**
     * Получает значение языковых констант
     * @param string $currLang Текущий язык интерфейса
     * @param array $params Список запрашиваемых констант  array(CONST1, CONST2, ...)
     * @return array
     */
    public function getValues($currLang, $params) {
        $result = [];
        if (!count($params)) {
            return $result;
        }
        // Массив преобразовываем в строку для запроса к БД
        $whereArr = "'" . implode("', '", $params) . "'";
        $result = $this->db()->associateQuery("SELECT `name`, `" . $currLang . "` FROM " . self::LANGUAGE_DB_TABLE . " WHERE `name` IN (" . $whereArr . ")");
        // Формируем данные в массив в виде [имя константы => значение]
        return Filter::arrayReindex($result, 'name');
    }

    /**
     * Получает значение языковой константы
     * @param string $name Имя константы
     * @param string $currLang Текущий язык интерфейса
     * @return string
     */
    public function getValue($name, $currLang){
        return $this->db()->scalarQuery(
            "SELECT `name`, `" . $currLang . "` FROM " . self::LANGUAGE_DB_TABLE . " WHERE `name` = '" . $name . "'",
            ''
        );
    }

//------------------------------------------- Геттеры ----------------------------------------------------//

    /** Получает дескриптор соединения с БД */
    function db(){
        return $this->db;
    }

    /** Возвращает текущий язык */
    public function getLanguage() {
        return $this->language;
    }

}
