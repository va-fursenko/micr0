<?php
/**
 *        PDO connect сlass (PHP 5 >= 5.4.0)
 *        Special thanks to: all, http://www.php.net
 *        Copyright (c)     viktor Belgorod, 2008-2016
 *        Email             vinjoy@bk.ru
 *        version           4.0.0
 *        Last modified     23:22 17.02.16
 *        
 *        This library is free software; you can redistribute it and/or
 *        modify it under the terms of the MIT License (MIT)
 *        @see https://opensource.org/licenses/MIT
 *
 *        Не удаляйте данный комментарий, если вы хотите использовать скрипт, и всё будет хорошо :)
 *        Do not delete this comment, if you want to use the script, and everything will be okay :)
 */

/*
 * Автоматическое формирование DSN производится в методе formDSN
 * Пока поддерживается только mysql
 */



/**
 * Класс исключения для объектной работы с БД
 */
class DbException extends Exception {}





/**
 * Класс объектной работы с PDO
 * @author    viktor
 * @version   4.0.0
 * @copyright viktor
 */
class Db {

    # Константы класса
    /** @const Флаг дебага БД */
    const DEBUG = true;
    /** @const Лог БД */
    const LOG_FILE = 'db.log';
    /** @const Лог ошибок БД */
    const ERROR_LOG_FILE = 'db.error.log';

    /** @const СУБД */
    const DMS = 'mysql';
    /** @const Хост БД */
    const HOST = 'localhost';
    /** @const Порт БД */
    const PORT = 3306;
    /** @const Имя БД */
    const DBNAME = 'report';
    /** @const Пользователь БД */
    const USER = 'root';
    /** @const Пароль БД @deprecated Подразумевается, что не используется в продакшне */
    const PASSWORD = '';
    /** @const Кодировка БД */
    const CHARSET = 'utf8';

    
    # Статические свойства
    /** Список экземпляров класса */
    protected static $_instances = [];
    /** Индекс главного экземпляра класса в списке экземпляров */
    protected static $_mainInstanceIndex = null;

    # Открытые данные
    /** Дескриптор PDO */
    public $db                 = null;

    # Закрытые данные
    /** СУБД */
    protected $_dms            = self::DMS;
    /** Хост сервера */
    protected $_host           = self::HOST;
    /** Порт сервера */
    protected $_port           = self::PORT;
    /** Имя БД */
    protected $_dbName         = self::DBNAME;
    /** Имя пользователя */
    protected $_userName       = self::USER;
    /** Пароль пользователя */
    protected $_userPass       = self::PASSWORD;
    /** Кодировка БД */
    protected $_charset        = self::CHARSET;
    /** Строка подключения */
    protected $_dsn            = self::DMS . ':' . 'host=' . self::HOST . ';port=' . self::PORT . ';dbname=' . self::NAME . ';charset=' . self::CHARSET;
    /** Индекс экземпляра класса */
    protected $_instanceIndex  = null;


    # Состояние объекта
    /** Текст последнего запроса к БД */
    protected $_lastQuery = '';
    /** Текст последней ошибки БД */
    protected $_lastError = '';
    /** Флаг соединения */
    protected $_connected = false;


    # Параметры
    /** Флаг логгирования */
    protected $_logging      = false;
    /** Полный путь к файлу лога БД */
    protected $_logFile      = self::LOG_FILE;
    /** Полный путь к файлу лога ошибок БД */
    protected $_errorLogFile = self::ERROR_LOG_FILE;


    # Сообщения класса (языковые константы)
    /** @const Server unreachable */
    const LNG_SERVER_UNREACHABLE            = 'Сервер базы данных недоступен';
    /** @const DB unreachable */
    const LNG_DB_UNREACHABLE                = 'База данных недоступна';
    /** @const Unable to process query */
    const LNG_UNABLE_TO_PROCESS_QUERY       = 'Невозможно обработать запрос';
    /** @const Unable to process parameters */
    const LNG_UNABLE_TO_PROCESS_PARAMETERS  = 'Невозможно обработать параметры запроса';
    /** @const Wrong parameters */
    const LNG_WRONG_PARAMETERS              = 'Неверные параметры';
    /** @const Error occurred */
    const LNG_ERROR_OCCURRED                = 'Произошла ошибка';






