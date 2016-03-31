<?php
/**
 * Instances trait (PHP 5 >= 5.4.0)
 * Special thanks to: all, http://www.php.net
 * Copyright (c)    viktor Belgorod, 2016
 * Email            vinjoy@bk.ru
 * Version          1.0.0
 * Last modified    23:22 17.02.16
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the MIT License (MIT)
 * @see https://opensource.org/licenses/MIT
 */

require_once(__DIR__ . DIRECTORY_SEPARATOR . 'class.BaseException.php');

/** Класс исключения для методов трейта */
class InstancesException extends BaseException
{
    # Языковые константы
    /** @const Неизвестный инстанс */
    const L_UNKNOWN_INSTANCE = 'Неизвестный инстанс класса';
}


/**
 * Трейт для работы с инстансами класса
 * @author    viktor
 * @version   1.0.0
 * @package   Micr0
 */
trait Instances
{
    # Статические свойства
    /** Список экземпляров класса */
    protected static $instances = [];

    # Закрытые данные
    /** Индекс экземпляра класса */
    protected $instanceIndex = null;


    /**
     * Установка, или получение индекса инстанса для объекта
     * @param string $index Новый индекс
     * @return string|true
     * @throws InstancesException
     */
    public function instanceIndex($index = null)
    {
        if (func_num_args() == 0) {
            return $this->instanceIndex;

        } else {
            if ($index === null || $index === '') {
                $index = strval(count(self::$instances));
            } elseif (!self::isValidInstanceIndex($index)) {
                throw new InstancesException(InstancesException::L_WRONG_PARAMETERS . ": '$index'");

            }
            if ($index !== $this->instanceIndex) {
                if ($this->instanceIndex !== null) {
                    self::clearInstance($this->instanceIndex);
                }
                self::$instances[$index] = &$this;
                $this->instanceIndex = $index;
            }
        }
        return true;
    }


    /**
     * Получение указанного экземпляра класса
     * @param string $index Индекс инстанса
     * @return mixed Инстанс указанного объекта
     * @throws InstancesException
     */
    public static function getInstance($index)
    {
        if (!isset(self::$instances[$index])) {
            throw new InstancesException(InstancesException::L_UNKNOWN_INSTANCE . __CLASS__ . ": '$index'");
        }
        return self::$instances[$index];
    }


    /**
     * Очищение инстанса
     * @param string $index Индекс инстанса
     * @return true
     */
    public static function clearInstance($index)
    {
        unset(self::$instances[$index]);
        return true;
    }


    /**
     * Проверка индекса инстанса на валидность
     * @param string $index
     * @return bool
     */
    protected static function isValidInstanceIndex($index)
    {
        return (is_string($index) && strlen($index) < 33) || is_int($index); // Лично я хочу видеть в индексах только строки до 33 символов, или целые числа
    }
} 
