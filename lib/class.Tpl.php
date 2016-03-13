<?php
/**
 * Templates explorer сlass (PHP 5 >= 5.0.0)
 * Special thanks to: all, http://www.php.net
 * Copyright (c)    viktor Belgorod, 2009-2016
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


/*
 * При работе в режиме отладки темплейты хранятся в файлах. Возможно расположение несольких темплейтов в одном файле
 * Фрагменты html-кода заключены в именованных блоках, выделяемых тегами [$имя блока] и [/$имя блока]
 * Языковые константы обозначатся тегами {L_ИМЯ_КОНСТАНТЫ}
 * Прочие фрагменты текста - {имя фрагмента}
 */


/** Собственное исключение класса */
class TplException extends BaseException{
    # Языковые константы класса
    const L_TPL_FILE_UNREACHABLE = 'Файл с шаблоном недоступен';
    const L_TPL_DB_UNREACHABLE   = 'База данных с темплейтами недоступна';
    const L_TPL_BLOCK_UNKNOWN    = 'Шаблон не найден';
}


/** @todo Добавить скрипт создания связанной таблицы БД */
/** @todo Закончить работу с БД */
/** @todo Добавить кеширование шаблонов - разворачивание файлов с несколькими блоками в папку с файлами блоков. Продумать развёртывание в разные папки для разных стилей и языков */

/** @todo Всё, что можно, увести в статические методы без привязки к экземпляру */
/** @todo Свойства по возможности увести в статические и сразу инициализовать свойствами CONFIG:: */


/**
 * Класс шаблонизатора
 * @author      viktor
 * @version     2.4
 * @package     Micr0
 */
class Tpl{
    # Подключаем трейты
    use UseDb; # Статическое свойство для работы с БД



    # Собственные константы
    /** @const Использование БД вместо файлов для работы с шаблонами */
    const USE_DB = CONFIG::TPL_USE_DB;
    /** @const Таблица БД с шаблонами */
    const DB_TABLE = CONFIG::TPL_DB_TABLE;
    /** @const Папка для хранения шаблонов */
    const DIR = CONFIG::ROOT . DIRECTORY_SEPARATOR . CONFIG::TPL_DIR . DIRECTORY_SEPARATOR;
    /** @const Режим дебага шаблонов */
    const DEBUG = CONFIG::TPL_DEBUG;



    # Скрытые свойства объекта
    protected $_filename  = '';      # Имя файла с темплейтом
    protected $_content   = '';        # Последний считаный файл



    /**
     * Создание объекта
     * @param string $filename Имя файла шаблона
     * @throws TplException
     */
    function __construct($filename = null) {
        if (!self::USE_DB){
            if ($filename && (!is_readable(self::DIR . $filename))) {
                throw new TplException(TplException::L_TPL_FILE_UNREACHABLE . ' - ' . $filename, E_USER_WARNING);
            }
            $this->filename($filename);
            $this->loadContent($filename);
        }
    }



    /**
     * Деструктор класса
     * @return void
     */
    public function __destruct() {
        $this->_content = null;
        $this->_filename = null;
    }



    /**
     * Загрузка содержимого файла
     * @param string $filename Имя файла для загрузки данных
     * @return bool
     * @throws TplException
     */
    public function loadContent($filename = null) {
        if ($filename != '') {
            if (!is_readable(self::DIR . $filename)) {
                throw new TplException(TplException::L_TPL_FILE_UNREACHABLE . ' - ' . $filename, E_USER_WARNING);
            }
            $this->filename($filename);
        }
        $this->_content = file_get_contents(self::DIR . $this->filename());
        return true;
    }



    /**
     * Получение из файла или БД заданного блока шаблона
     * @param string $name Имя блока шаблона
     * @return string
     * @throws TplException
     */
    public function getBlock($name) {
        if (self::USE_DB) {
            $result = self::db()->scalarQuery(
                "SELECT `body` FROM `" . self::DB_TABLE . "` WHERE `name` = '" . Filter::slashesAdd($name) . "' LIMIT 1",
                ''
            );
        }else{
            $result = Filter::strBetween($this->_content, "[\$$name]", "[/\$$name]");
        }
        if (!$result){
            throw new TplException(TplException::L_TPL_BLOCK_UNKNOWN . ': ' . $name, E_USER_WARNING);
        }
        return $result;
    }



    /**
     * Заполнение контейнера, заданного строкой
     * @param array $data Массив с полями шаблона
     * @param string $strContainer Шаблон в строке
     * @return string
     */
    private static function parseStrBlock($data, $strContainer) {
        foreach ($data as $key => $value) {
            $strContainer = str_replace('{' . $key . '}', $value, $strContainer);
        }
        return $strContainer;
    }



    /**
     * Заполнение контейнера, заданного именем секции
     * @param array $data Массив с полями шаблона
     * @param string $containerName Имя блока шаблона
     * @return string
     */
    function parseBlock($data, $containerName) {
        return self::parseStrBlock($data, $this->getBlock($containerName));
    }



    /**
     * Обработка целого файла или одного блока в нём
     * @param array $data Массив с полями шаблона
     * @param string $filename Имя файла для парсинга
     * @param string $blockName Имя блока
     * @return string
     * @throws TplException
     */
    public static function parseFile($data, $filename, $blockName = '') {
        if (!is_readable(self::DIR . $filename)) {
            throw new TplException(TplException::L_TPL_FILE_UNREACHABLE . ': ' . $filename, E_USER_WARNING);
        }
        $block = file_get_contents(self::DIR . $filename);
        return self::parseStrBlock(
            $data,
            $blockName
                ? Filter::strBetween($block, "[\$$blockName]", "[/\$$blockName]")
                : $block
        );
    }



    # ------------------------------------------- Геттеры и сеттеры ---------------------------------------------------- #
    /**
     * Возвращает, или устанавливает имя файла
     * @param string $fileName
     * @return string|true
     */
    function filename($fileName = null) {
        if (func_num_args() == 0){
            return $this->_filename;
        }else{
            $this->_filename = $fileName;
            return true;
        }
    }

    
}


