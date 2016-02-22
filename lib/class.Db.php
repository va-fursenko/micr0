<?php
/**
 * PDO connect сlass (PHP 5 >= 5.4.0)
 * Special thanks to: all, http://www.php.net
 * Copyright (c)    viktor Belgorod, 2008-2016
 * Email            vinjoy@bk.ru
 * Version          4.0.0
 * Last modified    23:22 17.02.16
 *        
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the MIT License (MIT)
 * @see https://opensource.org/licenses/MIT
 *
 * Не удаляйте данный комментарий, если вы хотите использовать скрипт, и всё будет хорошо :)
 * Do not delete this comment, if you want to use the script, and everything will be okay :)
 */





/**
 * Класс исключения для объектной работы с БД
 */
class DbException extends Exception {

    /**
     * Строковое представление объекта
     * @return string
     */
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}";
    }
}





/**
 * Класс объектной работы с PDO
 * @author    viktor
 * @version   4.0.0
 * @copyright viktor
 *
 * @see http://php.net/manual/ru/book.pdo.php
 * @see https://habrahabr.ru/post/137664/
 * @see http://php.net/manual/ru/pdo.constants.php  - Предопределённые константы PDO для $options
 */
class Db {
    # Статические свойства
    /** Список экземпляров класса */
    protected static $_instances = [];
    /** Индекс главного экземпляра класса в списке экземпляров */
    protected static $_mainInstanceIndex = null;


    # Открытые данные
    /** Дескриптор PDO */
    public $db = null;
    /** Дескриптор результирующего набора данны */
    public $stmt = null;


    # Закрытые данные
    /** Индекс экземпляра класса */
    protected $_instanceIndex  = null;
    # Состояние объекта
    /** Текст последнего запроса к БД */
    protected $_lastQuery = '';
    /** Текст последней ошибки БД */
    protected $_lastError = '';


    # Параметры
    /** Флаг логгирования */
    protected $_logging      = false;


    # Сообщения класса
    /** @const Server unreachable */
    const E_SERVER_UNREACHABLE            = 'Сервер базы данных недоступен';
    /** @const DB unreachable */
    const E_DB_UNREACHABLE                = 'База данных недоступна';
    /** @const Unable to process query */
    const E_UNABLE_TO_PROCESS_QUERY       = 'Невозможно обработать запрос';
    /** @const Wrong parameters */
    const E_WRONG_PARAMETERS              = 'Неверные параметры';
    /** @const Error occurred */
    const E_ERROR_OCCURRED                = 'Произошла ошибка';






    # Методы класса
    /**
     * Определение параметров БД, определение кодировки по умолчанию
     * @param string $dsn       СУБД или строка подключения
     * @param string $userName  Пользователь
     * @param string $userPass  Пароль
     * @param array  $options   Массив опций подключения
     * @return Db
     * @throws PDOException
     */
    public function __construct($dsn, $userName = '', $userPass = '', $options = []){
        $this->db            = null;
        $this->_logging      = CONFIG::DB_DEBUG;
        $this->_logFile      = CONFIG::DB_LOG_FILE;
        $this->_errorLogFile = CONFIG::DB_ERROR_LOG_FILE;
        $this->db = new PDO($dsn, $userName, $userPass, $options);
        $this->instanceIndex(count(self::$_instances));
        if ($this->logging()){
            $this->log('db_connected');
        }
    }






    /** 
     * Вызов исключения в классе БД
     * @param string $codeMessage Текствое сообщение напрямую с места возбуждения исключения
     * @throws DbException
     * @return bool Ничего не возвращает, но так IDE не канючит по поводу использования этого метода в выражениях
     */
    public function throwException($codeMessage = ''){
        throw new DbException(
            (is_resource($this->db) ? $this->getError() : self::E_SERVER_UNREACHABLE) .
            ($codeMessage ? ' - ' . $codeMessage : '')
        );
    }



