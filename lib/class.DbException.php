<?php
/**
 * Db Exception сlass (PHP 5 >= 5.4.0)
 * Special thanks to: all, http://www.php.net
 * Copyright (c)    viktor, Belgorod, 2008-2016
 * Email            vinjoy@bk.ru
 * Version          1.1.0
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the MIT License (MIT)
 * @see https://opensource.org/licenses/MIT
 */

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
    public $errorInfo = null;
    /** @property PDOStatement Последнее подготовленное выражение, если передано в конструктор */
    public $lastStatement = null;
    /** @property string Текст последнего запроса */
    public $lastQuery = null;
    /** @property int Число строк, затронутых последним запросом */
    public $rowsAffected = null;


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
                $this->rowsAffected = $this->lastStatement->rowCount();
                parent::__construct($message, $this->lastStatement->errorCode(), $prev);

            } elseif ($obj instanceof Db) {
                $this->db = $obj;
                $this->errorInfo = $this->db->lastError();
                $this->lastQuery = $this->db->lastQuery();
                //$this->rowsAffected = $this->db->;
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
        if ($this->rowsAffected !== null) {
            $result[Log::A_DB_ROWS_AFFECTED] = $this->rowsAffected;
        }
        if ($this->errorInfo) {
            $result[Log::A_PHP_ERROR_MESSAGE] = $this->errorInfo;
        }
        if ($this->lastQuery) {
            $result[Log::A_DB_LAST_QUERY] = $this->lastQuery;
        }

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
