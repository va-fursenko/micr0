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

require_once(__DIR__ . DIRECTORY_SEPARATOR . "trait.instances.php");




/**
 * Класс исключения для объектной работы с БД
 * @see http://php.net/manual/ru/class.pdoexception.php PDOException
 */
class DbException extends BaseException{

    /** @property Db Дескриптор соединения, если передана в конструктор */
    public $db            = null;
    /** @property string Информация об ошибке */
    public $errorInfo     = '';
    /** @property PDOStatement Последнее подготовленное выражение, если передано в конструктор */
    public $lastStatement = null;
    /** @property string Текст последнего запроса */
    public $lastQuery     = '';
    /** @property int Число строк, затронутых последним запросом */
    public $rowCount      = '';



    /**
     * Конструктор класса
     * @param string $message Текстовое сообщение об ошибке
     * @param string|PDOStatement|Db $obj Подготовленное выражение, которое, вероятно, вызвало исключение, объект БД, или просто код ошибки
     * @param Exception $prev Предыдущее исключение
     */
    public function __construct($message, $obj = null, Exception $prev = null){
        $numArgs = func_num_args();
        if ($numArgs == 1){
            parent::__construct($message);

        }else if ($numArgs > 1){
            if ($obj instanceof PDOStatement){
                $this->lastStatement = $obj; 
                $this->errorInfo = Db::formatLastErrorMessage($this->lastStatement->errorInfo());
                $this->lastQuery = $this->lastStatement->queryString;
                $this->rowCount = $this->lastStatement->rowCount();
                parent::__construct($message, $this->lastStatement->errorCode(), $prev);

            }else if ($obj instanceof Db){
                $this->db = $obj;
                $this->errorInfo = $this->db->getLastError();
                $this->lastQuery = $this->db->getLastQuery();
                parent::__construct($message, $this->db->getErrorCode(), $prev);

            }else{
                parent::__construct($message, Log::showObject($obj), $prev);
            }
        }
    }



//  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
//  -       ПРИ ИСКЛЮЧЕНИИ ВО ВРЕМЯ КОННЕКТА УШАТАЕТ В ЛОГ ЛОГИН И ПАРОЛЬ!      -
//  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    /**
     * Логгирование внутреннего исключения
     * @param string|array $action Текствое сообщение напрямую с места перехвата исключения
     * @return bool
     */
    public function toLog($action = ''){
        $mArr = array(
            Log::A_TYPE_NAME             => Log::T_DB_EXCEPTION,
            Log::A_SESSION_ID            => session_id(),
            Log::A_DB_EXCEPTION_MESSAGE  => $this->toString(),
            Log::A_DB_ROWS_AFFECTED      => $this->rowCount,
            Log::A_EXCEPTION_MESSAGE     => $this->errorInfo,
            Log::A_DB_LAST_QUERY         => $this->lastQuery,
            Log::A_PHP_FILE_NAME         => $this->getFile(),
            Log::A_PHP_FILE_LINE         => $this->getLine(),
            Log::A_PHP_TRACE             => serialize($this->getTrace()),
            Log::A_PHP_ERROR_CODE        => $this->getCode(),
            Log::A_HTTP_REQUEST_METHOD   => $_SERVER['REQUEST_METHOD'],
            Log::A_HTTP_SERVER_NAME      => $_SERVER['SERVER_NAME'],
            Log::A_HTTP_REQUEST_URI      => $_SERVER['REQUEST_URI'],
            Log::A_HTTP_USER_AGENT       => $_SERVER['HTTP_USER_AGENT'],
            Log::A_HTTP_REMOTE_ADDRESS   => $_SERVER['REMOTE_ADDR'],
        );

        // Из БД или подготовленного выражения тянем все интересные данные
        if ($this->db instanceof Db){
            $mArr[Log::A_DB_LAST_ERROR]  = $this->db->getLastError();
            $mArr[Log::A_DB_SERVER_INFO] = $this->db->getServerInfo();

        }else if ($this->lastStatement instanceof PDOStatement){
            $mArr[Log::A_DB_LAST_ERROR]  = Db::formatLastErrorMessage($this->lastStatement->errorInfo());
            $mArr[Log::A_DB_ROWS_AFFECTED] = $this->lastStatement->rowCount();
            $mArr[Log::A_DB_LAST_QUERY] = [
                'Ex'    => $mArr[Log::A_DB_LAST_QUERY],
                'debug' => Db::debugDumpParams($this->lastStatement),
            ];
        }

        // Строковый параметр пишем, как сообщение, массив добавляем
        if (is_string($action) && $action !== ''){
            $mArr[Log::A_TEXT_MESSAGE] = $action;
        }else if (is_array($action) && $action !== []){
            $mArr = $mArr + $action;
        }

        return Log::save(
            $mArr,
            CONFIG::DB_ERROR_LOG_FILE
        );
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
 * @see http://php.net/manual/ru/pdo.constants.php Предопределённые константы PDO для $options
 * @see http://phpfaq.ru/pdo
 * @see http://phpfaq.ru/SafeMysql https://github.com/colshrapnel/safemysql/blob/master/safemysql.class.php Безопасный класс mysql
 * @see http://ruseller.com/lessons.php?id=610&rub=28 Примеры fetch
 * @see https://github.com/f3ath/LazyPDO/
 */
class Db {
    # Подключаем трейты
    use instances; # Работа с инстансами

    # Открытые данные
    /** Дескриптор PDO */
    public $db = null;


    # Закрытые данные
    /** Текст последнего запроса к БД */
    protected $_lastQuery = '';
    /** Флаг логгирования */
    protected $_logging = false;
    /** Строка подключения */
    protected $_dsn = false;
    /** Пользователь БД */
    protected $_userName = false;


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


    # Константы класса
    const ATTR_LOGGING            = 'DB_ATTR_LOGGING';
    const ATTR_LOG_FILE           = 'DB_ATTR_LOG_FILE';
    const ATTR_ERROR_LOG_FILE     = 'DB_ATTR_ERROR_LOG_FILE';



    # Методы класса
    /**
     * Определение параметров БД, определение кодировки по умолчанию
     * @param string $dsn       СУБД или строка подключения
     * @param string $userName  Пользователь
     * @param string $userPass  Пароль
     * @param array  $options   Массив опций подключения
     * @return Db
     * @throws DbException
     *
     * @see http://php.net/manual/ru/pdo.constants.php Предопределённые константы, в том числе, используемые при подключении
     * @see http://php-zametki.ru/php-prodvinutym/58-pdo-konstanty-atributy.html разжёвано по-русски
     */
    public function __construct($dsn, $userName = '', $userPass = '', $options =
        [
            PDO::ATTR_PERSISTENT         => false,
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_SILENT,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_NAMED,
            PDO::ATTR_CASE               => PDO::CASE_LOWER,
        ]
    ){
        $this->_lastQuery    = 'CONNECT';
        $this->_logging      = isset($options[self::ATTR_LOGGING]) ? $options[self::ATTR_LOGGING] : CONFIG::DB_DEBUG;
        $this->_logFile      = isset($options[self::ATTR_LOG_FILE]) ? $options[self::ATTR_LOG_FILE] : CONFIG::DB_LOG_FILE;
        $this->_errorLogFile = isset($options[self::ATTR_ERROR_LOG_FILE]) ? $options[self::ATTR_ERROR_LOG_FILE] : CONFIG::DB_ERROR_LOG_FILE;
        $this->_dsn = $dsn;
        $this->_userName = $userName;
        // Пробуем подключиться, переделывая все исключения в DbException
        try {
            $this->db = new PDO($dsn, $userName, $userPass, $options);
        }catch (Exception $e){
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
        $this->instanceIndex(count(self::$_instances));
        if ($this->logging()){
            $this->log('db_connect');
        }
    }



    /**
     * Логгирование результата и текста запроса
     * @param mixed $action Строковый алиас действия или объект результата
     * @return bool
     */
    public function log($action = null){
        $arr = [
            Log::A_TYPE_NAME             => Log::T_DB_QUERY,
            Log::A_SESSION_ID            => session_id(),
            Log::A_DB_LAST_QUERY         => $this->getLastQuery(),
            Log::A_HTTP_REQUEST_METHOD   => $_SERVER['REQUEST_METHOD'],
            Log::A_HTTP_SERVER_NAME      => $_SERVER['SERVER_NAME'],
            Log::A_HTTP_REQUEST_URI      => $_SERVER['REQUEST_URI'],
            Log::A_HTTP_USER_AGENT       => $_SERVER['HTTP_USER_AGENT'],
            Log::A_HTTP_REMOTE_ADDRESS   => $_SERVER['REMOTE_ADDR']
        ];

        // Если передано выражение PDOStatement, выбираем из него знакомые поля
        if ($action instanceof PDOStatement){
            $arr[Log::A_DB_ROWS_AFFECTED] = $action->rowCount();
            $arr[Log::A_DB_STATUS] = $this->getLastError($action);
            // Попробуем посмотреть, не будет ли здесь расхождений
            if ($arr[Log::A_DB_LAST_QUERY] != $action->queryString) {
                $arr[Log::A_DB_LAST_QUERY] = [
                    'db' => $arr[Log::A_DB_LAST_QUERY],
                    'stmt' => $action->queryString,
                ];
            }

        }else if (is_string($action)){
            $arr[Log::A_DB_QUERY_TYPE] = $action;
        }

        // Пишем полученное в лог
        return Log::save(
            $arr,
            CONFIG::DB_LOG_FILE
        );
    }



    /** Закрытие коннекта */
    public function close(){
        $this->_lastQuery = 'CLOSE';
        self::clearInstance($this->instanceIndex());
        if ($this->logging()){
            $this->log('db_close');
        }
        $this->db = null;
    }






# ------------------------------------------        Синхронные запросы        ------------------------------------------------ #

    /**
     * Базовый метод SQL-запроса
     * @param string $query Текст запроса
     * @param int $fetchType Способ обработки результата
     * @return PDOStatement
     * @throws DbException
     * @see http://php.net/manual/ru/pdo.constants.php Список предопределённых констант
     * Использование, как минимум, с пользовательскими данными не рекомендовано
     */
    public function query($query, $fetchType = null){
        $this->_lastQuery = $query;
        $numArgs = func_num_args();

        if ($numArgs == 1){
            $result = $this->db->query($query);

        }else if ($numArgs == 2 && in_array($fetchType, [PDO::FETCH_LAZY, PDO::FETCH_COLUMN, PDO::FETCH_UNIQUE, PDO::FETCH_KEY_PAIR, PDO::FETCH_NAMED, PDO::FETCH_ASSOC, PDO::FETCH_OBJ, PDO::FETCH_BOTH, PDO::FETCH_NUM])){
            $result = $this->db->query($query, $fetchType);

        }else if ($numArgs == 3 && in_array($fetchType, [PDO::FETCH_COLUMN, PDO::FETCH_INTO])){
            $result = $this->db->query($query, $fetchType, func_get_arg(2));

        }else if ($numArgs == 4 && $fetchType == PDO::FETCH_CLASS){
            $result = $this->db->query($query, $fetchType, func_get_arg(2), func_get_arg(3));

        // Неверные входные данные
        }else{
            throw new DbException(self::E_WRONG_PARAMETERS);
        }

        if ($this->logging()){
            $this->log($result);
        }

        if ($result === false){
            throw new DbException(self::E_UNABLE_TO_PROCESS_QUERY);
        }
        if ($result instanceof PDOStatement && $result->errorCode() !== PDO::ERR_NONE){
            throw new DbException(self::E_UNABLE_TO_PROCESS_QUERY, $result);
        }
        return $result;
    }



    /**
     * SQL запрос к БД для получения одной скалярной величины
     * @param string $query Текст запроса
     * @param mixed $defaultValue Значение по умолчанию
     * @return mixed
     * Использование, как минимум, с пользовательскими данными не рекомендовано
     */
    public function scalarQuery($query, $defaultValue = false){
        $result = $this->query($query, PDO::FETCH_NUM);
        if ($result->rowCount() > 0){
            return $result->fetchColumn(0);
        }
        return $defaultValue;
    }



    /**
     * SQL запрос к БД для получения результата в виде одномерного или двухмерного ассоциативного массива
     * @param string $query Текст запроса
     * @param int $fetchType Формат взвращаемых данных
     * @return array|false
     * @throws DbException
     * Использование, как минимум, с пользовательскими данными не рекомендовано
     * @see http://php.net/manual/ru/pdo.constants.php
     */
    public function associateQuery($query, $fetchType = null){
        $numArgs = func_num_args();

        switch ($numArgs){
            case 1: $result = $this->query($query); break;
            case 2: $result = $this->query($query, $fetchType); break;
            case 3: $result = $this->query($query, $fetchType, func_get_arg(2)); break;
            case 4: $result = $this->query($query, $fetchType, func_get_arg(2), func_get_arg(3)); break;
            default:
                throw new DbException(self::E_WRONG_PARAMETERS);
        }

        if ($result->rowCount() > 1){
            return $result->fetchAll($fetchType);
        }else if ($result->rowCount() == 1){
            return $result->fetch($fetchType);
        }else{
            return false;
        }
    }



    /**
     * Текстовый SQL-запрос без вовзращения табличного результата
     * @param string $statement Текст запроса
     * @return int|bool Число изменённых строк, или false в случае ошибок
     * @throws DbException
     * Использование, как минимум, с пользовательскими данными не рекомендовано
     */
    public function exec($statement){
        $statement = $this->quote($statement);
        $this->_lastQuery = $statement;
        $result = $this->db->exec($statement);
        if ($this->logging()){
            $this->log($result);
        }
        if ($result === false){
            throw new DbException(self::E_UNABLE_TO_PROCESS_QUERY);
        }
    }



    /**
     * Возвращает последний ID, добавленный в БД
     * @return string
     */
    public function lastInsertId(){
        return $this->db !== null ? $this->db->lastInsertId() : false;
    }



    /**
     * Экранирует специальные символы в строке, не принимая во внимание кодировку соединения
     * Не все PDO драйверы реализуют этот метод (особенно PDO_ODBC).
     * Предполагается, что вместо него будут использоваться подготавливаемые запросы.
     * http://php.net/manual/ru/pdo.quote.php
     * @param string $unescapedString Входная строка
     * @param int $parameterType,.. Представляет подсказку о типе данных первого параметра для драйверов, которые имеют альтернативные способы экранирования
     * @return string Возвращает экранированную строку, или false, если драйвер СУБД не поддерживает экранирование
     * @throws DbException Кидает исключение, если дескриптор БД недоступен
     */
    public function quote($unescapedString, $parameterType = PDO::PARAM_STR){
        return $this->db->quote($unescapedString, $parameterType);
    }






# ---------------------------------------       Подготовленные выражения        ------------------------------------------------- #

    /**
     * Подготовка выражения
     * @param string $statement SQL-выражение
     * @param array $driverOptions Атрибуты возвращаемого объекта PDOStatement
     * @return PDOStatement|bool Подготовленное выражение, или false
     * @throws PDOException
     */
    public function stmtPrepare($statement , $driverOptions = []){
        $this->_lastQuery = [
            'PREPARE',
            $statement
        ];
        if (is_array($driverOptions) && count($driverOptions) > 0){
            $this->_lastQuery[] = Log::printObject($driverOptions);
        }
        return $this->db->prepare($statement, $driverOptions);
    }



    /**
     * Выполнение подготовленного выражения
     * В логике проекта объекты PDOStatement лучше выполнять через этот метод, чтобы шло логгирование
     * @param PDOStatement|string $statement Текстовое SQL-выражение, или подготовленное выражение
     * @param array $inputParameters Атрибуты возвращаемого объекта PDOStatement
     * @return bool Флаг успешного, или неуспешного выполнения запроса
     * @throws PDOException|DbException
     */
    public function stmtExecute($statement , $inputParameters = []){
        $this->_lastQuery = ['EXEC'];
        if ($statement instanceof PDOStatement){
            $this->_lastQuery[] = $statement->queryString;

        // Если на входе строка, пробуем подготовить из неё выражение и выполнить
        }else if (is_string($statement)){
            $this->_lastQuery[] = $statement;
            $statement = $this->stmtPrepare($statement);

        }else{
            throw new DbException(self::E_WRONG_PARAMETERS);
        }

        // Экранируем передаваемые параметры и добавляем в лог
        if (!is_array($inputParameters)){
                throw new DbException(self::E_WRONG_PARAMETERS);
        }
        if (count($inputParameters) > 0){
            foreach ($inputParameters as $key => $value){
                $inputParameters[$key] = $this->quote($value);
            }
            $this->_lastQuery[] = Log::printObject($inputParameters);
        }

        return $statement->execute($statement, $inputParameters);
    }






# ----------------------------------------------------------   Транзакции   ------------------------------------------------------------ #

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






# ------------------------------------------       Геттеры, сеттеры и информаторы       ----------------------------------------------- #

    /**
     * Код ошибки соединения
     * @see http://php.net/manual/ru/pdo.errorcode.php
     * @return string
     */
    public function getErrorCode(){
        return $this->db->errorCode();
    }

    /**
     * Ошибка соединения
     * @see http://php.net/manual/ru/pdo.errorinfo.php
     * @return array
     */
    public function getErrorInfo(){
        return $this->db->errorInfo();
    }

    /**
     * Строковое представление ошибки соединения
     * @param PDOStatement $st Выражение, из которого получается информация
     * @return mixed
     */
    public function getLastError(PDOStatement $st = null){
        if ($st){
            $e = ($st instanceof PDOStatement) ? $st->errorInfo() : false;
        }else{
            $e = $this->db->errorInfo();
        }
        return self::formatLastErrorMessage($e);
    }

    /**
     * Получение одного атрибута PDO
     * @param int $attrName Имя атрибута
     * @return mixed
     * @see http://php.net/manual/ru/pdo.getattribute.php
     * @throws PDOException
     */
    public function getAttribute($attrName){
        return $this->db->getAttribute($attrName);
    }

    /** Информация о сервере */
    public function getServerInfo(){
        return '[' . $this->getAttribute(PDO::ATTR_SERVER_VERSION) . '] ' . $this->getAttribute(PDO::ATTR_SERVER_INFO);
    }

    /** Информация о клиенте */
    public function getClientVersion(){
        return $this->getAttribute(PDO::ATTR_CLIENT_VERSION);
    }

    /** Информация о драйвере СУБД */
    public function getDriverName(){
        return $this->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /** Возвращает текст последнего запроса */
    public function getLastQuery(){
        return $this->_lastQuery;
    }

    /**
     * Получение списка доступных драйверов для различных СУБД
     * @return array
     */
    public static function getAvailableDrivers(){
        return PDO::getAvailableDrivers();
    }

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



    /**
     * Возвращает или устанавливает режим логгирования
     * @param  bool $logging Флаг логгирования
     * @return bool Флаг логгирования, или true в случае установки этого флага
     */
    public function logging($logging = null){
        if (func_num_args() == 0){
            return $this->_logging;
        }else{
            $this->_logging = boolval($logging);
            return true;
        }
    }






# -----------------------------------------------   Скрытые методы класса   -------------------------------------------------- #

    /**
     * Возвращает строку со знаками ? для выражений вида ... IN (?, ?,...)
     * @param array $params
     * @return string
     * @see http://phpfaq.ru/pdo#fetchcolumn - внизу страницы
     */
    public static function strIN(Array $params){
        return str_repeat('?,', count($params) - 1) . '?';
    }



    /**
     * Определение в условном числе типа запроса по переданному слову или первому из запроса
     * @param string $text Первое слово запрса
     * @param bool $firstCall Флаг того, что фунцкия вызвана не рекурсивно
     * @return int
     */
    protected static function _getIntQueryType($text, $firstCall = true){
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
     * Форматирование в строку массива с сообщением об ошибке
     * @param array $errorMessage Результат метода lastError() PDOStatement, PDO, или Db
     * @return string
     * Открыта, чтобы использовать в классе исключения
     */
    public static function formatLastErrorMessage($errorMessage){
        return is_array($errorMessage) && count($errorMessage) == 3
            ? isset($errorMessage[1]) && $errorMessage[1] !== null
                ? "[{$errorMessage[0]}] {$errorMessage[1]}: " . isset($errorMessage[2]) && $errorMessage
                    ? $errorMessage[2]
                    : ''
                : $errorMessage[0]
            : false;
    }



    /**
     * Возвращение в текстовом виде информации о подготовленном выражении
     * @param PDOStatement $stmt Подготовленное выражение
     * @param bool $withPre Флаг - оборачивать или нет результат тегами <pre>
     * @return string
     */
    public static function debugDumpParams(PDOStatement $stmt, $withPre = false){
        ob_start();
        $stmt->debugDumpParams();
        $result = ob_get_contents();
        ob_end_clean();
        return $withPre ? '<pre>' . $result . '</pre>' : $result;
    }



    /**
     * Запрос(на изменение БД), составляемый из входных параметров - действия, таблицы и массива параметров
     * ВНИМАНИЕ! Автоматического экранирования данных нет. Контролируйте все параметры процедуры!
     * Все параметры кроме null оборачиваются одинарными кавычками.
     * @param string $action Тип запроса
     * @param string $sourceName Название таблицы или хранимой процедуры
     * @param array $params Столбцы выборки, записи
     * @param mixed $target,.. Параметры выборки или действия
     * @return PDOStatement
     * @throws DbException
     * @deprecated ОСОБАЯ ФИЧА! Метод актуален только тогда, когда доллар стоит меньше 30 рублей
     */
    public function arrayQuery($action, $sourceName, $params, $target = null){
        $action = strtoupper($action);
        switch ($action){
            // Команда вставки данных из массива
            case 'INSERT':
                $paramsCount = count($params);
                if (!$paramsCount){
                    throw new DbException(self::E_WRONG_PARAMETERS);
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
                    throw new DbException(self::E_WRONG_PARAMETERS);
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
                throw new DbException(self::E_WRONG_PARAMETERS);
        }
        return $this->query($line);
    }



    /**
     * Метод, аналогичный методу arrayQuery(), но с автоматическим экранированием параметров
     * @see self::arrayQuery()
     * @param string $action Тип запроса
     * @param string $sourceName Название таблицы или хранимой процедуры
     * @param array $params Столбцы выборки, записи
     * @param mixed $target Параметры выборки или действия
     * @return PDOStatement
     * @throws DbException
     * @deprecated ОСОБАЯ ФИЧА! Метод актуален только тогда, когда доллар стоит меньше 30 рублей
     */
    public function arraySquery($action, $sourceName, $params, $target = null){
        // Пока не реализована обработка сложных условий, а только сравнивание с id, оставим проверку такой
        if (($target !== null) && !is_numeric($target)){
            throw new DbException(self::E_WRONG_PARAMETERS);
        }
        $action = $this->quote($action);
        $sourceName = $this->quote($sourceName);
        $sequredParams = array();
        foreach ($params as $key => $value){
            if ($value){
                $sequredParams[$this->quote($key)] = self::quote($value);
            } else {
                $sequredParams[$this->quote($key)] = $value;
            }
        }
        return $this->arrayQuery($action, $sourceName, $sequredParams, $target);
    }


}

