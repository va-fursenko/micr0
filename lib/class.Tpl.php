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
/** @todo Вынести замену языковых констант в отдельный метод, чтобы заменять их в уже готовом к выводу тексте страницы */
/** @todo Всё, что можно, увести в статические методы без привязки к экземпляру */
/** @todo Свойства по возможности увести в статические и сразу инициализовать свойствами CONFIG:: */


/**
 * Класс шаблонизатора
 * @author      viktor
 * @version     2.4
 * @package     Micr0
 */
class Tpl{

    # Скрытые свойства класса
    protected $_fileName  = '';      # Имя файла с темплейтом для работы в отладочном режиме
    protected $_db        = null;    # Объект Db БД с темплейтами для работы в эксплуатационном режиме
    protected $_db_table  = '';      # Имя таблицы БД с шаблонами
    protected $_debugMode = false;   # Режим работы класса - отладка(true) или эксплуатация(false)
    protected $_useDb     = false;   # Источник тесплейтов - БД или файл (bool)
    protected $_language  = '';      # Алиас языка для работы с мультиязычностью


    # Свойства для работы с файлами темплейтов
    protected $_content = '';        # Последний считаный файл



    /**
     * Создание объекта
     * @param string|Db $target Полное имя файла, или дескриптор БД
     * @param bool $useDb Флаг использования БД для чтения шаблонов
     * @param string $language Язык системы
     * @throws TplException
     */
    function __construct($target, $useDb = CONFIG::TPL_USE_DB, $language = CONFIG::TPL_DEFAULT_LANGUAGE) {
        $this->debug(CONFIG::TPL_DEBUG);
        $this->useDb($useDb);
        $this->_content = '';
        if ($this->useDb()){
            // Проверка дескриптора на корректность
            if (!($target instanceof Db)){
                throw new TplException(TplException::L_TPL_DB_UNREACHABLE, E_USER_WARNING);
            }
            $this->_db = $target;
            $this->_db_table = CONFIG::TPL_DB_TABLE;
        }else{
            if (($target != '') && (!is_readable($target))) {
                throw new TplException(TplException::L_TPL_FILE_UNREACHABLE . ' - ' . $target, E_USER_WARNING);
            }
            $this->fileName($target);
            $this->loadContent($target);
            $this->_db = null;
        }
        $this->language($language);
    }



    /**
     * Деструктор класса
     * @return void
     */
    public function __destruct() {
        $this->_db = null;
        $this->_language = null;
        $this->_content = null;
        $this->_fileName = null;
        $this->_language = null;
    }



    /**
     * Загрузка содержимого файла
     * @param string $fileName Имя файла для загрузки данных
     * @return bool
     * @throws TplException
     */
    public function loadContent($fileName = null) {
        if ($fileName != '') {
            if (!is_readable($fileName)) {
                throw new TplException(TplException::L_TPL_FILE_UNREACHABLE . ' - ' . $fileName, E_USER_WARNING);
            }
            $this->fileName($fileName);
        }
        $this->_content = file_get_contents($this->fileName());
        return true;
    }



    /**
     * Получение из файла или БД заданного блока шаблона
     * @param string $name Имя блока шаблона
     * @return string
     * @throws TplException
     */
    public function getBlock($name) {
        if ($this->useDb()) {
            $result = $this->db()->scalarQuery(
                "SELECT `body` FROM `" . $this->_db_table . "` WHERE `name` = '" . Filter::slashesAdd($name) . "' LIMIT 1",
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
     * @param array $content Массив с полями шаблона
     * @param string $strContainer Шаблон в строке
     * @return string
     */
    private static function parseStrBlock($content, $strContainer) {
/*
        //Заменяем языковые константы
        preg_match_all('/\({L_[a-zA-Z_0-9]+\})/', $strContainer, $lang);
        // Получаем массив использованных в данном шаблоне языковых констант
        $lang = Language::getList($this->db(), TPL_DEFAULT_LANGUAGE, $lang[1]);
        // Проходим в цикле и меняем все
        foreach ($lang as $key => $value) {
           $strContainer = str_replace($key, $value, $strContainer);
        }
*/
        // Прочие параметры		
        foreach ($content as $key => $value) {
            $strContainer = str_replace('{' . $key . '}', $value, $strContainer);
        }
        return $strContainer;
    }



    /**
     * Заполнение контейнера, заданного именем секции
     * @param array $content Массив с полями шаблона
     * @param string $containerName Имя блока шаблона
     * @return string
     */
    function parseBlock($content, $containerName) {
        return self::parseStrBlock($content, $this->getBlock($containerName));
    }



    /**
     * Обработка целого файла, как одного блока шаблона
     * @param array $content Массив с полями шаблона
     * @param string $fileName Имя файла для парсинга
     * @return string
     * @throws TplException
     */
    public static function parseFile($content, $fileName) {
        if (!is_readable($fileName)) {
            throw new TplException(TplException::L_TPL_FILE_UNREACHABLE . ': ' . $fileName, E_USER_WARNING);
        }
        return self::parseStrBlock(
                $content, 
                file_get_contents($fileName)
        );
    }



    /**
     * Заполнение одного выбранного блока из некэшированного файла
     * @param array $content Массив с полями шаблона
     * @param string $fileName Имя файла
     * @param string $blockName Имя блока
     * @param string $style Стиль блока
     * @return string
     * @throws TplException
     */
    function parseBlockFromFile($content, $fileName, $blockName, $style = '') {
        if (!is_readable($fileName)) {
            throw new TplException(TplException::L_TPL_FILE_UNREACHABLE . ': ' . $fileName, E_USER_WARNING);
        }
        $result = self::parseStrBlock(
                $content,
                Filter::strBetween(
                    file_get_contents($fileName),
                    "[\$$blockName]" . ($style != '' ? "[$style]" : ''),
                    "[/\$$blockName]" . ($style != '' ? "[$style]" : '')
                )
        );
        return $result;
    }






    # ------------------------------------------- Геттеры и сеттеры ---------------------------------------------------- #
    /**
     * Возвращает, или устанавливает имя файла
     * @param string $fileName
     * @return string|true
     */
    function fileName($fileName = null) {
        if (func_num_args() == 0){
            return $this->_fileName;
        }else{
            $this->_fileName = $fileName;
            return true;
        }
    }

    /**
     * Возвращает, или устанавливает режим дебага
     * @param bool $debugMode
     * @return bool
     */
    function debug($debugMode = null) {
        if (func_num_args() == 0){
            return $this->_debugMode;
        }else{
            $this->_debugMode = $debugMode;
            return true;
        }
    }

    /**
     * Возвращает, или устанавливает режим чтения темплейтов - из БД, или файла
     * @param bool $useDb
     * @return bool
     */
    function useDb($useDb = null) {
        if (func_num_args() == 0){
            return $this->_useDb;
        }else{
            $this->_useDb = $useDb;
            return true;
        }
    }
        
    /**
     * Возвращает, или устанавливает язык темплейтов
     * @param string $language
     * @return string|true
     */
    function language($language = null) {
        if (func_num_args() == 0){
            return $this->_language;
        }else{
            $this->_language = $language;
            return true;
        }
    }
    
    /**
     * Возвращает дескриптор соединения с БД
     * @return Db
     */
    function db(){
        return $this->_db;
    }

}