    /** 
     * Логгирование внутреннего исключения
     * @param DbException $ex Объект исключения
     * @param string $textMessage Текствое сообщение напрямую с места перехвата исключения
     * @return bool
     */
    public function logException(DbException $ex, $textMessage = ''){
        $messageArray = array(
            'type_name'             => 'db_exception',
            'session_id'            => session_id(),
            'db_ex_message'         => $ex->__toString(),
            'db_last_query'         => $this->getLastQuery(),
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
        if (is_string($textMessage) && $textMessage !== ''){
            $messageArray['text_message'] = $textMessage;
        }
        return Log::write(
            $messageArray,
            CONFIG::DB_ERROR_LOG_FILE
        );
    }



    /**
     * Логгирование результата и текста запроса
     * @param string,.. $action Строковый алиас действия
     * @param mixed,.. $result Результат запроса
     * @return bool
     */
    public function log($action = '', $result = null){
        $arr = [
            'type_name'             => 'db_query',
            'session_id'            => session_id(),
            'db_last_query'         => $this->getLastQuery(),
            'db_affected_rows'      => $this->affectedRows(),
            'http_request_method'   => $_SERVER['REQUEST_METHOD'],
            'http_server_name'      => $_SERVER['SERVER_NAME'],
            'http_request_uri'      => $_SERVER['REQUEST_URI'],
            'http_user_agent'       => $_SERVER['HTTP_USER_AGENT'],
            'http_remote_addr'      => $_SERVER['REMOTE_ADDR']
        ];
        if ($action) {
            $arr['db_query_type'] = is_string($action) ? $action : '';
            $arr['db_result'] = Log::printObject($result);
        }
        return Log::write(
            $arr,
            CONFIG::DB_LOG_FILE
        );
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
                $this->throwException(self::E_DB_UNREACHABLE);
            }
        } catch (DbException $ex){
            return $this->logException($ex, self::E_DB_UNREACHABLE);
        }
        return $result;
    }

    /** Закрытие коннекта */
    public function close(){
        $this->_lastQuery = 'close_connection';
        self::clearInstance($this->instanceIndex());
        if ($this->logging()){
            $this->log('db_closed');
        }
        return mysqli_close($this->db);
    }



    /** Устанавливает данный экземпляр класса как главный */
    public function instanceSetMain(){
        self::$_mainInstanceIndex = $this->instanceIndex();
        return true;
    }