    # Методы класса
    /**
     * Определение параметров БД, определение кодировки по умолчанию
     * @param string $dsn       СУБД или строка подключения
     * @param string $userName  Пользователь
     * @param string $userPass  Пароль
     * @param string $host      Хост
     * @param int    $port      Порт
     * @param string $dbName    Имя БД
     * @param string $charset  Кодировка БД
     */
    public function __construct($dsn, $userName = null, $userPass = null, $dbName = null, $host = null, $port = null, $charset = null){
        $this->db            = null;
        $this->_connected    = false;
        $this->_logging      = self::DEBUG;
        $this->_logFile      = self::LOG_FILE;
        $this->_errorLogFile = self::ERROR_LOG_FILE;

        $numArgs = func_num_args();
        $args = func_get_args();

        // Если первый параметр - длинная строка, значит это DSN
        if ($numArgs > 0 && is_string($dsn) && count($dsn) > 6){ // Надо бы прикинуть минимальную длину
            $this->dsn($dsn);
            /** @todo получить из DSN разрешённые данные и распихать по свойствам. Или продумать, как получать их потом из дескриптора */

        // Если параметров не передали, готовим соединение по умолчанию
        }else if ($numArgs == 0) {
            $this->dms(self::DMS);
            $this->host(self::HOST);
            $this->dbName(self::DBNAME);
            $this->port(self::PORT);
            $this->userName(self::USER);
            $this->userPass = self::PASSWORD;
            $this->_charset = self::CHARSET;
            // Формируем DSN
            $this->_dsn = $this->formDSN();

        // Если параметры переданы по отдельности
        }else if ($numArgs > 0 && $numArgs < 9){ // Надо бы прикинуть максимальную длину алиаса СУБД
            // Проверяем и собираем параметры БД
            $this->_dms =  is_string($dsn) && count($dsn) < 6 ? $dsn : $this->throwException(self::LNG_WRONG_PARAMETERS);
            $options = $numArgs = 8 ? (is_array($args[7]) ? $args[7] : $this->throwException(self::LNG_WRONG_PARAMETERS)) : [];
            $this->_userName = $numArgs > 1 && $userName !== null ? $userName : self::USER;
            $this->_userPass = $numArgs > 2 && $userPass !== null ? (is_string($userPass) ? $userPass : $this->throwException(self::LNG_WRONG_PARAMETERS)) : self::PASSWORD;
            $this->_dbName = $numArgs > 3 && $dbName !== null ? (is_string($dbName) && $dbName !== '' ? $dbName : $this->throwException(self::LNG_WRONG_PARAMETERS)) : self::DBNAME;
            $this->_host = $numArgs > 4 && $host !== null ? (is_string($host) && $host !== '' ? $host : $this->throwException(self::LNG_WRONG_PARAMETERS)) : self::HOST;
            $this->_port = $numArgs > 5 && $port !== null ? (is_numeric($port) && $port > 0 && $port < 65535 ? $port : $this->throwException(self::LNG_WRONG_PARAMETERS)) : self::PORT;
            $this->_charset = $numArgs > 3 && $charset !== null ? (is_string($charset) && $charset !== '' ? $charset : $this->throwException(self::LNG_WRONG_PARAMETERS)) : self::CHARSET;


        }


        // Сохраняем ссылку на объект в списке экземпляров класса, а в классе храним индекс ссылки в списке
        // @todo Индексы реализованы на имени БД. Не уверен, что это стоящая идея
        if (!isset(self::$_instances[$dbName])){
            self::$_instances[$dbName] = &$this;
            $this->_instanceIndex = $dbName;
        }else{
            self::$_instances[] = &$this;
            end(self::$_instances);
            $this->_instanceIndex = key(self::$_instances);
        }
    }



    /** Открытие нового соединения с указанными параметрами */
    public function connect($setDefaultEncoding = true){


        //$this->_db = new PDO( string $dsn [, string $username [, string $password [, array $options ]]] );


        // Если пароль не установлен, идёт второй коннект подряд
        if (!isset($this->_userPass)){
            return $this->connected();
        }
        try {
            if (!mysqli_real_connect($this->db, $this->getHost(), $this->userName(), $this->getUserPassword(), $this->getDbName(), $this->getPort())){
                $this->throwException(self::LNG_SERVER_UNREACHABLE);
            };
        } catch (DbException $ex){
            $this->catchException($ex, self::LNG_SERVER_UNREACHABLE);
            // Ни в одном месте системы недоступность БД не является допустимой
            Ex::throwEx(Ex::E_DB_UNREACHABLE);
        }
        unset($this->_userPass);
        if ($setDefaultEncoding){
            $this->setEncoding($this);
        }
        $this->_connected = true;
        return true;
    }



    /** 
     * Обработка ошибок БД в классе БД 
     * @param string $codeMessage Текствое сообщение напрямую с места возбуждения исключения
     * @throws DbException
     * @return bool
     */
    public function throwException($codeMessage = ''){
        throw new DbException(
            (is_resource($this->db) ? $this->getError() : self::LNG_SERVER_UNREACHABLE) .
            ($codeMessage ? ' - ' . $codeMessage : '')
        );
    }



