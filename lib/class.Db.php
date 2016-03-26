<?php
/**
 * PDO connect сlass (PHP 5 >= 5.4.0)
 * Special thanks to: all, http://www.php.net
 * Copyright (c)    viktor, Belgorod, 2008-2016
 * Email            vinjoy@bk.ru
 * Version          4.0.0
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the MIT License (MIT)
 * @see https://opensource.org/licenses/MIT
 */

require_once(__DIR__ . DIRECTORY_SEPARATOR . "trait.Instances.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "class.Log.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "class.BaseException.php");


//  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
//  -       ПРИ ИСКЛЮЧЕНИИ ВО ВРЕМЯ КОННЕКТА МОЖЕТ УШАТАТЬ В ЛОГ КАК ЛОГИН, ТАК И ПАРОЛЬ!       -
//  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
/**
 * Класс исключения для объектной работы с БД
 * @see http://php.net/manual/ru/class.pdoexception.php PDOException
 */
class DbException extends BaseException
{
    # Сообщения класса
    /** @const Server unreachable */
    const L_SERVER_UNREACHABLE = 'Сервер базы данных недоступен';
    /** @const DB unreachable */
    const L_DB_UNREACHABLE = 'База данных недоступна';
    /** @const Unable to process query */
    const L_UNABLE_TO_PROCESS_QUERY = 'Невозможно обработать запрос';


    /** @property string Файл лога для данных исключений */
    const LOG_FILE = CONFIG::DB_ERROR_LOG_FILE;


    /** @property Db Дескриптор соединения, если передана в конструктор */
    public $db = null;
    /** @property string Информация об ошибке */
    public $errorInfo = '';
    /** @property PDOStatement Последнее подготовленное выражение, если передано в конструктор */
    public $lastStatement = null;
    /** @property string Текст последнего запроса */
    public $lastQuery = '';
    /** @property int Число строк, затронутых последним запросом */
    public $rowCount = '';


    /**
     * Конструктор класса
     * @param string $message Текстовое сообщение об ошибке
     * @param string|PDOStatement|Db $obj Подготовленное выражение, которое, вероятно, вызвало исключение, объект БД, или просто код ошибки
     * @param bool $traceble Флаг доступности для исключения метода getTrace(). Если true, то он вернёт
     * @param Exception $prev Предыдущее исключение
     */
    public function __construct($message, $obj = null, Exception $prev = null, $traceble = true)
    {
        $numArgs = func_num_args();
        if ($numArgs == 1) {
            parent::__construct($message);

        } elseif ($numArgs > 1) {
            if ($obj instanceof PDOStatement) {
                $this->lastStatement = $obj;
                $this->errorInfo = Db::formatLastErrorMessage($this->lastStatement->errorInfo());
                $this->lastQuery = $this->lastStatement->queryString;
                $this->rowCount = $this->lastStatement->rowCount();
                parent::__construct($message, $this->lastStatement->errorCode(), $prev);

            } elseif ($obj instanceof Db) {
                $this->db = $obj;
                $this->errorInfo = $this->db->lastError();
                $this->lastQuery = $this->db->lastQuery();
                parent::__construct($message, $this->db->errorCode(), $prev);

            } else {
                parent::__construct($message, Log::showObject($obj), $prev);
            }
        }
    }


