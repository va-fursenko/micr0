<?php

/**
 * При работе в режиме отладки темплейты хранятся в файлах. Возможно расположение несольких темплейтов в одном файле
 * Фрагменты html-кода заключены в именованных блоках, выделяемых тегами [$имя блока] и [/$имя блока]
 * Стиль(если есть) указывается в квадратных скобках после объявления начала и конца блока [$имя блока][$стиль] и [/$имя блока][$стиль] 
 * Языковые константы обозначатся тегами {L_ИМЯ_КОНСТАНТЫ}
 * Прочие фрагменты текста - {имя фрагмента}
 */

/**
 * Клсасс шаблонизатора
 * 
 * @author    viktor
 * @version   2.3.3
 * @copyright viktor
 */
class Tpl {
    /**
     * Таблица БД, в которой хранятся темплейты
     * @property TEMPLATES_DB_TABLE
     */
    const TEMPLATES_DB_TABLE = '`interface_templates`';

    protected $fileName;  // Имя файла с темплейтом для работы в отладочном режиме
    protected $db;        // Класс БД с темплейтами для работы в эксплуатационном режиме
    protected $debugging; // Режим работы класса - отладка(true) или эксплуатация(false)
    protected $usingDb;   // Источник тесплейтов - БД или файл (bool)
    protected $language;  // Языковой массив для поддержки мультиязычности
    // Свойства для работы с файлами темплейтов
    protected $content;   // Последний считаный файл

// Языковые константы класса
    const L_TPL_FILE_UNREACHABLE = 'Файл шаблона недоступен';
    const L_TPL_BLOCK_UNKNOWN = 'Шаблон не найден';
    const L_TPL_DB_UNREACHABLE = 'База данных с темплейтами недоступна';

// Методы класса
    /** Создание объекта */
    function __construct($fileOrDb, $usingDb = false, $language = TPL_DEFAULT_LANGUAGE) {
        $this->setDebugging(TPL_DEBUG);
        $this->setUsingDb($usingDb);
        $this->content = '';
        if ($this->usingDb()) {
            $this->setDb($fileOrDb);
            // Проверка дескриптора на корректность
            if (!method_exists($this->getDb(), 'isConnected') || !$this->getDb()->isConnected()) {
                trigger_error(self::L_TPL_DB_UNREACHABLE, E_USER_WARNING);
            }
        } else {
            if (($fileOrDb != '') && (!is_readable($fileOrDb))) {
                trigger_error(self::L_TPL_FILE_UNREACHABLE . ' - ' . $fileOrDb, E_USER_WARNING);
            }
            $this->setFileName($fileOrDb);
            $this->loadContent($fileOrDb);
            $this->setDb(null);
        }
        if ($language !== null) {
            $this->setLanguage($language);
        } else {
            if (defined('TPL_USER_LANGUAGE')) {
                $this->setLanguage(TPL_USER_LANGUAGE);
            } else {
                $this->setLanguage(TPL_DEFAULT_LANGUAGE);
            }
        }
    }

    /** Загрузка содержимого файла */
    function loadContent($fileName = null) {
        if (!$this->usingDb()) {
            if ($fileName != '') {
                if (!is_readable($fileName)) {
                    trigger_error(self::L_TPL_FILE_UNREACHABLE . ' - ' . $fileName, E_USER_WARNING);
                }
                $this->setFileName($fileName);
            }
            $this->content = file_get_contents($this->getFileName());
            return true;
        }
        return false;
    }