    /** 
     * Функция перехвата внутреннего исключения, логгирования записи о ней и вывода сообщения пользователю 
     * @param DbException $ex Объект исключения
     * @param string $textMessage Текствое сообщение напрямую с места перехвата исключения
     * @param array $otherVars Дополнительные переменные, которые пишутся в ошибку
     * @return bool
     */
    public function catchException(DbException $ex, $textMessage = '', $otherVars = null){
        $messageArray = array(
            'type_name'             => 'db_exception',
            'session_id'            => session_id(),
            'text_message'          => $textMessage,
            'db_ex_message'         => $ex->getMessage(),
            'db_query_text'         => $this->getLastQuery(),
            'db_host'               => $this->getHost(),
            'db_name'               => $this->getDbName(),
            'db_user_name'          => $this->userName(),
            'db_ping'               => $this->ping(),
            'db_status'             => $this->getServerStatus(),
            'db_last_error'         => $this->getError(),
            'db_connect_error'      => $this->getConnectError(),
            'php_file_name'         => $ex->getFile(),
            'php_file_line'         => $ex->getLine(),
            'php_trace'             => serialize($ex->getTrace()),
            'php_error_code'        => $ex->getCode(),
            'http_request_method'   => $_SERVER['REQUEST_METHOD'],
            'http_server_name'      => $_SERVER['SERVER_NAME'],
            'http_request_uri'      => $_SERVER['REQUEST_URI'],
            'http_user_agent'       => $_SERVER['HTTP_USER_AGENT'],
            'http_remote_addr'      => $_SERVER['REMOTE_ADDR']
        );
        if (is_array($otherVars) && count($otherVars) > 0){
            $messageArray += $otherVars;
        }
        return Log::write(
            $messageArray,
            $this->getErrorLogFile()
        );
    }



    /**
     * Логгирование результата и текста запроса
     * @param mixed,.. $result Результат запроса
     * @param string,.. $action Строковый алиас действия
     * @return bool
     */
    public function log($result = null, $action = null){
        return Log::write(
            array(
                'type_name'             => 'db_query',
                'session_id'            => session_id(),
                'db_query_text'         => $this->getLastQuery(),
                'db_result'             => Log::printObject($result),
                'db_query_type'         => $action,
                'db_affected_rows'      => $this->affectedRows(),
                'db_user_name'          => $this->userName(),
                'http_request_method'   => $_SERVER['REQUEST_METHOD'],
                'http_server_name'      => $_SERVER['SERVER_NAME'],
                'http_request_uri'      => $_SERVER['REQUEST_URI'],
                'http_user_agent'       => $_SERVER['HTTP_USER_AGENT'],
                'http_remote_addr'      => $_SERVER['REMOTE_ADDR']
            ),
            $this->getLogFile()
        );
    }



    /**
     * Установка SSL-соединения
     * @deprecated
     */
    public function setSSLConnection($key = null, $certificate = null, $certificateAuthority = null, $pemFormatCertificate = null, $ciphers = null){
        return mysqli_ssl_set($this->db, $key, $certificate, $certificateAuthority, $pemFormatCertificate, $ciphers);
    }



    /**
     * Установка одной опции MySQL
     */
    public function setOption($optionName, $optionValue){
        return mysqli_options($this->db, $optionName, $optionValue);
    }



    /** Смена пользователя БД */
    public function changeUser($user, $password, $database){
        return mysqli_change_user($user, $password, $database, $this->db);
    }



    /** Выбор указанной БД на сервере */
    public function selectDb($dbName = null){
        try {
            $result = mysqli_select_db($this->db, $dbName ? $dbName : $this->getDbName());
            if (!$result){
                $this->throwException(self::LNG_DB_UNREACHABLE );
            }
        } catch (DbException $ex){
            return $this->catchException($ex, self::LNG_DB_UNREACHABLE);
        }
        return $result;
    }

    /** Закрытие коннекта */
    public function close(){
        $this->_connected = false;
        $this->setLastQuery('close_connection');
        if ($this->getInstanceIndex() == self::getMainInstanceIndex()){
            self::setMainInstanceIndex(null);
        }
        self::clearInstance($this->getInstanceIndex());
        return mysqli_close($this->db);
    }






# ------------------------------------------        Синхронные запросы        ------------------------------------------------ #

