<?php
/**
 * Templates manager (PHP 5 >= 5.6.0)
 * Special thanks to: all, http://www.php.net
 * Copyright (c)    viktor Belgorod, 2016-2016
 * Email            vinjoy@bk.ru
 * Version          1.0.0
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the MIT License (MIT)
 * @see https://opensource.org/licenses/MIT
 */


require_once(__DIR__ . DIRECTORY_SEPARATOR . 'class.BaseException.php');


/** Собственное исключение класса */
class ViewException extends BaseException
{
}



/**
 * Класс шаблонизатора
 * @author      viktor
 * @version     1.0
 * @package     Micr0
 */
class View extends ViewBase
{
    /** @const Расширение файлов шаблонов */
    const FILE_EXT = '.php';


    /**
     * Отрисовка одного шаблона
     * @param string $filename
     * @param array $data
     * @return string
     */
    public static function display($filename, $data)
    {
        if (!file_exists(self::DIR_RUNTIME . $filename . self::FILE_EXT)) {
            ViewTranslator::translateFile($filename);
        }
        require (self::DIR_RUNTIME . $filename . self::FILE_EXT);
        return call_user_func_array([ViewTranslator::getTplClassName($filename), 'display'], [$data]);
    }
} 