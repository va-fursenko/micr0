<?php
/**
 * Error'n'Exceptions handler class
 * Special thanks to: all, http://www.php.net
 * Copyright (c)    viktor, Belgorod, 2008-2016
 * Email            vinjoy@bk.ru
 * version          2.0.0
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the MIT License (MIT)
 * @see https://opensource.org/licenses/MIT
 */


function errorHandlerCatchError($code, $msg, $file, $line){
    return ErrorHandler::pushError(array(
        'datetime'          => date("Y-m-d H:i:s"),
        'session_id'        => session_id(),
        'php_error_code'    => $code,
        'php_file_name'     => $file,
        'php_file_line'     => $line,
        'text_message'      => $msg
    ));
}


/**
 * Класс работы со стеком ошибок
 * @version   2.0.0
 * @author    viktor
 * @package   Micr0
 */
class ErrorHandler{
    /** Режим отладки */
    protected static $_debugMode = true;

    /** Обработчик ошибок */
    protected static $_errorHandler = null;
    /** Обработчик завершения работы скрипта */
    protected static $_shutdownHandler = null;
    /** Обработчик исключений */
    protected static $_exceptionHandler = null;



    /**
     * Устанавливает обработчик на программные ошибки
     * @param callable $func Обработчик ошибок вида bool function (int $errno, string $errstr [, string $errfile [, int $errline [, array $errcontext ]]] )
     * @return mixed
     * @see http://php.net/manual/ru/function.set-error-handler.php
     */
    static function setErrorHandler(callable $func){
        self::$_errorHandler = $func;
        return set_error_handler($func, E_ALL);
    }



    /**
     * Устанавливает обработчик на программные ошибки
     * @param callable $func Обработчик исключений вида void function (Exception $e){}
     * @return callable
     * @see http://php.net/manual/ru/function.set-exception-handler.php
     */
    static function setExceptionHandler(callable $func){
        self::$_exceptionHandler = $func;
        return set_exception_handler($func);
    }



    /**
     * Устанавливает обработчик на окончание скрипта
     * @param callable $func Обработчик ошибок
     * @return void
     */
    static function setShutdownHandler(callable $func){
        self::$_shutdownHandler = $func;
        register_shutdown_function($func);
    }



    /**
     * Установка режима отладки
     * @param bool $debugMode
     * @return bool|void
     */
    static function debugMode($debugMode = null){
        if (func_num_args() == 1){
            self::$_debugMode = $debugMode;
        }else{
            return self::$_debugMode;
        }
    }

 }