    /** Запрос без дополнительной обработки */
    public function directQuery($query, $resultMode = null){
        $this->setLastQuery($query);
        try {
            $result = mysqli_query($this->db, $query, $resultMode);
            if (!$result){
                $this->throwException(self::LNG_UNABLE_TO_PROCESS_QUERY);
            }
        } catch (DbException $ex){
            return $this->catchException($ex, self::LNG_UNABLE_TO_PROCESS_QUERY);
        }
        return $result;
    }



    /** Базовый метод SQL-запроса */
    public function query($query, $resultMode = null){
        $result = $this->directQuery($query, $resultMode);
        if ($this->logging()){
            $this->log($result);
        }
        return $result;
    }    



    /** Запрос с автоматическим логгированием */
    public function loggingQuery($query){
        $result = $this->query($query);
        // Если отладка включена, то запрос уже логгирован и второй раз его не пишем
        if (!$this->logging()){
            $this->log($result, self::_getIntQueryType($query));
        }
        return $result;
    }



    /** SQL запрос к БД для получения одной скалярной величины */
    public function scalarQuery($query, $defaultValue = false){
        $result = $this->query($query, MYSQLI_STORE_RESULT);
        if ($result && (mysqli_num_rows($result))){
            return reset(mysqli_fetch_row($result));
        }
        return $defaultValue;
    }



    /** SQL запрос к БД для получения результата в виде одномерного или двухмерного ассоциативного массива */
    public function associateQuery($query, $row = null, $resultType = MYSQLI_ASSOC){
        $queryResult = $this->query($query);
        if ($row === null){
            return $this->fetchResult($queryResult, $resultType);
        }else{
            return $this->fetchRow($queryResult, $row, $resultType);
        }
    }



    /** Возвращает число затронутых прошлой операцией строк */
    public function affectedRows(){
        return mysqli_affected_rows($this->db);
    }



    /** Возвращает последний ID БД */
    public function lastInsertId(){
        return mysqli_insert_id($this->db);
    }



    /**
     * Запрос(на изменение БД), составляемый из входных параметров - действия, таблицы и массива параметров
     * ВНИМАНИЕ! Автоматического экранирования данных нет. Контролируйте все параметры процедуры!
     * Все параметры кроме null оборачиваются одинарными кавычками.
     * @param string $action Тип запроса
     * @param string $sourceName Название таблицы или хранимой процедуры
     * @param array $params Столбцы выборки, записи
     * @param mixed $target,.. Параметры выборки или действия
     * @param bool $log,.. Флаг - логгировать запрос автоматически или нет
     * @return mixed
     * @throws DbException
     */
    public function arrayQuery($action, $sourceName, $params, $target = null, $log = true){
        $action = strtoupper($action);
        try {
            switch ($action){
                // Команда вставки данных из массива
                case 'INSERT':
                    $paramsCount = count($params);
                    if (!$paramsCount){
                        $this->throwException(self::LNG_UNABLE_TO_PROCESS_PARAMETERS);
                    }
                    // Если массив данных двухмерный
                    if (is_array(reset($params))){
                        $qParams = array(0 => '`' . implode('`, `', array_keys(current($params))) . '`', '');
                        $data = array();
                        foreach ($params as $rowArr){
                            $row = array();
                            foreach ($rowArr as $el){
                                $row[] = $el === null ? 'null' : "'$el'";
                            }
                            $data[] = '(' . implode(', ', $row) . ')';
                        }
                        $qParams[1] = implode(', ', $data);
                    } else {
                        $t = each($params);
                        $qParams = array('`' . $t[0] . '`', $t[1] === null ? 'null' : "'{$t[1]}'");
                        while ($t = each($params)){
                            $qParams[0] .= ', `' . $t[0] . '`';
                            $qParams[1] .= ', ' . ($t[1] === null ? 'null' : "'{$t[1]}'");
                        }
                        $qParams[1] = '(' . $qParams[1] . ')';
                    }
                    $line = "INSERT INTO $sourceName ({$qParams[0]}) VALUES {$qParams[1]}";
                    break;
                // Команда обновления данных из массива        
                case 'UPDATE':
                    $paramsCount = count($params);
                    if (!($paramsCount)){
                        $this->throwException(self::LNG_UNABLE_TO_PROCESS_PARAMETERS);
                    }
                    $t = each($params);
                    $qParams = '`' . $t[0] . '` = ' . ($t[1] === null ? 'null' : "'{$t[1]}'");
                    while ($t = each($params)){
                        $qParams .= ', `' . $t[0] . '` = ' . ($t[1] === null ? 'null' : "'{$t[1]}'");
                    }
                    $line = "UPDATE $sourceName SET $qParams WHERE `id` = $target LIMIT 1";
                    break;
                // Команда выборки данны из таблицы
                case 'SELECT':
                    $line = "SELECT `" . implode('`, `', $params) . "` FROM $sourceName";
                    if ($target !== null){
                        $line .= " WHERE `id` = $target";
                    }
                    break;
                // Команда запуска хранимой процедуры
                case 'CALL':
                    $line = "CALL $sourceName(" . implode(', ', $params) . ')';
                    break;                
                default:
                    $this->throwException(self::LNG_UNABLE_TO_PROCESS_PARAMETERS);
                    break;
            }
            $result = $log ? $this->loggingQuery($line, $action) : $this->directQuery($line);
            if (self::_getIntQueryType($action) >= 4){
                $result = $this->fetchResult($result);
            }
            return $result;
        } catch (DbException $ex){
            return $this->catchException($ex, self::LNG_UNABLE_TO_PROCESS_PARAMETERS);
        }
    }