    /**
     * Выжимка исключения в массив
     * @param string $action Текстовое сообщение об ошибке от программиста
     * @return array
     */
    public function toArray($action = null)
    {
        $result = Log::dumpException($this);
        $result[Log::A_EVENT_TYPE] = Log::T_DB_EXCEPTION;
        $result[Log::A_DB_ROWS_AFFECTED] = $this->rowCount;
        $result[Log::A_PHP_ERROR_MESSAGE] = $this->errorInfo;
        $result[Log::A_DB_LAST_QUERY] = $this->lastQuery;

        // Строковый параметр пишем, как сообщение, массив добавляем
        if (is_string($action) && $action !== '') {
            $result[Log::A_TEXT_MESSAGE] = $action;
        } elseif (is_array($action) && count($action) > 0) {
            $result = $result + $action;
        }

        // Из БД или подготовленного выражения тянем все интересные данные
        if ($this->db instanceof Db) {
            $result[Log::A_DB_LAST_ERROR] = $this->db->lastError();
            $result[Log::A_DB_SERVER_INFO] = $this->db->serverInfo();
            if ($this->db->user()) {
                $result[Log::A_DB_USERNAME] = $this->db->user();
            }

        } elseif ($this->lastStatement instanceof PDOStatement) {
            $result[Log::A_DB_LAST_ERROR] = Db::formatLastErrorMessage($this->lastStatement->errorInfo());
            $result[Log::A_DB_ROWS_AFFECTED] = $this->lastStatement->rowCount();
            $str = Db::debugDumpParams($this->lastStatement);
            if ($result[Log::A_DB_LAST_QUERY] != $str) {
                $result[Log::A_DB_LAST_QUERY] = [
                    'Ex' => $result[Log::A_DB_LAST_QUERY],
                    'debug' => $str,
                ];
            }
        }
        return $result;
    }

}


/** @todo Не логгируется ошибка при коннекте */


/**
 * Класс объектной работы с PDO
 * @author      viktor
 * @version     4.0.0
 * @package     Micr0
 *
 * @see http://php.net/manual/ru/book.pdo.php
 * @see https://habrahabr.ru/post/137664/
 * @see http://php.net/manual/ru/pdo.constants.php Предопределённые константы PDO для $options
 * @see http://phpfaq.ru/pdo
 * @see http://phpfaq.ru/SafeMysql https://github.com/colshrapnel/safemysql/blob/master/safemysql.class.php Безопасный класс mysql
 * @see http://ruseller.com/lessons.php?id=610&rub=28 Примеры fetch
 * @see https://github.com/f3ath/LazyPDO/
 */
class Db
{
    # Подключаем трейты
    use Instances; # Работа с инстансами


    # Открытые данные
    /** Дескриптор PDO */
    public $db = null;


    # Закрытые данные
    /** Текст последнего запроса к БД */
    protected $lastQuery = '';
    /** Флаг логгирования */
    protected $_debug = false;
    /** Строка подключения */
    protected $_dsn = false;
    /** Пользователь БД */
    protected $_userName = false;


    # Алиасы параметров класса
    const ATTR_DEBUG = 'DB_ATTR_DEBUG';
    const ATTR_LOG_FILE = 'DB_ATTR_LOG_FILE';
    const ATTR_ERROR_LOG_FILE = 'DB_ATTR_ERROR_LOG_FILE';
    const ATTR_INSTANCE_INDEX = 'DB_ATTR_INSTANCE_INDEX';


    # Методы класса
    /**
     * Определение параметров БД, определение кодировки по умолчанию
     * @param string $dsn СУБД или строка подключения
     * @param string $userName Пользователь
     * @param string $userPass Пароль
     * @param array $options Массив опций подключения
     * @return Db
     * @throws DbException
     *
     * @see http://php.net/manual/ru/pdo.constants.php Предопределённые константы, в том числе, используемые при подключении
     * @see http://php-zametki.ru/php-prodvinutym/58-pdo-konstanty-atributy.html разжёвано по-русски
     */
    public function __construct(
        $dsn,
        $userName = '',
        $userPass = '',
        $options =
        [
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_NAMED,
            PDO::ATTR_CASE => PDO::CASE_LOWER,
        ]
    ) {
        $this->lastQuery = 'CONNECT';
        $this->debug = isset($options[self::ATTR_DEBUG]) ? $options[self::ATTR_DEBUG] : CONFIG::DB_DEBUG;
        $this->logFile = isset($options[self::ATTR_LOG_FILE]) ? $options[self::ATTR_LOG_FILE] : CONFIG::DB_LOG_FILE;
        $this->errorLogFile = isset($options[self::ATTR_ERROR_LOG_FILE]) ? $options[self::ATTR_ERROR_LOG_FILE] : CONFIG::DB_ERROR_LOG_FILE;
        $this->dsn = $dsn;
        $this->userName = $userName;
        // Пробуем подключиться, переделывая возможные исключения в DbException
        try {
            $this->db = new PDO($dsn, $userName, $userPass, $options);
        } catch (Exception $e) {
            if (CONFIG::DB_DEBUG) {
                throw new DbException($e->getMessage(), $e->getCode(), $e);
            } else {
                trigger_error($e->getMessage() . ': ' . $e->getCode(), E_USER_ERROR);
            }
        }
        $this->instanceIndex(isset($options[self::ATTR_INSTANCE_INDEX]) ? $options[self::ATTR_INSTANCE_INDEX] : null);
        if ($this->debug()) {
            $this->log('db_connect');
        }
    }


