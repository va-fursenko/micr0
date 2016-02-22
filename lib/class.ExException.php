<?php

/**
 *      Exception class
 *      Special thanks to: Stascer, http://www.php.su
 *      Copyright (c) Enjoy! Belgorod, 2011
 *      Email             vinjoy@bk.ru
 *      version           1.1.1
 *      Last modifed      14:53 12.09.11
 *        
 *      This library is free software; you can redistribute it and/or
 *      modify it under the terms of the GNU Lesser General Public
 *      License as published by the Free Software Foundation; either
 *      version 2.1 of the License, or (at your option) any later version.
 *      @see http://www.gnu.org/copyleft/lesser.html
 *      @author Enjoy
 *        
 *      Не удаляйте данный комментарий, если вы хотите использовать скрипт! 
 *      Do not delete this comment if you want to use the script!
 *
 */

/**
 * Класс общего исключения логики проекта
 * @author    viktor
 * @version   1.1.1
 * @copyright viktor
 * @deprecated Данная историческая залупа сильно под вопросом
 */
class ExException extends Exception {

    // Параметры
    protected $logFile;     // Полный путь к файлу лога исключений
    protected $debugging;   // Режим эксплуатации - true/false
    protected $db;          // Дескриптор подключения к БД
    
    // Языковые константы класса
    const L_ERROR_TITLE = 'Произошла ошибка';
    // Строковые коды ошибок
    const E_BAD_DATA              = 'bad_data';
    const E_BAD_SESSION           = 'bad_session';
    const E_TIMEOUT_ERROR         = 'timeout_error';
    const E_ALIKE_FULL_NAME       = 'alike_full_name';
    const E_ALIKE_NAME            = 'alike_name';
    const E_ALIKE_NICK            = 'alike_nick';
    const E_ALIKE_SYSNAME         = 'alike_sysname';
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
    const E_EMAIL_NOT_SENDED      = 'email_not_sended';
    const E_BAD_AUTHORISATION     = 'bad_authorisation';
    const E_DB_UNREACHABLE        = 'db_unreachable';
    const E_BAD_URL               = 'bad_url';
    const E_UNKNOWN_ITEM          = 'unknown_item';
    const E_WRONG_CAPTCHA         = 'wrong_captcha';
    const E_CANNOT_PERFORM_ACTION = 'cannot_perform_action';
    const E_UNKNOWN_ERROR         = 'unknown_error';

    /**
     * Конструктор класса
     * @param string $throwCode Строковый код исключения
     * @param int $code,.. Числовой код исключения
     * @param Exception $previus,.. Предыдущее исключение в цепочке вызовов
     */
    function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->logFile = EX_LOG_FILE;
        $this->debugging = EX_DEBUG;
    }

    /**
     * Вызов исключения с кодом ошибки
     * @param string $message Строковый код вызова исключения
     * @param int $code,.. Числовой код исключения
     * @param Exception $previus,.. Предыдущее исключение в цепочке вызовов
     */
    static public function throwEx($message = '', $code = 0, Exception $previous = null) {
        throw new Ex($message, $code, $previous);
    }

    /**
     * Обработка перехваченного исключения
     * @param string $handleCode Строковый код перехвата исключения
     * @param boolean $returnText Флаг. Возвращать пользовательское сообщение об ошибке или результат аякс-запроса
     * в виде array('state' => false, 'error' => $throwCode)
     * @return string Текстовое сообщение о произошедшей ошибке
     */
    public function handleEx($handleCode = '', $returnText = true) {
        $trace = $this->getTrace();
        Log::write(
            //$this->getLogFile(), 
            array(
                'type_name'             => 'php_exception',
                'session_id'            => session_id(),
                'session_user_id'       => Session::userGet('id', -1),
                'text_message'          => $handleCode,
                'exception_message'     => $this->getMessage(),
                'php_file_name'         => $this->getFile(),
                'php_file_line'         => $this->getLine(),
                'php_file_name'         => $trace[1]['file'],
                'php_file_line'         => $trace[1]['line'],
                'php_trace'             => serialize($this->getTrace()),
                'php_error_code'        => $this->getCode(),
                'http_request_method'   => $_SERVER['REQUEST_METHOD'],
                'http_server_name'      => $_SERVER['SERVER_NAME'],
                'http_request_uri'      => $_SERVER['REQUEST_URI'],
                'http_user_agent'       => $_SERVER['HTTP_USER_AGENT'],
                'http_remote_addr'      => $_SERVER['REMOTE_ADDR']
            )
        );
        if ($returnText){
            // Вывод пользовательского сообщения или подробной информации об исключении
            if ($this->getDebugging()){
                return Log::parseMessage($messageArr); 
            }else{
                return array(
                    'js'      => '',
                    'caption' => self::L_ERROR_TITLE,
                    'content' => ErrorHandler::getUserError($this->getMessage())
                );
            }
        }else{// Вывод результата аякс-запроса
            return array('state' => false, 'error' => $this->getMessage(), 'html' => '');
        }
    }

// ------------------------------------------   Геттеры         -------------------------------------------------------------- //    

    /** Адрес файла лога исключений */
    public function getLogFile() {
        return $this->logFile;
    }
    
    /** Режим эксплуатации */
    public function getDebugging(){
        return $this->debugging;
    }
    
    /** Дескриптор подключения к БД */
    public function getDb(){
        return $this->db;
    }
    
// ------------------------------------------   Сеттеры         -------------------------------------------------------------- //    
    
    /** Адрес файла лога исключений */
    public function setLogFile($logFile){
        $this->logFile = $logFile;
    }
    
    /** Режим эксплуатации */
    public function setDebugging($debugging){
        $this->debugging = $debugging;
    }
    
    /** Дескриптор подключения к БД */
    public function setDb($db){
        $this->db = $db;
    }

}

?>