    /** 
     * Метод, аналогичный методу arrayQuery(), но с автоматическим экранированием параметров
     * @see self::arrayQuery()
     * @param string $action Тип запроса
     * @param string $sourceName Название таблицы или хранимой процедуры
     * @param array $params Столбцы выборки, записи
     * @param mixed $target,.. Параметры выборки или действия
     * @param bool $log,.. Флаг - логгировать запрос автоматически или нет
     * @return mixed
     */
    public function arraySquery($action, $sourceName, $params, $target = null, $log = false){
        // Пока не реализована обработка сложных условий, а только сравнивание с id, оставим проверку такой
        try {
            if (($target !== null) && !is_numeric($target)){
                $this->throwException(self::LNG_UNABLE_TO_PROCESS_PARAMETERS);
            }
        } catch (DbException $ex){
            return $this->catchException($ex, self::LNG_UNABLE_TO_PROCESS_PARAMETERS);
        }
        /** @todo Реализовать правильную проверку target в зависимости от типа запроса и прочих входных данных */
        $action = self::escapeString($action);
        $sourceName = self::escapeString($sourceName);
        $sequredParams = array();
        foreach ($params as $key => $value){
            if ($value){
                $sequredParams[self::escapeString($key)] = self::escapeString($value);
            } else {
                $sequredParams[self::escapeString($key)] = $value;
            }
        }
        return $this->arrayQuery($action, $sourceName, $sequredParams, $target, $log);
    }






# ----------------------------------------        Методы обработки результатов запросов        ------------------------------- #

    /** Возвращает количество строк результата запроса */
    public function numRows($result){
        return mysqli_num_rows($result);
    }



    /** Возвращает количество полей результата запроса */
    public function numFields($result){
        return mysqli_num_fields($result);
    }



    /** Возвращает один ряд из результата запроса в виде массива с указанной индексацией 
     *  $resultType может быть MYSQLI_ASSOC, MYSQLI_NUM, MYSQLI_BOTH */
    public function fetchRow($queryResult, $row = null, $resultType = MYSQLI_ASSOC){
        $result = null;
        $numRows = mysqli_num_rows($queryResult);
        if ($queryResult && $numRows){
            if ($row){
                if ($row < $numRows){                    
                    mysqli_data_seek($queryResult, $row);
                }else{
                    return $result;
                }
            }
            $result = mysqli_fetch_array($queryResult, $resultType);
        }
        return $result;
    }



    /** Возвращает результат запроса в виде двухмерного массива с указанной индексацией строк
     *  $resultType может быть MYSQLI_ASSOC, MYSQLI_NUM, MYSQLI_BOTH */
    public function fetchResult($queryResult, $resultType = MYSQLI_ASSOC){
        $result = array();
        $numRows = mysqli_num_rows($queryResult);
        if ($queryResult && $numRows){
            for ($i = 0; $i < $numRows; $i++){
                $result[$i] = mysqli_fetch_array($queryResult, $resultType);
            }
        }
        return $result;
    }



    /** Возвращает один ряд из результата запроса в виде объекта */
    public function fetchObject($result){
        return mysqli_fetch_object($result);
    }



    /** Экранирует специальные символы в строке, принимая во внимание кодировку соединения */
    public function realEscapeString($unescapedString){
        return mysqli_real_escape_string($this->db, $unescapedString);
    }



    /** Возвращает информацию о поле с номером $offset в результате $result в виде объекта 
     *  Если $offset не указан, то возвращается информация о текущем поле и смещается указатель на него */
    public function fetchField($result, $offset = null){
        if ($offset !== null){
            return mysqli_fetch_field_direct($result, $offset);
        } else {
            return mysqli_fetch_field($result);
        }
    }



    /** Освобождает память от результата запроса */
    public function freeResult($result){
        return mysqli_free_result($result);
    }