    /**
     * Логгирование результата и текста запроса
     * @param mixed $action Строковый алиас действия или объект результата
     * @return bool
     */
    public function log($action = null)
    {
        $arr = [
            Log::A_EVENT_TYPE => Log::T_DB_QUERY,
            Log::A_SESSION_ID => session_id(),
            Log::A_DB_LAST_QUERY => $this->lastQuery(),
            Log::A_HTTP_REQUEST_METHOD => $_SERVER['REQUEST_METHOD'],
            Log::A_HTTP_SERVER_NAME => $_SERVER['SERVER_NAME'],
            Log::A_HTTP_REQUEST_URI => $_SERVER['REQUEST_URI'],
            Log::A_HTTP_USER_AGENT => $_SERVER['HTTP_USER_AGENT'],
            Log::A_HTTP_REMOTE_ADDRESS => $_SERVER['REMOTE_ADDR']
        ];

        // Если передано выражение PDOStatement, выбираем из него знакомые поля
        if ($action instanceof PDOStatement) {
            $arr[Log::A_DB_ROWS_AFFECTED] = $action->rowCount();
            $arr[Log::A_DB_STATUS] = $this->lastError($action);
            // Попробуем посмотреть, не будет ли здесь расхождений
            if ($arr[Log::A_DB_LAST_QUERY] != $action->queryString) {
                $arr[Log::A_DB_LAST_QUERY] = [
                    'db' => $arr[Log::A_DB_LAST_QUERY],
                    'stmt' => $action->queryString,
                ];
            }

        } elseif (is_string($action)) {
            $arr[Log::A_DB_QUERY_TYPE] = $action;
        }

        // Пишем полученное в лог
        return Log::save(
            $arr,
            CONFIG::DB_LOG_FILE
        );
    }


