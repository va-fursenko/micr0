<?php
/**
 * PDO connect сlass (PHP 5 >= 5.4.0)
 * Special thanks to: all, http://www.php.net
 * Copyright (c)    viktor, Belgorod, 2008-2016
 * Email            vinjoy@bk.ru
 * Version          4.0.3
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the MIT License (MIT)
 * @see https://opensource.org/licenses/MIT
 */

require_once(__DIR__ . DIRECTORY_SEPARATOR . "trait.Instances.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "class.Log.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "class.DbException.php");




/** @todo Не логгируется ошибка при коннекте */


/**
 * Класс объектной работы с PDO
 * @author      viktor
 * @version     4.0.3
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
    /** Число строк, затронутых последним запросом */
    protected $rowCount = '';
    /** Флаг логгирования */
    protected $debug = false;
    /** Строка подключения */
    protected $dsn = false;
    /** Пользователь БД */
    protected $userName = false;


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
            if ($this->isDebug()) {
                throw new DbException($e->getMessage(), $e->getCode(), $e);
            } else {
                trigger_error($e->getMessage() . ': ' . $e->getCode(), E_USER_ERROR);
            }
        }
        $this->instanceIndex(isset($options[self::ATTR_INSTANCE_INDEX]) ? $options[self::ATTR_INSTANCE_INDEX] : null);
        if ($this->isDebug()) {
            $this->toLog('db_connect');
        }
    }


    /**
     * Логгирование результата и текста запроса
     * @param mixed $action Строковый алиас действия или объект результата
     * @return bool
     */
    public function toLog($action = null)
    {
        $arr = [
            Log::A_EVENT_TYPE          => Log::T_DB_QUERY,
            Log::A_SESSION_ID          => session_id(),
            Log::A_DB_LAST_QUERY       => $this->lastQuery(),
            Log::A_DB_ROWS_AFFECTED    => $this->rowCount(),
            Log::A_HTTP_REQUEST_METHOD => $_SERVER['REQUEST_METHOD'],
            Log::A_HTTP_SERVER_NAME    => $_SERVER['SERVER_NAME'],
            Log::A_HTTP_REQUEST_URI    => $_SERVER['REQUEST_URI'],
            Log::A_HTTP_USER_AGENT     => $_SERVER['HTTP_USER_AGENT'],
            Log::A_HTTP_REMOTE_ADDRESS => $_SERVER['REMOTE_ADDR']
        ];

        // Если передано выражение PDOStatement, выбираем из него знакомые поля
        if ($action instanceof PDOStatement) {
            $arr[Log::A_DB_ROWS_AFFECTED] = $action->rowCount();
            $arr[Log::A_DB_STATUS] = $this->lastError($action);
            // Попробуем посмотреть, не будет ли здесь расхождений
            if ($arr[Log::A_DB_LAST_QUERY] != $action->queryString) {
                $arr[Log::A_DB_LAST_QUERY] = [
                    'db'   => $arr[Log::A_DB_LAST_QUERY],
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
        if ($this->isDebug()) {
            $this->toLog('db_close');
        }
        $this->db = null;
    }






# ------------------------------------------        Синхронные запросы        ------------------------------------------------ #

    /**
     * Базовый метод SQL-запроса
     * @param string $query Текст запроса
     * @param array $params Параметры запроса
     * @param int $fetchType Способ обработки результата
     * @return PDOStatement
     * @throws DbException
     * @see http://php.net/manual/ru/pdo.constants.php Список предопределённых констант
     */
    public function query($query, array $params = [], $fetchType = PDO::FETCH_ASSOC)
    {
        $numArgs = func_num_args();

        $this->lastQuery = $query;
        $stmt = $this->db->prepare($query);
        // Если запрос не выполнился, дальше делать нечего
        if (!$stmt->execute($params)) {
            throw new DbException(DbException::L_UNABLE_TO_PROCESS_QUERY);
        }
        $this->rowCount = $stmt->rowCount();

        // Подразумевается, что $fetchType по умолчанию относится к этой категории
        if ($numArgs < 4 && in_array($fetchType, [
                PDO::FETCH_LAZY, PDO::FETCH_COLUMN, PDO::FETCH_UNIQUE, PDO::FETCH_KEY_PAIR,
                PDO::FETCH_NAMED, PDO::FETCH_ASSOC, PDO::FETCH_OBJ, PDO::FETCH_BOTH, PDO::FETCH_NUM
            ])
        ) {
            $result = $stmt->fetchAll($fetchType);

        } elseif ($numArgs == 4 && in_array($fetchType, [PDO::FETCH_COLUMN, PDO::FETCH_INTO])) {
            $result = $stmt->fetchAll($query, $fetchType, func_get_arg(3));

        } elseif ($numArgs == 5 && $fetchType == PDO::FETCH_CLASS) {
            $result = $stmt->fetchAll($query, $fetchType, func_get_arg(3), func_get_arg(4));

        } else {
            throw new DbException(DbException::L_WRONG_PARAMETERS);
        }

        if ($this->isDebug()) {
            $this->toLog($result);
        }
        return $result;
    }


    /**
     * SQL запрос к БД для получения одной скалярной величины
     * @param string $query Текст запроса
     * @param array $params Параметры запроса
     * @param mixed $defaultValue Значение по умолчанию
     * @return mixed
     */
    public function selectOne($query, array $params = [], $defaultValue = false)
    {
        $result = $this->query($query, $params, PDO::FETCH_NUM);
        return is_array($result) && count($result) > 0 ? $result[0][0] : $defaultValue;
    }


    /**
     * Текстовый SQL-запрос без вовзращения табличного результата
     * @param string $statement Текст запроса
     * @param array $params Массив параметров запроса
     * @return int|bool Число изменённых строк, или false в случае ошибок
     * @throws DbException
     * Использование, как минимум, с пользовательскими данными не рекомендовано
     */
    public function exec($statement, array $params = [])
    {
        $this->lastQuery = $statement;
        $stmt = $this->db->prepare($statement);
        if (!$stmt->execute($params)) {
            throw new DbException(DbException::L_UNABLE_TO_PROCESS_QUERY);
        }
        $this->rowCount = $stmt->rowCount();
        if ($this->isDebug()) {
            $this->toLog();
        }
        return true;
    }


    /**
     * Возвращает последний ID, добавленный в БД
     * @return string
     */
    public function lastInsertId()
    {
        return $this->db->lastInsertId();
    }


    /**
     * Экранирует специальные символы в строке, не принимая во внимание кодировку соединения
     * Не все PDO драйверы реализуют этот метод (особенно PDO_ODBC).
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


    /** Число рядов, затронутых последним запросом */
    public function rowCount()
    {
        return $this->rowCount;
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
    public function isDebug($debug = null)
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
    protected function formInsertQuery($data)
    {
        $result = '';
        while ($t = each($data)) {
            $result .= (strlen($result) > 0 ? ', ' : '') .
                (is_numeric($t[1]) || $t[1] == 'null' ? $t[1] : "'{$t[1]}'");
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
}
