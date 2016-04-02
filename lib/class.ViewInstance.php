<?php
/**
 * Templates instance сlass (PHP 5 >= 5.6.0)
 * Special thanks to: all, http://www.php.net
 * Copyright (c)    viktor Belgorod, 2009-2016
 * Email            vinjoy@bk.ru
 * Version          1.2.0
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the MIT License (MIT)
 * @see https://opensource.org/licenses/MIT
 */


/** Собственное исключение класса */
class ViewInstanceException extends ViewTranslatorException
{
}


/**
 * Абстрактный класс-предок для кешированных в PHP шаблонов
 * @author      viktor
 * @version     1.2
 * @package     Micr0
 */
abstract class ViewInstance extends ViewVar
{
    /**
     * @var array $data Контекст шаблона
     */
    protected static $data = [];


    /**
     * Метод вывода в PHP одной переменной
     * @param string $varName Имя переменной (индекс в массиве контекста self::$data)
     * @param bool $escape Флаг экранирования html
     * @param array $base Контекст переменной
     * @param string $altVarName Альтернативное полное имя переменной в контексте (используется в итераторах)
     * @return mixed
     * @throws ViewInstanceException
     */
    protected static function getVar($varName, $escape = false, array $base = null, $altVarName = null)
    {
        if ($base === null) {
            $base = &self::$data;
        }
        if ($altVarName !== null && self::hasVar($base, $altVarName)) {
            $varName = $altVarName;
        } elseif (!self::hasVar($base, $varName)) {
            return ViewBase::VAR_BEGIN . " '$varName' " . ViewBase::VAR_END;
            /* Или так, или так...
            return '';
            throw new ViewInstanceException(
                ViewInstanceException::L_WRONG_PARAMETERS .
                    ": '$varName'" . ($varIndex ? ".'$varIndex'" : '')
            )
            */
        }
        return self::parseVar($base, $varName, $escape ? 'e' : '');
    }


    /**
     * Метод вывода экранированной, или не экранированной переменной
     * @param int|float|string $var Переменная на вывод.
     * Типы не проверяются и в случае невозможности вывода воникнет ошибка
     * @param bool $escape Флаг экранирования
     * @return string
     */
    protected function showVar($var, $escape)
    {
        return $escape ? htmlspecialchars($var) : $var;
    }


    /**
     * Вывод текущего шаблона
     * @param array $data Контекст шаблона
     */
    public static function display($data)
    {
        self::$data = $data;
    }
} 