    /** Закрытие коннекта */
    public function close()
    {
        $this->lastQuery = 'CLOSE';
        self::clearInstance($this->instanceIndex());
        if ($this->debug()) {
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
    public function query($query, $fetchType = null)
    {
        $this->lastQuery = $query;
        $numArgs = func_num_args();

        if ($numArgs == 1) {
            $result = $this->db->query($query);

        } elseif ($numArgs == 2 && in_array($fetchType, [
                PDO::FETCH_LAZY,
                PDO::FETCH_COLUMN,
                PDO::FETCH_UNIQUE,
                PDO::FETCH_KEY_PAIR,
                PDO::FETCH_NAMED,
                PDO::FETCH_ASSOC,
                PDO::FETCH_OBJ,
                PDO::FETCH_BOTH,
                PDO::FETCH_NUM
            ])
        ) {
            $result = $this->db->query($query, $fetchType);

        } elseif ($numArgs == 3 && in_array($fetchType, [PDO::FETCH_COLUMN, PDO::FETCH_INTO])) {
            $result = $this->db->query($query, $fetchType, func_get_arg(2));

        } elseif ($numArgs == 4 && $fetchType == PDO::FETCH_CLASS) {
            $result = $this->db->query($query, $fetchType, func_get_arg(2), func_get_arg(3));

            // Неверные входные данные
        } else {
            throw new DbException(DbException::L_WRONG_PARAMETERS);
        }

        if ($this->debug()) {
            $this->log($result);
        }

        if ($result === false) {
            throw new DbException(DbException::L_UNABLE_TO_PROCESS_QUERY);
        }
        if ($result instanceof PDOStatement && $result->errorCode() !== PDO::ERR_NONE) {
            throw new DbException(DbException::L_UNABLE_TO_PROCESS_QUERY, $result);
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
    public function scalarQuery($query, $defaultValue = false)
    {
        $result = $this->query($query, PDO::FETCH_NUM);
        if ($result->rowCount() > 0) {
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
    public function assocQuery($query, $fetchType = null)
    {
        $numArgs = func_num_args();

        switch ($numArgs) {
            case 1:
                $result = $this->query($query);
                break;
            case 2:
                $result = $this->query($query, $fetchType);
                break;
            case 3:
                $result = $this->query($query, $fetchType, func_get_arg(2));
                break;
            case 4:
                $result = $this->query($query, $fetchType, func_get_arg(2), func_get_arg(3));
                break;
            default:
                throw new DbException(DbException::L_WRONG_PARAMETERS);
        }

        if ($result->rowCount() > 1) {
            return $result->fetchAll($fetchType);
        } elseif ($result->rowCount() == 1) {
            return $result->fetch($fetchType);
        } else {
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
    public function execQuery($statement)
    {
        $statement = $this->quote($statement);
        $this->lastQuery = $statement;
        $result = $this->db->exec($statement);
        if ($this->debug()) {
            $this->log($result);
        }
        if ($result === false) {
            throw new DbException(DbException::L_UNABLE_TO_PROCESS_QUERY);
        }
    }


    /**
     * Возвращает последний ID, добавленный в БД
     * @return string
     */
    public function lastInsertId()
    {
        return $this->db !== null ? $this->db->lastInsertId() : false;
    }


    /**
     * Экранирует специальные символы в строке, не принимая во внимание кодировку соединения
     * Не все PDO драйверы реализуют этот метод (особенно PDO_ODBC).
     * Предполагается, что вместо него будут использоваться подготавливаемые запросы.
     * http://php.net/manual/ru/pdo.quote.php
     * @param string $unescapedString Входная строка
     * @param int $parameterType ,.. Представляет подсказку о типе данных первого параметра для драйверов, которые имеют альтернативные способы экранирования
     * @return string Возвращает экранированную строку, или false, если драйвер СУБД не поддерживает экранирование
     * @throws DbException Кидает исключение, если дескриптор БД недоступен
     */
    public function quote($unescapedString, $parameterType = PDO::PARAM_STR)
    {
        return $this->db->quote($unescapedString, $parameterType);
    }


    /**
     * Удаление экранирования спецсимволов SQL в строке
     * @param string $escapedString
     * @return string
     */
    public static function unQuote($escapedString)
    {
        // Нечего разэкранировать
        if (mb_strpos($escapedString, '\\', 0, 'UTF-8') === false) {
            return $escapedString;
        }
        // Проверка на JSON
        if (is_string($escapedString) && in_array($escapedString[0], ['{', '[']) && json_decode($escapedString, true)) {
            $escapedString = str_replace('\\', '\\\\', $escapedString);
            $escapedString = str_replace('\"', '\\\"', $escapedString);
        }
        return stripslashes($escapedString);
    }






# ---------------------------------------       Подготовленные выражения        ------------------------------------------------- #

    /**
     * Подготовка выражения
     * @param string $statement SQL-выражение
     * @param array $driverOptions Атрибуты возвращаемого объекта PDOStatement
     * @return PDOStatement|bool Подготовленное выражение, или false
     * @throws PDOException
     */
    public function prepare($statement, $driverOptions = [])
    {
        $this->lastQuery = [
            'PREPARE',
            $statement
        ];
        if (is_array($driverOptions) && count($driverOptions) > 0) {
            $this->lastQuery[] = Log::printObject($driverOptions);
        }
        return $this->db->prepare($statement, $driverOptions);
    }


    /**
     * Выполнение подготовленного выражения
     * В логике проекта объекты PDOStatement лучше выполнять через этот метод, чтобы шло логгирование запросов при дебаге
     * @param PDOStatement|string $statement Текстовое SQL-выражение, или подготовленное выражение
     * @param array $inputParameters Атрибуты возвращаемого объекта PDOStatement
     * @return bool Флаг успешного, или неуспешного выполнения запроса
     * @throws PDOException|DbException
     */
    public function execute($statement, $inputParameters = null)
    {
        $this->lastQuery = ['EXEC'];
        if ($statement instanceof PDOStatement) {
            $this->lastQuery[] = $statement->queryString;

            // Если на входе строка, пробуем подготовить из неё выражение и выполнить
        } elseif (is_string($statement)) {
            $this->lastQuery[] = $statement;
            $statement = $this->prepare($statement);

        } else {
            throw new DbException(DbException::L_WRONG_PARAMETERS);
        }

        // Проверяем параметры и добавляем в лог
        if ($inputParameters !== null && !is_array($inputParameters)) {
            throw new DbException(DbException::L_WRONG_PARAMETERS);
        }
        if (count($inputParameters) > 0) {
            $this->lastQuery[] = Log::printObject($inputParameters);
        }

        return $statement->execute($statement, $inputParameters);
    }






# ----------------------------------------------------------   Транзакции   ------------------------------------------------------------ #

    /**
     * Режим автоматических транзакций - включён или выключен
     * @param int $autocommitMode
     * @return mixed
     */
    public function autocommitMode($autocommitMode = null)
    {
        if (func_num_args() == 0) {
            return $this->attribute(PDO::ATTR_AUTOCOMMIT);
        } else {
            return $this->attribute(PDO::ATTR_AUTOCOMMIT, $autocommitMode);
        }
    }

    /** Начало транзакции */
    public function beginTransaction()
    {
        return $this->db->beginTransaction();
    }

    /** Подтверждение изменений */
    public function commit()
    {
        return $this->db->commit();
    }

    /** Отмена внесенных изменений */
    public function rollBack()
    {
        return $this->db->rollBack();
    }

    /** Проверка на наличие открытой транзакии */
    public function inTransaction()
    {
        return $this->db->inTransaction();
    }






# ------------------------------------------       Геттеры, сеттеры и информаторы       ----------------------------------------------- #

    /**
     * Код ошибки соединения
     * @see http://php.net/manual/ru/pdo.errorcode.php
     * @return string
     */
    public function errorCode()
    {
        return $this->db->errorCode();
    }

    /**
     * Ошибка соединения
     * @see http://php.net/manual/ru/pdo.errorinfo.php
     * @return array
     */
    public function errorInfo()
    {
        return $this->db->errorInfo();
    }

    /**
     * Строковое представление ошибки соединения или подготовленного выражения
     * @param PDOStatement $st Выражение, из которого получается информация
     * @return mixed
     */
    public function lastError(PDOStatement $st = null)
    {
        if ($st) {
            $e = ($st instanceof PDOStatement) ? $st->errorInfo() : false;
        } else {
            $e = $this->db->errorInfo();
        }
        return self::formatLastErrorMessage($e);
    }

    /** Информация о сервере */
    public function serverInfo()
    {
        return '[' . $this->attribute(PDO::ATTR_SERVER_VERSION) . '] ' . $this->attribute(PDO::ATTR_SERVER_INFO);
    }

    /** Информация о клиенте */
    public function clientVersion()
    {
        return $this->attribute(PDO::ATTR_CLIENT_VERSION);
    }

    /** Информация о драйвере СУБД */
    public function driverName()
    {
        return $this->attribute(PDO::ATTR_DRIVER_NAME);
    }

    /** Возвращает текст последнего запроса */
    public function lastQuery()
    {
        return $this->lastQuery;
    }

    /**
     * Возвращает имя пользователя, с которым осуществлено подключение
     * @return string
     */
    public function user()
    {
        return $this->userName;
    }

    /**
     * Получение списка доступных драйверов для различных СУБД
     * @return array
     */
    public static function availableDrivers()
    {
        return PDO::getAvailableDrivers();
    }

    /**
     * Получение или установка одного атрибута PDO
     * @param int $attrName Имя атрибута
     * @param mixed $attrValue Значение атрибута
     * @return mixed
     * @see http://php.net/manual/ru/pdo.getattribute.php
     * @throws PDOException
     */
    public function attribute($attrName, $attrValue = null)
    {
        if (func_num_args() == 1) {
            return $this->db->getAttribute($attrName);
        } else {
            return $this->db->setAttribute($attrName, $attrValue);
        }
    }

    /**
     * Возвращает или устанавливает режим дебага
     * @param  bool $debug Флаг логгирования
     * @return bool Флаг логгирования, или true в случае установки этого флага
     */
    public function debug($debug = null)
    {
        if (func_num_args() == 0) {
            return $this->debug;
        } else {
            $this->debug = boolval($debug);
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
    public static function strIN(array $params)
    {
        return str_repeat('?,', count($params) - 1) . '?';
    }


    /**
     * Формирование одной строки запроса вставки. В данном методе фильтрация не производится
     * @param array $data Ассоциативный массив параметров вставки
     * @return string
     */
    protected function _formInsertQuery($data)
    {
        $t = each($data);
        $result = is_numeric($t[1]) || $t[1] == 'null' ? $t[1] : "'{$t[1]}'";
        while ($t = each($data)) {
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
    public static function formatLastErrorMessage($errorMessage)
    {
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
    public static function debugDumpParams(PDOStatement $stmt, $withPre = false)
    {
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
     * @param mixed $target ,.. Параметры выборки или действия
     * @return PDOStatement
     * @throws DbException
     * @deprecated ОСОБАЯ ФИЧА! Метод актуален только тогда, когда доллар стоит меньше 30 рублей
     */
    public function arrayQuery($action, $sourceName, $params, $target = null)
    {
        $action = strtoupper($action);
        switch ($action) {
            // Команда вставки данных из массива
            case 'INSERT':
                $paramsCount = count($params);
                if (!$paramsCount) {
                    throw new DbException(DbException::L_WRONG_PARAMETERS);
                }
                // Если массив данных двухмерный
                if (is_array(reset($params))) {
                    $qParams = array(0 => '`' . implode('`, `', array_keys(current($params))) . '`', '');
                    $data = array();
                    foreach ($params as $rowArr) {
                        $row = array();
                        foreach ($rowArr as $el) {
                            $row[] = $el === null ? 'null' : "'$el'";
                        }
                        $data[] = '(' . implode(', ', $row) . ')';
                    }
                    $qParams[1] = implode(', ', $data);
                } else {
                    $t = each($params);
                    $qParams = array('`' . $t[0] . '`', $t[1] === null ? 'null' : "'{$t[1]}'");
                    while ($t = each($params)) {
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
                if (!($paramsCount)) {
                    throw new DbException(DbException::L_WRONG_PARAMETERS);
                }
                $t = each($params);
                $qParams = '`' . $t[0] . '` = ' . ($t[1] === null ? 'null' : "'{$t[1]}'");
                while ($t = each($params)) {
                    $qParams .= ', `' . $t[0] . '` = ' . ($t[1] === null ? 'null' : "'{$t[1]}'");
                }
                $line = "UPDATE $sourceName SET $qParams WHERE `id` = $target LIMIT 1";
                break;

            // Команда выборки данны из таблицы
            case 'SELECT':
                $line = "SELECT `" . implode('`, `', $params) . "` FROM $sourceName";
                if ($target !== null) {
                    $line .= " WHERE `id` = $target";
                }
                break;

            // Команда запуска хранимой процедуры
            case 'CALL':
                $line = "CALL $sourceName(" . implode(', ', $params) . ')';
                break;

            default:
                throw new DbException(DbException::L_WRONG_PARAMETERS);
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
    public function arraySQuery($action, $sourceName, $params, $target = null)
    {
        // Пока не реализована обработка сложных условий, а только сравнивание с id, оставим проверку такой
        if (($target !== null) && !is_numeric($target)) {
            throw new DbException(DbException::L_WRONG_PARAMETERS);
        }
        $action = $this->quote($action);
        $sourceName = $this->quote($sourceName);
        $sequredParams = array();
        foreach ($params as $key => $value) {
            if ($value) {
                $sequredParams[$this->quote($key)] = $this->quote($value);
            } else {
                $sequredParams[$this->quote($key)] = $value;
            }
        }
        return $this->arrayQuery($action, $sourceName, $sequredParams, $target);
    }
}