    /** Получение подстроки $str, заключенной между $s_marker и $f_marker */
    private function getStrBetween($str, $sMarker, $fMarker, $initOffset = 0) {
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

    /** Получение из файла или БД заданного темплэйта */
    function getBlock($name, $style = '') {
        $result = '';
        if ($this->usingDb()) {
            $st = $style != '' ? " AND `style` = '" . Db::escapeString($style)."'" : '';
            $result = $this->getDb()->scalarQuery(
                "SELECT `body` FROM " . self::TEMPLATES_DB_TABLE . " WHERE `name` = '" . Db::escapeString($name) . "'" . $st, 
                ''
            );
            if (($result == '') && $this->getDebugging()){
                trigger_error(self::L_TPL_BLOCK_UNKNOWN . ' - ' . $name, E_USER_WARNING);
            }
        } else {
            $result = $this->getStrBetween(
                    $this->content, "[\$$name]" . ($style != '' ? "[$style]" : ''), "[/\$$name]" . ($style != '' ? "[$style]" : '')
            );
            if (($result == '') && $this->getDebugging()) {
                trigger_error(self::L_TPL_BLOCK_UNKNOWN . ' - ' . $name, E_USER_WARNING);
            }
        }
        return $result;
    }

    /** Заполнение контейнера, заданного строкой */
    private function parseStrBlock($content, $strContainer) {
        //Заменяем языковые константы
        preg_match_all('/\{L_([a-zA-Z_0-9]+)\}/', $strContainer, $arr);
        // Языковые константы
        $langs = Language::getValues($this->getDb(), TPL_DEFAULT_LANGUAGE, $arr[1]);
        foreach ($arr[1] as $name) {            
           $strContainer = str_replace('{L_' . $name . '}', $langs[$name], $strContainer);
        }
        // Прочие параметры		
        foreach ($content as $key => $value) {
            $strContainer = str_replace('{' . $key . '}', $value, $strContainer);
        }
        return $strContainer;
    }

    /** Заполнение контейнера, заданного именем секции */
    function parseBlock($content, $containerName) {
        return $this->parseStrBlock($content, $this->getBlock($containerName));
    }

    /** Обработка файла целиком */
    function parseFile($content, $fileName = null) {
        $fileName = $fileName ? $fileName : $this->fileName;
        if (!is_readable($fileName)) {
            trigger_error(self::L_TPL_FILE_UNREACHABLE . ' - ' . $fileName, E_USER_WARNING);
        }
        return $this->parseStrBlock(
                $content, 
                file_get_contents($fileName === null ? $this->getFileName() : $fileName)
        );
    }

    /** Заполнение одного выбранного блока из некэшированного файла */
    function parseBlockFromFile($content, $fileName, $blockName, $style = '') {
        $result = '';
        if (!is_readable($fileName)) {
            trigger_error(self::L_TPL_FILE_UNREACHABLE . ' - ' . $fileName, E_USER_WARNING);
        }
        $result = $this->parseStrBlock(
                $content, $this->getStrBetween(
                        file_get_contents($fileName), 
                        "[\$$blockName]" . ($style != '' ? "[$style]" : ''), 
                        "[/\$$blockName]" . ($style != '' ? "[$style]" : '')
                )
        );
        return $result;
    }

//------------------------------------------- Геттеры ----------------------------------------------------//
    /** Имя файла темплейта */
    function getFileName() {
        return $this->fileName;
    }

    /** Режим работы */
    function getDebugging() {
        return $this->debugging;
    }

    /** Режим чтения темплейтов - из БД или файла */
    function usingDb() {
        return $this->usingDb;
    }
    
    /** Язык темплейтов */
    function getLanguage(){
        return $this->language;
    }
    
    /** Дескриптор соединения с БД */
    function getDb(){
        return $this->db;
    }
    

//------------------------------------------- Сеттеры ----------------------------------------------------//
    /** Устанавливает имя файла */
    function setFileName($fileName) {
        $this->fileName = $fileName;
    }

    /** Устанавливает режим работы */
    function setDebugging($debugMode) {
        $this->debugging = $debugMode;
    }

    /** Устанавливает режим чтения темплейтов - из БД, или файла */
    function setUsingDb($usingDb) {
        return $this->usingDb = $usingDb;
    }
        
    /** Устанавливает язык темплейтов */
    function setLanguage($language) {
        return $this->language = $language;
    }
    
    /** Устанавливает дескриптор соединения с БД */
    function setDb($db){
        $this->db = $db;
    }

}

?>