    /** Возвращает текущее смещение в результате запроса */
    public function fieldTell($result){
        return mysqli_field_tell($result);
    }






# ---------------------------------------        Асинхронные запросы        ------------------------------------------------- #

    /** SQL-запрос без авто-обработки результата и её буфферизации */
    public function unbufferedQuery($query){
        $this->setLastQuery(array('UNBUFFERED_QUERY', $query));
        try {
            $result = mysqli_real_query($this->db, $query);
            if (!$result){
                $this->throwException(self::LNG_UNABLE_TO_PROCESS_QUERY);
            }
        } catch (DbException $ex){
            return $this->catchException($ex, self::LNG_UNABLE_TO_PROCESS_QUERY);
        }
        if ($this->logging()){
            $this->log($result);
        }
        return $result;
    }



    /** Определение длины результата асинхронного запроса */
    public function fieldCount(){
        return mysqli_field_count($this->db);
    }



    /** Сохранение реультата асинхронного запроса */
    public function storeResult(){
        return mysqli_store_result($this->db);
    }



    /** Возвращение дескриптора результата асинхронного запроса */
    public function useResult(){
        return mysqli_use_result($this->db);
    }



    /** Асинхронное выполнение одного или нескольких запросов */
    public function multiQuery($query){
        $this->setLastQuery(array('MULI_QUERY', $query));
        try {
            $result = mysqli_multi_query($this->db, $query);
            if (!$result){
                $this->throwException(self::LNG_UNABLE_TO_PROCESS_QUERY);
            }
        } catch (DbException $ex){
            return $this->catchException($ex, self::LNG_UNABLE_TO_PROCESS_QUERY);
        }
        if ($this->logging()){
            $this->log($result);
        }
        return $result;
    }



    /** Подготовка к получению следующего результирующего набора данных после выполнения множественного запроса */
    public function nextResult(){
        return mysqli_next_result($this->db);
    }



    /** Проверка наличия следующего результирующего набора данных после выполнения множественного запроса */
    public function moreResults(){
        return mysqli_next_result($this->db);
    }






# ------------------------------------------   Работа с транзакциями   --------------------------------------------------- #

    /** Режим автоматических транзакций - включён или выключен */
    public function getAutocommitMode(){
        return $this->scalarQuery('SELECT @@autocommit');
    }



    /** Установка режима автоматических транзакций */
    public function setAutocommitMode($autocommitMode){
        return mysqli_autocommit($this->db, $autocommitMode);
    }



    /** Начало транзакции */
    public function startTransaction(){
        return $this->query('START TRANSACTION');
    }



    /** Подтверждение изменений */
    public function commit(){
        return mysqli_commit($this->db);
    }



    /** Отмена внесенных изменений */
    public function rollback(){
        return mysqli_rollback($this->db);
    }






# ------------------------------------------       Информаторы       --------------------------------------------------------- #

    /** Пингует соединение с БД */
    public function ping(){
        return is_resource($this->db) ? mysqli_ping($this->db) : 0;
    }

    /** Информация о сервере MySQL */
    public function getServerInfo(){
        return mysqli_get_server_info($this->db);
    }

    /** Информация о версии сервера MySQL */
    public function getServerVersion(){
        return mysqli_get_server_version($this->db);
    }

    /** Информация о протоколе MySQL */
    public function getProtocolInfo(){
        return mysqli_get_proto_info($this->db);
    }

    /** Информация о соединении с MySQL */
    public function getHostInfo(){
        return mysqli_get_host_info($this->db);
    }

    /** Информация о клиенте MySQL */
    public function getClientInfo(){
        return mysqli_get_client_info($this->db);
    }

    /** Информация о версии клиента MySQL */
    public function getClientVersion(){
        return mysqli_get_client_version($this->db);
    }

    /** Возвращает кодировку соединения */
    public function getCharset(){
        return mysqli_character_set_name($this->db);
    }

    /** Получает информацию о последнем запросе */
    public function getInfo(){
        return mysqli_info($this->db);
    }

    /** Получает статус сервера */
    public function getServerStatus(){
        return mysqli_stat($this->db);
    }

    /** Список БД, доступных на сервере */
    public function getListDbs(){
        return $this->associateQuery('SHOW DATABASES');
    }

    /** Список таблиц, доступных в БД */
    public function getListTables(){
        return $this->associateQuery('SHOW TABLES FROM `' . $this->dbName() . '`');
    }

    /** Возвращает численный код и строку последнего сообщения об ошибке MySQL */
    public function getError(){
        $errn = mysqli_errno($this->db);
        return $errn . ' - ' . ($errn != 0 ? mysqli_error($this->db) : 'Ok');
    }

