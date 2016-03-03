<?php
/**
 * Base exception class
 * Special thanks to: all, http://www.php.net
 * Copyright (c)    viktor, Belgorod, 2008-2016
 * Email            vinjoy@bk.ru
 * version          1.2.0
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the MIT License (MIT)
 * @see https://opensource.org/licenses/MIT
 */

require_once('class.Log.php');


/**
 * Класс общего исключения логики проекта
 * @author      viktor
 * @package     Micr0
 * @version     1.2.0
 */
class BaseException extends Exception {

    # Параметры
    protected $logFile;     // Полный путь к файлу лога исключений
    protected $debugging;   // Режим эксплуатации - true/false
    protected $db;          // Дескриптор подключения к БД

    # Языковые константы класса
    const L_ERROR_TITLE = 'Произошла ошибка';

    # Строковые коды ошибок
    const E_BAD_DATA              = 'bad_data';
    const E_BAD_SESSION           = 'bad_session';
    const E_TIMEOUT_ERROR         = 'timeout_error';
    const E_ALIKE_FULL_NAME       = 'alike_full_name';
    const E_ALIKE_NAME            = 'alike_name';
    const E_ALIKE_NICK            = 'alike_nick';
    const E_ALIKE_SYS_NAME        = 'alike_sys_name';
    const E_ALIKE_LOGIN           = 'alike_login';
    const E_ALIKE_EMAIL           = 'alike_email';
    const E_UNKNOWN_COMMAND       = 'unknown_command';
    const E_ACCESS_DENIED         = 'access_denied';
    const E_ACCOUNT_BANNED        = 'account_banned';
    const E_ACCOUNT_INACTIVE      = 'account_inactive';
    const E_FILE_NOT_UPLOADED     = 'file_not_uploaded';
    const E_ALIKE_FILE_NAME       = 'alike_file_name';
    const E_UNKNOWN_FILE          = 'unknown_file';
    const E_UNKNOWN_EMAIL         = 'unknown_email';
    const E_EMAIL_NOT_SEND        = 'email_not_send';
    const E_BAD_AUTHORISATION     = 'bad_authorisation';
    const E_DB_UNREACHABLE        = 'db_unreachable';
    const E_BAD_URL               = 'bad_url';
    const E_UNKNOWN_ITEM          = 'unknown_item';
    const E_WRONG_CAPTCHA         = 'wrong_captcha';
    const E_CANNOT_PERFORM_ACTION = 'cannot_perform_action';
    const E_UNKNOWN_ERROR         = 'unknown_error';



    /**
     * Конструктор класса
     * @param string $message Строковый код исключения
     * @param int $code Числовой код исключения
     * @param Exception $previous Предыдущее исключение в цепочке вызовов
     */
    function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->setLogFile(CONFIG::LOG_FILE);
        $this->setDebugging(CONFIG::DEBUG);
    }



    /**
     * Строковое представление исключения. Если доступны предыдущие исключения, они тоже рекурсивно выводятся
     * @return string
     */
    public function toString(){
        $result = __CLASS__ . ": [{$this->code}]: {$this->message}";
        $prev = $this;
        // Собираем предыдущие исключения
        while ($prev = $prev->getPrevious()){
            if (!is_array($result)){
                $result = [$result];
            }
            if ($prev instanceof BaseException){ // Собираем в цепочку все вызовы исключения, унаследованные от BaseException
                $result[] = $prev->toString();
            }else if ($prev instanceof Exception){ // Из этой строчки видно, что рекурсия закончится на первом стандартном исключении
                $result[] = $prev->__toString();
            }
        }
        return $result;
    }



    /**
     * Представление исключения в виде массива со всей доступной информацией
     * @param string $action Текстовое сообщение
     * @return array
     */
    public function toArray($action = null){
        $trace = $this->getTrace();
        $result = [
            Log::A_TYPE_NAME             => Log::T_EXCEPTION,
            Log::A_SESSION_ID            => session_id(),
            Log::A_EXCEPTION_MESSAGE     => $this->toString(),
            Log::A_PHP_FILE_NAME         => $this->getFile(),
            Log::A_PHP_FILE_LINE         => $this->getLine(),
            Log::A_PHP_TRACE             => serialize($trace),
            Log::A_PHP_ERROR_CODE        => $this->getCode(),
            Log::A_HTTP_REQUEST_METHOD   => $_SERVER['REQUEST_METHOD'],
            Log::A_HTTP_SERVER_NAME      => $_SERVER['SERVER_NAME'],
            Log::A_HTTP_REQUEST_URI      => $_SERVER['REQUEST_URI'],
            Log::A_HTTP_USER_AGENT       => $_SERVER['HTTP_USER_AGENT'],
            Log::A_HTTP_REMOTE_ADDRESS   => $_SERVER['REMOTE_ADDR']
        ];
        if ($action !== null){
            $result[Log::A_TEXT_MESSAGE] = $action;
        }
        return $result;
    }



    /**
     * Запись исключения в лог
     * @param string $action Текстовое сообщение
     * @return bool|int
     */
    public function toLog($action = null){
        return Log::save($this->toArray($action));
    }




// ------------------------------------------   Геттеры         -------------------------------------------------------------- //    

    /**
     * Адрес файла лога исключений
     * @return string
     */
    public function getLogFile() {
        return $this->logFile;
    }
    
    /**
     * Режим эксплуатации
     * @return bool
     */
    public function getDebugging(){
        return $this->debugging;
    }
    
    /**
     * Дескриптор подключения к БД
     * @return Db
     */
    public function getDb(){
        return $this->db;
    }






// ------------------------------------------   Сеттеры         -------------------------------------------------------------- //    
    
    /**
     * Адрес файла лога исключений
     * @param string $logFile
     * @return void
     */
    public function setLogFile($logFile){
        $this->logFile = $logFile;
    }
    
    /**
     * Режим эксплуатации
     * @param bool $debugging
     * @return void
     */
    public function setDebugging($debugging){
        $this->debugging = $debugging;
    }
    
    /**
     * Дескриптор подключения к БД
     * @param Db $db
     * @return void
     */
    public function setDb($db){
        $this->db = $db;
    }

}