# ------------------------------------------        Синхронные запросы        ------------------------------------------------ #

    /** Запрос без дополнительной обработки */
    public function directQuery($query, $resultMode = null){
        $this->setLastQuery($query);
        try {
            $result = mysqli_query($this->db, $query, $resultMode);
            if (!$result){
                $this->throwException(self::E_UNABLE_TO_PROCESS_QUERY);
            }
        } catch (DbException $ex){
            return $this->logException($ex, self::E_UNABLE_TO_PROCESS_QUERY);
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
                        $this->throwException(self::E_WRONG_PARAMETERS);
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
                        $this->throwException(self::E_WRONG_PARAMETERS);
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
                    $this->throwException(self::E_WRONG_PARAMETERS);
                    break;
            }
            $result = $log ? $this->loggingQuery($line, $action) : $this->directQuery($line);
            if (self::_getIntQueryType($action) >= 4){
                $result = $this->fetchResult($result);
            }
            return $result;
        } catch (DbException $ex){
            return $this->logException($ex, self::E_WRONG_PARAMETERS);
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
                $this->throwException(self::E_WRONG_PARAMETERS);
            }
        } catch (DbException $ex){
            return $this->logException($ex, self::E_WRONG_PARAMETERS);
        }
        /** @todo Реализовать правильную проверку target в зависимости от типа запроса и прочих входных данных */
        $action = $this->realEscapeString($action);
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



    /**
     * Возвращает число затронутых строк для указанного набора данных
     * @param PDOStatement $stmt
     * @return int
     */
    public function affectedRows($stmt = null){
        return func_num_args() == 1
            ? $stmt ? $stmt->rowCount() : false
            : $this->stmt !== null ? $this->stmt->rowCount() : false;
    }



    /**
     * Возвращает последний ID, добавленный в БД
     * @return string
     */
    public function lastInsertId(){
        return $this->db !== null ? $this->db->lastInsertId() : false;
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



    /**
     * Экранирует специальные символы в строке, не принимая во внимание кодировку соединения
     * Не все PDO драйверы реализуют этот метод (особенно PDO_ODBC).
     * Предполагается, что вместо него будут использоваться подготавливаемые запросы.
     * http://php.net/manual/ru/pdo.quote.php
     * @param string $unescapedString Входная строка
     * @param int $parameterType,.. Представляет подсказку о типе данных первого параметра для драйверов, которые имеют альтернативные способы экранирования
     * @return string Возвращает экранированную строку, или false, если драйвер СУБД не поддерживает экранирование
     */
    public function escapeString($unescapedString, $parameterType = PDO::PARAM_STR){
        return $this->db->quote($unescapedString, $parameterType);
    }






# ---------------------------------------        Асинхронные запросы        ------------------------------------------------- #

    /** SQL-запрос без авто-обработки результата и её буфферизации */
    public function unbufferedQuery($query){
        $this->setLastQuery(array('UNBUFFERED_QUERY', $query));
        try {
            $result = mysqli_real_query($this->db, $query);
            if (!$result){
                $this->throwException(self::E_UNABLE_TO_PROCESS_QUERY);
            }
        } catch (DbException $ex){
            return $this->logException($ex, self::E_UNABLE_TO_PROCESS_QUERY);
        }
        if ($this->logging()){
            $this->log($query);
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
        $this->setLastQuery('MULTI_QUERY ' . $query);
        try {
            $result = mysqli_multi_query($this->db, $query);
            if (!$result){
                $this->throwException(self::E_UNABLE_TO_PROCESS_QUERY);
            }
        } catch (DbException $ex){
            return $this->logException($ex, self::E_UNABLE_TO_PROCESS_QUERY);
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

    /**
     * Режим автоматических транзакций - включён или выключен
     * @return mixed
     */
    public function getAutocommitMode(){
        return $this->getAttribute(PDO::ATTR_AUTOCOMMIT);
    }

    /**
     * Установка режима автоматических транзакций
     * @param int $autocommitMode
     * @return bool
     */
    public function setAutocommitMode($autocommitMode){
        return $this->setAttribute(PDO::ATTR_AUTOCOMMIT, $autocommitMode);
    }

    /** Начало транзакции */
    public function beginTransaction(){
        return $this->db->beginTransaction();
    }

    /** Подтверждение изменений */
    public function commit(){
        return $this->db->commit();
    }

    /** Отмена внесенных изменений */
    public function rollBack(){
        return $this->db->rollBack();
    }

    /** Проверка на наличие открытой транзакии */
    public function inTransaction(){
        return $this->db->inTransaction();
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







# ------------------------------------------------      Сеттеры     ---------------------------------------------------------- #

    /**
     * Установка одного атрибута PDO
     * @param int   $attrName   Имя атрибута
     * @param mixed $attrValue  Значение атрибута
     * @return bool
     * @see http://php.net/manual/ru/pdo.setattribute.php
     * @throws PDOException
     */
    public function setAttribute($attrName, $attrValue){
        return $this->db->setAttribute($attrName, $attrValue);
    }




# ---------------------------------------------------       Геттеры       ---------------------------------------------------- #

    /** Возвращает текст последнего запроса */
    public function getLastQuery(){
        return $this->_lastQuery;
    }

    /**
     * Установка одного атрибута PDO
     * @param int   $attrName   Имя атрибута
     * @return mixed
     * @see http://php.net/manual/ru/pdo.getattribute.php
     * @throws PDOException
     */
    public function getAttribute($attrName){
        return $this->db->getAttribute($attrName);
    }




# ------------------------------------------       Геттеры и Сеттеры - 2 в 1      -------------------------------------------- #

    /**
     * Устанавливает, или получает индекс объекта в списке экземпляров класса
     * @param string,.. $index Индекс инстанса
     * @return string Запрашиваемый индекс, или true в случае успешной установки этого индекса
     */
    public function instanceIndex($index = null){
        if (func_num_args() == 0){
            return $this->_instanceIndex;
        }else {
            self::$_instances[$index] = &$this;
            unset(self::$_instances[$this->_instanceIndex]);
            $this->_instanceIndex = $index;
            return true;
        }
    }

    /**
     * Возвращает или устанавливает режим логгирования
     * @param bool,.. $logging Флаг логгирования
     * @return bool Флаг логгирования, или true в случае установки этого флага
     */
    public function logging($logging = null){
        if (func_num_args() <= 0){
            return $this->_logging;
        }else{
            $this->_logging = boolval($logging);
            return true;
        }
    }







# ------------------------------       Статические методы класса (работа с инстансами)      ---------------------------------- #

    /** @todo Перевести инстансы в отдельный трейт */

    /** 
     * Возвращает один экземпляр класса из списка классов - аналог метода getInstance()
     * @param string $instanceIndex,.. Индекс экземпляр класса в списке классов
     * @return mixed Инстанс с указанным индексом
     */
    public static function instance($instanceIndex = null){
        return $instanceIndex === null ? self::getMainInstance() : self::getInstance($instanceIndex);
    }

    /**
     * Получение списка экземпляров класса или одного его элемента
     * @param string $index,.. Индекс инстанса
     * @return mixed Инстанс указанной БД, или весь массив
     */
    public static function getInstance($index = null){
        return $index === null ? self::$_instances : (isset(self::$_instances[$index]) ? self::$_instances[$index] : null);
    }

    /**
     * Возвращает главный эземпляр класса из списка классов
     * @return mixed Главный инстанс класса
     */
    public static function getMainInstance(){
        return self::getInstance(self::mainInstanceIndex());
    }

    /**
     * Установка или получение индекса главного экземпляра класса
     * @param string $index Индекс инстанса
     * @return string Индекс главного инстанса класса, или true в случае успешной установки
     * @throws DbException
     */
    public static function mainInstanceIndex($index = null){
        if ($index === null){
            return self::$_mainInstanceIndex;
        }else {
            if (!(is_string($index) || is_numeric($index)) || !in_array($index, self::$_instances)) {
                self::throwException(self::E_WRONG_PARAMETERS);
            }
            self::$_mainInstanceIndex = $index;
            return true;
        }
    }

    /**
     * Очищение инстанса
     * @param string $index Индекс инстанса
     * @return true
     */
    public static function clearInstance($index){
        if ($index == self::mainInstanceIndex()){
            self::mainInstanceIndex(null);
        }
        unset(self::$_instances[$index]);
        return true;
    }

    /**
     * Получение списка доступных драйверов для различных СУБД
     * @return array
     */
    public static function getAvailableDrivers(){
        return PDO::getAvailableDrivers();
    }






# -----------------------------------------------   Скрытые методы класса   -------------------------------------------------- #

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

