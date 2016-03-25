<?php
/**
 * Created by PhpStorm.
 * User: Виктор
 * Date: 23.03.2016
 * Time: 0:22
 */


/** Собственное исключение класса */
class BaseTemplateException extends BaseException
{
}


/**
 * Базовый класс для транслированных в PHP шаблонов
 * @author      viktor
 * @package     Micr0
 * @version     1.0.0
 */
abstract class BaseTemplate
{
    /** @property array $data Ассоциативный массив с контекстом шаблона */
    protected static $data = [];


    /**
     * Возвращение заполненного данными шаблона
     * @return string
     */
    public static function show()
    {
        return '';
    }


    /**
     * Получение одного поля контекста шаблона
     * @param string $varName Имя поля
     * @return mixed
     */
    public static function getVar($varName)
    {
        return array_key_exists($varName, self::$data) ? self::$data[$varName] : '{{ ' . $varName . ' }}';
    }
} 