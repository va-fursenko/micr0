<?php

/* +------------------------------------------------------------------------+
 * | class.language.php                                                     |
 * | Copyright (c) Гончаров Станислав, Фурсенко Виктор 2011г.               |
 * | Version       1.1.1                                                    |
 * | Last modified 19/09/2011                                               |
 * | Email         stascer@mail.ru, vinjoy@bk.ru                            |
 * +------------------------------------------------------------------------+
 * | Модуль для поддержки мультиязычности интерфейса web-приложений         |
 * +------------------------------------------------------------------------+
 */

/**
 * Класс для работы с мультиязычностью интерфейса web-приложений.
 * Использует подключение к БД, название таблицы с языковыми данными.
 * Таблица с языковыми константами должна иметь следующую структуру:
 * -----------------------------------------------------------------------------
 * id | sysname | RU | EN | UA |.... 
 * -----------------------------------------------------------------------------
 * sysname - название константы
 * RU,.. - значение на Русском языке (например)
 * Пример запроса получения всех русскоязычных констант:
 * SELECT sysname, RU FROM tbl_language;
 * -----------------------------------------------------------------------------
 * @version   1.1.1
 * @author    Гончаров Станислав stascer@mail.ru
 * @author    Фурсенко Виктор vinjoy@bk.ru
 * @copyright stascer, Enjoy
 * @package   se-engine
 */
class Language {

    /**
     * Текущий язык [RU, UA, EN ...]
     * @access private
     * @var string
     */
    private $language = '';

    /**
     * Дискриптор соединения с БД
     * @access private
     * @var object
     */
    private $db = null;

    /**
     * Таблица в бд с описанием языковых констант
     * @access private
     * @var string
     */
    const LANGUAGE_TABLE_NAME = '`interface_languages_constants`';

    /**
     * Таблица в бд со справочником доступных языков интерфейса
     * @access private
     * @var string
     */
    const LANGUAGE_DICTIONARY = '`interface_languages`';
    
    /**
     * Конструктор класса
     * @param object $db <p> 
     * Дискриптор БД
     * </p>
     * @param string $language <p>
     * Язык
     * </p>
     * @return void
     */
    public function __construct($db, $language) {
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
     * @param string $langName <p> Обозначение проверяемого языка (RU, EN, UA..) </p>
     * @return bool
     */
    public static function hasLanguage($lang) {
        /** @todo Определить, содержит ли таблица self::LANGUAGE_TABLE_NAME столбец $lang */
    }
    
    /**
     * Получает из БД список поддерживаемых языков
     * @param object $db Дескриптор соединения с БД
     * @param bool $onlyVisible Флаг - выбирать только видимые языки или нет
     * @return array
     */
    public static function getLanguagesList($db, $onlyVisible = true){
        return $db->associateQuery("SELECT `id`, `code`, `name`, `caption`, `flag`, `visible`, `order`, `description` FROM " . self::LANGUAGE_DICTIONARY . ($onlyVisible ? " WHERE `visible` = 1 ORDER BY `order`" : ''));
    }

    /**
     * Получает значение языковых констант
     * @param array $params <p>
     * список запрашиваемых констант  array(CONST1, CONST2, ...)
     * </p> 
     * @return array
     */
    public function getLanguageValues($params) {
        $result = array();
        if (!count($params)) {
            return $result;
        }
        // Массив преобразовываем в строку для запроса к БД
        $whereArr = "'" . implode("', '", $params) . "'";
        $currLang = $this->getLanguage();
        $dataList = $this->getDb()->associateQuery(
            "SELECT `name`, `" . $currLang . "` FROM " . self::LANGUAGE_TABLE_NAME . " WHERE `name` IN (" . $whereArr . ")", 
            0
        );
        // Формируем данные в массив в виде [имя константы => значение]
        return Filter::arrayReindex($result, 'name');
    }
    
    
    /**
     * Получает значение языковых констант
     * @param array $params <p>
     * список запрашиваемых констант  array(CONST1, CONST2, ...)
     * </p> 
     * @param object $db <p>
     * Дескриптор соединения с БД
     * </p> 
     * @param string $currLang <p>
     * Текущий язык интерфейса
     * </p> 
     * @return array
     */
    public static function getValues($db, $currLang, $params) {
        $result = array();
        if (!count($params)) {
            return $result;
        }
        // Массив преобразовываем в строку для запроса к БД
        $whereArr = "'" . implode("', '", $params) . "'";
        $dataList = $db->associateQuery("SELECT `name`, `" . $currLang . "` FROM " . self::LANGUAGE_TABLE_NAME . " WHERE `name` IN (" . $whereArr . ")");
        // Формируем данные в массив в виде [имя константы => значение]
        return Filter::arrayReindex($result, 'name');
    }
    
    /**
     * Получает значение языковой константы
     * @param string $param <p>
     * Запрашиваемая константа
     * </p> 
     * @param object $db <p>
     * Дескриптор соединения с БД
     * </p> 
     * @param string $currLang <p>
     * Текущий язык интерфейса
     * </p> 
     * @return string
     */
    public static function getValue($db, $name, $currLang) {
        return $db->scalarQuery(
            "SELECT `name`, `" . $currLang . "` FROM " . self::LANGUAGE_TABLE_NAME . " WHERE `name` = '" . $name . "'", 
            ''
        );
    }

//------------------------------------------- Геттеры ----------------------------------------------------//

    /** Получает дескриптор соединения с БД */
    function getDb(){
        return $this->db;
    }
    
    /** Возвращает текущий язык */
    public function getLanguage() {
        return $this->language;
    }

}


?>