    /** Возвращает численный код последнего сообщения об ошибке MySQL */
    public function getErrorNumber(){
        return mysqli_errno($this->db);
    }

    /** Возвращает строку последнего сообщения об ошибке MySQL */
    public function getErrorMessage(){
        return mysqli_error($this->db);
    }

    /** Возвращает численный код состояния MySQL */
    public function getSQLState(){
        return mysqli_sqlstate($this->db);
    }

    /** Возвращает численный код ошибки соединения с БД */
    public function getConnectErrorNumber(){
        return mysqli_connect_errno();
    }

    /** Возвращает строку сообщения ошибки соединения с БД */
    public function getConnectErrorMessage(){
        return mysqli_connect_error();
    }

    /** Возвращает численный код и строку сообщения ошибки соединения с БД */
    public function getConnectError(){
        $errn = mysqli_connect_errno();
        return $errn . ' - ' . ($errn != 0 ? mysqli_connect_error() : 'Ok');
    }






# ------------------------------------------      Геттеры и сеттеры     ---------------------------------------------------- #

    /** Возвращает или устанавливает DSN */
    public function dsn(){
        if (func_num_args() == 0) {
            return $this->_dsn;
        }else {
            $dsn = func_get_arg(0);          # Да, мягко говоря, условная проверка строки на валидность
            $this->_dsn = is_string($dsn) && count($dsn) > 6 ? $dsn : $this->throwException(self::LNG_WRONG_PARAMETERS);
            return true;
        }
    }

    /** Возвращает или устанавливает алиас СУБД */
    public function dms(){
        if (func_num_args() == 0) {
            return $this->_dms;
        }else {
            $dms = func_get_arg(0);          # Да, мягко говоря, условная проверка строки на валидность
            $this->_dms = is_string($dms) && count($dms) < 7 ? $dms : $this->throwException(self::LNG_WRONG_PARAMETERS);
            return true;
        }
    }

    /** Возвращает или устанавливает имя активной БД */
    public function dbName(){
        if (func_num_args() == 0) {
            return $this->_dbName;
        }else {
            $dbName = func_get_arg(0);
            $this->_dbName = is_string($dbName) && $dbName !== '' ? $dbName : $this->throwException(self::LNG_WRONG_PARAMETERS);
            return true;
        }
    }

    /** Возвращает или устанавливает хост */
    public function host(){
        if (func_num_args() == 0) {
            return $this->_host;
        }else {
            $host = func_get_arg(0);
            $this->_host = is_string($host) && $host !== '' ? $host : $this->throwException(self::LNG_WRONG_PARAMETERS);
            return true;
        }
    }

    /** Возвращает или устанавливает порт */
    public function port(){
        if (func_num_args() == 0) {
            return $this->_port;
        }else {
            $port = func_get_arg(0);
            $this->_port = is_numeric($port) && $port > 0 && $port < 65535 ? $port : $this->throwException(self::LNG_WRONG_PARAMETERS);
            return true;
        }
    }

    /** Возвращает или устанавливает имя текущего пользователя */
    public function userName(){
        if (func_num_args() == 0) {
            return $this->_userName;
        }else {
            $userName = func_get_arg(0);
            $this->_userName = is_string($userName) && $userName !== '' ? $userName : $this->throwException(self::LNG_WRONG_PARAMETERS);
            return true;
        }
    }

    /**
     * Возвращает или устанавливает кодировку БД
     * @param string,.. Кодировка БД
     * @return string Кодировка БД, или bool результат установки кодировки БД
     */
    public function charset(){
        if (func_num_args() <= 0){
            return $this->_charset;
        }else{
            return mysqli_set_charset($this->db, func_get_arg(0));
        }
    }

    /**
     * Возвращает или устанавливает режим логгирования
     * @param bool,.. Флаг логгирования
     * @return bool Флаг логгирования, или true в случае установки этого флага
     */
    public function logging(){
        if (func_num_args() <= 0){
            return $this->_logging;
        }else{
            $this->_logging = func_get_arg(0);
            return true;
        }
    }

    /** Возвращает или устанавливает текст последнего запроса */
    public function getLastQuery(){
        if (func_num_args() <= 0){
            return $this->_lastQuery;
        }else {
            $this->_lastQuery = func_get_arg(0);
            return true;
        }
    }

    /** Возвращает флаг подключения */
    public function connected(){
        return $this->_connected;
    }





    /** Возвращает адрес файла лога ошибок БД */
    public function getErrorLogFile(){
        return $this->_errorLogFile;
    }

    /** Возвращает адрес файла лога запросов БД */
    public function getLogFile(){
        return $this->_logFile;
    }






