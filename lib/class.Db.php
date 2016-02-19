<?php
/**
 *        PDO connect сlass (PHP 5 >= 5.3.0)
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
    
    # Статические свойства
    /** Список экземпляров класса */
    protected static $_instances = array();
    /** Индекс главного экземпляра класса в списке экземпляров */
    protected static $_mainInstanceIndex = null;


    # Данные
    /** Дескриптор соединения */
    protected $_db;
    /** Имя БД */
    protected $_dbName;
    /** Адрес сервера БД */
    protected $_host;
    /** Порт сервера БД */
    protected $_port;
    /** Имя пользователя БД */
    protected $_userName;
    /** Его пароль */
    protected $_userPassword;
    /** Кодировка БД по умолчанию */
    protected $_defaultClientEncoding;
    /** Кодировка БД */
    protected $_clientEncoding;
    /** Индекс экземпляра класса */
    protected $_instanceIndex;


    # Состояние
    /** Текст последнего запроса к БД */
    protected $_lastQuery;
    /** Текст последней ошибки БД */
    protected $_lastError;
    /** Флаг соединения */
    protected $_connected;


    # Параметры
    /** Флаг логгирования */
    protected $_logging;
    /** Полный путь к файлу лога БД */
    protected $_logFile;
    /** Полный путь к файлу лога ошибок БД */
    protected $_errorLogFile;


    # Сообщения класса (языковые константы)
    /** @const Server unreachable */
    const LNG_SERVER_UNREACHABLE            = 'Сервер базы данных недоступен';
    /** @const DB unreachable */
    const LNG_DB_UNREACHABLE                = 'База данных недоступна';
    /** @const Unable to process query */
    const LNG_UNABLE_TO_PROCESS_QUERY       = 'Невозможно обработать запрос';
    /** @const Unable to process parameters */
    const LNG_UNABLE_TO_PROCESS_PARAMETERS  = 'Невозможно обработать параметры запроса';
    /** @const Error occurred */
    const LNG_ERROR_OCCURRED                = 'Произошла ошибка';



    # Методы класса
    /**
     * Определение параметров БД, определение кодировки по умолчанию
     * @param string $host Хост
     * @param string $dbName Имя БД
     * @param string $userName Пользователь
     * @param string $userPassword Пароль
     * @param int $port Порт
     * @param string $defaultEncoding Кодировка по умолчанию
     */
    public function __construct($host, $dbName, $userName, $userPassword,
                                $port = CONFIG::DB_PORT, $defaultEncoding = CONFIG::DB_ENCODING
    ) {
        $this->_db = mysqli_init();
        $this->_dbName = $dbName;
        $this->_host = $host;
        $this->_port = $port;
        $this->_userName = $userName;
        $this->_userPassword = $userPassword;
        $this->_defaultClientEncoding = $defaultEncoding;
        $this->_connected = false;
        $this->_logging = CONFIG::DB_DEBUG;
        $this->_logFile = CONFIG::DB_LOG_FILE;
        $this->_errorLogFile = CONFIG::DB_ERROR_LOG_FILE;
        // Сохраняем ссылку на объект в списке экземпляров класса, а в классе храним индекс ссылки в списке
        if (!isset(self::$_instances[$dbName])){
            self::$_instances[$dbName] = &$this;
            $this->_instanceIndex = $dbName;
        }else{
            self::$_instances[] = &$this;
            end(self::$_instances);
            $this->_instanceIndex = key(self::$_instances);
        }
    }



    /** 
     * Обработка ошибок БД в классе БД 
     * @param string $codeMessage Текствое сообщение напрямую с места возбуждения исключения
     * @throws DbException
     */
    public function throwException($codeMessage = '') {
        throw new DbException(
            (is_resource($this->getDb()) ? $this->getError() : self::LNG_SERVER_UNREACHABLE) .
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
    public function catchException(DbException $ex, $textMessage = '', $otherVars = null) {
        $messageArray = array(
            'type_name'             => 'db_exception',
            'session_id'            => session_id(),
            'text_message'          => $textMessage,
            'db_ex_message'         => $ex->getMessage(),
            'db_query_text'         => $this->getLastQuery(),
            'db_host'               => $this->getHost(),
            'db_name'               => $this->getDbName(),
            'db_user_name'          => $this->getUserName(),
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
    public function log($result = null, $action = null) {
        return Log::write(
            array(
                'type_name'             => 'db_query',
                'session_id'            => session_id(),
                'db_query_text'         => $this->getLastQuery(),
                'db_result'             => Log::printObject($result),
                'db_query_type'         => $action,
                'db_affected_rows'      => $this->affectedRows(),
                'db_user_name'          => $this->getUserName(),
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
    public function setSSLConnection($key = null, $certificate = null, $certificateAuthority = null, $pemFormatCertificate = null, $ciphers = null) {
        return mysqli_ssl_set($this->getDb(), $key, $certificate, $certificateAuthority, $pemFormatCertificate, $ciphers);
    }



    /**
     * Установка одной опции MySQL
     */
    public function setOption($optionName, $optionValue) {
        return mysqli_options($this->getDb(), $optionName, $optionValue);
    }



    /** Открытие нового соединения с указанными параметрами */
    public function connect($setDefaultEncoding = true) {
        // Если пароль не установлен, идёт второй коннект подряд
        if (!isset($this->_userPassword)){
                return $this->connected();
        }	
        try {
            if (!mysqli_real_connect($this->getDb(), $this->getHost(), $this->getUserName(), $this->getUserPassword(), $this->getDbName(), $this->getPort())) {
                $this->throwException(self::LNG_SERVER_UNREACHABLE);
            };
        } catch (DbException $ex) {
            $this->catchException($ex, self::LNG_SERVER_UNREACHABLE);
            // Ни в одном месте системы недоступность БД не является допустимой
            Ex::throwEx(Ex::E_DB_UNREACHABLE);
        }
        unset($this->_userPassword);
        if ($setDefaultEncoding) {
            $this->setClientEncoding($this->getDefaultClientEncoding());
        }
        $this->_connected = true;
        return true;
    }



    /** Смена пользователя БД */
    public function changeUser($user, $password, $database) {
        return mysqli_change_user($user, $password, $database, $this->getDb());
    }



    /** Выбор указанной БД на сервере */
    public function selectDb($dbName = null) {
        try {
            $result = mysqli_select_db($this->getDb(), $dbName ? $dbName : $this->getDbName());
            if (!$result) {
                $this->throwException(self::LNG_DB_UNREACHABLE );
            }
        } catch (DbException $ex) {
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
        return mysqli_close($this->getDb());
    }






# ------------------------------------------        Синхронные запросы        ------------------------------------------------ #

    /** Запрос без дополнительной обработки */
    public function directQuery($query, $resultMode = null){
        $this->setLastQuery($query);
        try {
            $result = mysqli_query($this->getDb(), $query, $resultMode);
            if (!$result) {
                $this->throwException(self::LNG_UNABLE_TO_PROCESS_QUERY);
            }
        } catch (DbException $ex) {
            return $this->catchException($ex, self::LNG_UNABLE_TO_PROCESS_QUERY);
        }
        return $result;
    }



    /** Базовый метод SQL-запроса */
    public function query($query, $resultMode = null){
        $result = $this->directQuery($query, $resultMode);
        if ($this->logging()) {
            $this->log($result);
        }
        return $result;
    }    



    /** Запрос с автоматическим логгированием */
    public function loggingQuery($query) {
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
        return mysqli_affected_rows($this->getDb());
    }



    /** Возвращает последний ID БД */
    public function lastInsertId(){
        return mysqli_insert_id($this->getDb());
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
        return mysqli_real_escape_string($this->getDb(), $unescapedString);
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
            $result = mysqli_real_query($this->getDb(), $query);
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
        return mysqli_field_count($this->getDb());
    }



    /** Сохранение реультата асинхронного запроса */
    public function storeResult(){
        return mysqli_store_result($this->getDb());
    }



    /** Возвращение дескриптора результата асинхронного запроса */
    public function useResult(){
        return mysqli_use_result($this->getDb());
    }



    /** Асинхронное выполнение одного или нескольких запросов */
    public function multiQuery($query){
        $this->setLastQuery(array('MULI_QUERY', $query));
        try {
            $result = mysqli_multi_query($this->getDb(), $query);
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
        return mysqli_next_result($this->getDb());
    }



    /** Проверка наличия следующего результирующего набора данных после выполнения множественного запроса */
    public function moreResults(){
        return mysqli_next_result($this->getDb());
    }






# ------------------------------------------   Работа с транзакциями   --------------------------------------------------- #

    /** Режим автоматических транзакций - включён или выключен */
    public function getAutocommitMode(){
        return $this->scalarQuery('SELECT @@autocommit');
    }



    /** Установка режима автоматических транзакций */
    public function setAutocommitMode($autocommitMode){
        return mysqli_autocommit($this->getDb(), $autocommitMode);
    }



    /** Начало транзакции */
    public function startTransaction(){
        return $this->query('START TRANSACTION');
    }



    /** Подтверждение изменений */
    public function commit(){
        return mysqli_commit($this->getDb());
    }



    /** Отмена внесенных изменений */
    public function rollback(){
        return mysqli_rollback($this->getDb());
    }






# ------------------------------------------       Информаторы       --------------------------------------------------------- #

    /** Пингует соединение с БД */
    public function ping(){
        return is_resource($this->getDb()) ? mysqli_ping($this->getDb()) : 0;
    }

    /** Информация о сервере MySQL */
    public function getServerInfo(){
        return mysqli_get_server_info($this->getDb());
    }

    /** Информация о версии сервера MySQL */
    public function getServerVersion(){
        return mysqli_get_server_version($this->getDb());
    }

    /** Информация о протоколе MySQL */
    public function getProtocolInfo(){
        return mysqli_get_proto_info($this->getDb());
    }

    /** Информация о соединении с MySQL */
    public function getHostInfo(){
        return mysqli_get_host_info($this->getDb());
    }

    /** Информация о клиенте MySQL */
    public function getClientInfo(){
        return mysqli_get_client_info($this->getDb());
    }

    /** Информация о версии клиента MySQL */
    public function getClientVersion(){
        return mysqli_get_client_version($this->getDb());
    }

    /** Возвращает кодировку соединения */
    public function getClientEncoding(){
        return mysqli_character_set_name($this->getDb());
    }

    /** Получает информацию о последнем запросе */
    public function getInfo(){
        return mysqli_info($this->getDb());
    }

    /** Получает статус сервера */
    public function getServerStatus(){
        return mysqli_stat($this->getDb());
    }

    /** Список БД, доступных на сервере */
    public function getListDbs(){
        return $this->associateQuery('SHOW DATABASES');
    }

    /** Список таблиц, доступных в БД */
    public function getListTables(){
        return $this->associateQuery('SHOW TABLES FROM `' . $this->getDbName() . '`');
    }

    /** Возвращает численный код и строку последнего сообщения об ошибке MySQL */
    public function getError(){
        $errn = mysqli_errno($this->getDb());
        return $errn . ' - ' . ($errn != 0 ? mysqli_error($this->getDb()) : 'Ok');
    }

    /** Возвращает численный код последнего сообщения об ошибке MySQL */
    public function getErrorNumber(){
        return mysqli_errno($this->getDb());
    }

    /** Возвращает строку последнего сообщения об ошибке MySQL */
    public function getErrorMessage(){
        return mysqli_error($this->getDb());
    }

    /** Возвращает численный код состояния MySQL */
    public function getSQLState(){
        return mysqli_sqlstate($this->getDb());
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






# ------------------------------------------      Геттеры      -------------------------------------------------------------- #

    /** Возвращает пароль текущего пользователя */
    private function getUserPassword(){
        return $this->_userPassword;
    }

    /** Взвращает соединение с активной БД */
    public function getDb(){
        return $this->_db;
    }

    /** Возвращает имя активной БД */
    public function getDbName(){
        return $this->_dbName;
    }

    /** Возвращает хост */
    public function getHost(){
        return $this->_host;
    }

    /** Возвращает порт */
    public function getPort(){
        return $this->_port;
    }

    /** Возвращает имя текущего пользователя */
    public function getUserName(){
        return $this->_userName;
    }

    /** Возвращает кодировку по умолчанию для БД */
    public function getDefaultClientEncoding(){
        return $this->_defaultClientEncoding;
    }

    /** Возвращает текст последнего запроса */
    public function getLastQuery(){
        return $this->_lastQuery;
    }

    /** Возвращает флаг подключения */
    public function connected(){
        return $this->_connected;
    }

    /**
     * Возвращает или устанавливает режим логгирования
     * @param bool,.. Флаг логгирования
     * @return bool Флаг логгирования, или true в случае установки этого флага
     */
    public function logging(){
        if (func_num_args() <= 0){
            return self::$_logging;
        }else{
            self::$_logging = func_get_arg(0);
            return true;
        }
    }

    /** Возвращает адрес файла лога ошибок БД */
    public function getErrorLogFile(){
        return $this->_errorLogFile;
    }

    /** Возвращает адрес файла лога запросов БД */
    public function getLogFile(){
        return $this->_logFile;
    }
    
    /** Возвращает индекс в списке экземпляров */
    public function getInstanceIndex(){
        return $this->_instanceIndex;
    }






# ------------------------------------------      Сеттеры      -------------------------------------------------------------- #

    /** Устанавливает текст последнего запроса */
    protected function setLastQuery($query){
        $this->_lastQuery = $query;
    }

    /** Устанавливает заданную кодировку в БД */
    public function setClientEncoding($encoding){
        return mysqli_set_charset($this->getDb(), $encoding);
    }
    
    /** Устанавливает индекс объекта в списке экземпляров класса */
    public function setInstanceIndex($index){
        if ($index == $this->getInstanceIndex()){
            return true;
        }
        if (self::getMainInstanceIndex() == $this->getInstanceIndex()){
            self::setMainInstanceIndex($index);
        }
        self::$_instances[$index] = &$this;
        self::clearInstance($this->getInstanceIndex());
        $this->_instanceIndex = $index;
        return true;
    }
    
    /** Устанавливает данный экземпляр класса как главный */
    public function setMainInstance(){
        self::$_mainInstanceIndex = $this->getInstanceIndex();
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

    /** Получение списка экземпляров класса или одного его элемента */
    public static function getInstance($index = null){
        return $index === null ? self::$_instances : (isset(self::$_instances[$index]) ? self::$_instances[$index] : null);
    }

    /** Возвращает главный эземпляр класса из списка классов */
    public static function getMainInstance(){
        return self::getInstance(self::getMainInstanceIndex());
    }

    /** Возвращает индекс главного эземпляра класса из списка классов */
    public static function getMainInstanceIndex(){
        return self::$_mainInstanceIndex;
    }

    /** Установка индекса главного экземпляра класса */
    public static function setMainInstanceIndex($index){
        if (!in_array($index, self::$_instances)){
            return false;
        }
        self::$_mainInstanceIndex = $index;
        return true;
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
    public static function escapeString($unescapedString, $parameterType = PDO::PARAM_STR){
        return PDO::quote($unescapedString, $parameterType);
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

}

