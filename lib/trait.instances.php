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


/**
 * Трейт для работы с инстансами класса
 * @author    viktor
 * @version   1.0.0
 * @package   Micr0
 */
trait instances {

    # Статические свойства
    /** Список экземпляров класса */
    protected static $_instances = [];
    /** Индекс главного экземпляра класса в списке экземпляров */
    protected static $_mainInstanceIndex = null;

    # Закрытые данные
    /** Индекс экземпляра класса */
    protected $_instanceIndex  = null;






# -----------------------------------------------       Работа с инстансами      ------------------------------------------------------ #

    /**
     * Возвращает один экземпляр класса из списка классов - аналог метода getInstance()
     * @param string $instanceIndex,.. Индекс экземпляр класса в списке классов
     * @return mixed Инстанс с указанным индексом
     */
    public static function instance($instanceIndex = null){
        return $instanceIndex === null ? self::getMainInstance() : self::getInstance($instanceIndex);
    }

    /**
     * Получение списка экземпляров класса или одного его элемента
     * @param string $index,.. Индекс инстанса
     * @return mixed Инстанс указанной БД, или весь массив
     */
    public static function getInstance($index = null){
        return $index === null ? self::$_instances : (isset(self::$_instances[$index]) ? self::$_instances[$index] : null);
    }

    /**
     * Возвращает главный эземпляр класса из списка классов
     * @return mixed Главный инстанс класса
     */
    public static function getMainInstance(){
        return self::getInstance(self::mainInstanceIndex());
    }

    /**
     * Установка или получение индекса главного экземпляра класса
     * @param string $index Индекс инстанса
     * @return string Индекс главного инстанса класса, или true в случае успешной установки
     * @throws Exception
     */
    public static function mainInstanceIndex($index = null){
        if ($index === null){
            return self::$_mainInstanceIndex;
        }else {
            if (!(is_string($index) || is_numeric($index)) || !in_array($index, self::$_instances)) {
                throw new DbException(Db::E_WRONG_PARAMETERS);
            }
            self::$_mainInstanceIndex = $index;
            return true;
        }
    }

    /**
     * Очищение инстанса
     * @param string $index Индекс инстанса
     * @return true
     */
    public static function clearInstance($index){
        if ($index == self::mainInstanceIndex()){
            self::mainInstanceIndex(null);
        }
        unset(self::$_instances[$index]);
        return true;
    }

    /**
     * Устанавливает, или получает индекс объекта в списке экземпляров класса
     * @param string,.. $index Индекс инстанса
     * @return string Запрашиваемый индекс, или true в случае успешной установки этого индекса
     */
    public function instanceIndex($index = null){
        if (func_num_args() == 0){
            return $this->_instanceIndex;
        }else {
            self::$_instances[$index] = &$this;
            unset(self::$_instances[$this->_instanceIndex]);
            $this->_instanceIndex = $index;
            return true;
        }
    }

    /** Устанавливает данный экземпляр класса как главный */
    public function instanceSetMain(){
        self::$_mainInstanceIndex = $this->instanceIndex();
        return true;
    }


} 