    /** Устанавливает индекс объекта в списке экземпляров класса */
    public function instanceIndex(){
        if (func_num_args() == 0){
            return $this->_instanceIndex;
        }else {
            $index = func_get_arg(0);
            if ($index == $this->_instanceIndex) {
                return true;
            }
            if ($index == self::mainInstanceIndex()) {
                self::mainInstanceIndex($index);
            }
            self::$_instances[$index] = &$this;
            self::clearInstance($this->_instanceIndex);
            $this->_instanceIndex = $index;
            return true;
        }
    }
    
    /** Устанавливает данный экземпляр класса как главный */
    public function setMainInstance(){
        self::$_mainInstanceIndex = $this->instanceIndex();
        return true;
    }






# ------------------------------------------       Статические методы класса       ------------------------------------------- #
    
    /** 
     * Возвращает один экземпляр класса из списка классов - аналог метода getInstance()
     * @param string $instanceIndex,.. Индекс экземпляр класса в списке классов
     * @return mixed Инстанс с указанным индексом
     */
    public static function db($instanceIndex = null){
        return $instanceIndex === null ? self::getMainInstance() : self::getInstance($instanceIndex);
    }

    /**
     * Получение списка экземпляров класса или одного его элемента
     * @param string $index,.. Индекс инстанса
     * @return mixed
     */
    public static function getInstance($index = null){
        return $index === null ? self::$_instances : (isset(self::$_instances[$index]) ? self::$_instances[$index] : null);
    }

    /** Возвращает главный эземпляр класса из списка классов */
    public static function getMainInstance(){
        return self::getInstance(self::mainInstanceIndex());
    }

    /** Установка или получение индекса главного экземпляра класса */
    public static function mainInstanceIndex(){
        if (func_num_args() == 0){
            return self::$_mainInstanceIndex;
        }else {
            $index = func_get_arg(0);
            if (!in_array($index, self::$_instances)) {
                return false;
            }
            self::$_mainInstanceIndex = $index;
            return true;
        }
    }



    /**
     * Экранирует специальные символы в строке, не принимая во внимание кодировку соединения
     * Не все PDO драйверы реализуют этот метод (особенно PDO_ODBC).
     * Предполагается, что вместо него будут использоваться подготавливаемые запросы.
     * http://php.net/manual/ru/pdo.quote.php
     * @deprecated
     * @param string $unescapedString Входная строка
     * @param int $parameterType,.. Представляет подсказку о типе данных первого параметра для драйверов, которые имеют альтернативные способы экранирования
     * @return string Возвращает экранированную строку, или false, если драйвер СУБД не поддерживает экранирование
     */
    public function escapeString($unescapedString, $parameterType = PDO::PARAM_STR){
        return $this->_db->quote($unescapedString, $parameterType);
    }






# ------------------------------------------   Скрытые статические методы класса   ------------------------------------------- #

    /**
     * Определение в условном числе типа запроса по переданному слову или первому из запроса
     * @param string $text Первое слово запрса
     * @param bool $firstCall Флаг того, что фунцкия вызвана не рекурсивно
     * @return int
     */
    static protected function _getIntQueryType($text, $firstCall = true){
        switch ($text){
            case 'INSERT': return 1;
            case 'UPDATE': return 2;
            case 'DELETE': return 3;
            case 'CALL'  : return 4;
            case 'SELECT': return 5;
            default: 
                // Пытаемся определить тип запроса по первому слову текста запроса, если уже не делаем это
                return $firstCall ? self::_getIntQueryType(strtoupper(strtok($text, ' ')), false) : false;
        }
    }



    /**
     * Формирование одной строки запроса вставки. В данном методе фильтрация не производится
     * @param array $data Ассоциативный массив параметров вставки
     * @return string
     */
    protected function _formInsertQuery($data){
        $t = each($data);
        $result = is_numeric($t[1]) || $t[1] == 'null' ? $t[1] : "'{$t[1]}'";
        while ($t = each($data)){
            $result .= ', ' . (is_numeric($t[1]) || $t[1] == 'null' ? $t[1] : "'{$t[1]}'");
        }
        return $result;
    }



    /**
     * Формирование строки DSN
     * @return string
     * @throws DbException
     */
    protected function formDSN(){
        switch ($this->_dms){
            case 'mysql':
                $result = 'mysql:' .
                    ($this->_host ? 'host=' . $this->_host : '') .
                    ($this->_port ? ';port=' . $this->_port : '') .
                    ($this->_dbName ? ';dbname=' . $this->_dbName : '') .
                    ($this->_charset ? ';charset=' . $this->_charset : '');
                return $result;

            default:
                $this->throwException(self::LNG_WRONG_PARAMETERS);
        }
    }

}

