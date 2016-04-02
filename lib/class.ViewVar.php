<?php
/**
 * Tpl vars class (PHP 5 >= 5.4.0)
 * Special thanks to: all, http://www.php.net
 * Copyright (c)    viktor Belgorod, 2016
 * Email            vinjoy@bk.ru
 * Version          1.0.0
 * Last modified    00:01 13.03.16
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the MIT License (MIT)
 * @see https://opensource.org/licenses/MIT
 */



/**
 * Абстрактный класс для доступа к переменным контекста шаблона по их мени (varName.varIndex1.varIndex2...)
 * @author    viktor
 * @version   1.0.0
 * @package   Micr0
 */
abstract class ViewVar
{
    # Параметры класса
    /** @const Режим дебага шаблонов */
    const DEBUG = CONFIG::VIEW_DEBUG;
    /** @const Режим автоэкранирования */
    const AUTO_ESCAPE = CONFIG::VIEW_AUTO_ESCAPE;
    /** @const Расширение файлов шаблонов */
    const FILE_EXT = '.html';
    /** @const Папка для хранения шаблонов */
    const DIR = null;



    /**
     * Парсинг одной переменной
     * @param mixed $data Контекст шаблона, ассоциативный массив.
     * @param string $varName Имя переменной с произвольным числом индексов
     * @param string $modifier Модификатор переменной
     * Элементы могут быть массивами, объектами, числами, строками, bool или null
     * @return mixed Значение элемента контекста шаблона,
     * если он простого типа и ссылка на него, если он массив, или объект
     * @throws ViewBaseException
     */
    public static function parseVar($data, $varName, $modifier = '')
    {
        // Получаем массив индексов из имени переменной
        $varParts = explode('.', $varName);
        $partsCount = count($varParts);
        if ($partsCount < 1) {
            throw new ViewBaseException(ViewBaseException::L_TPL_WRONG_VAR_NAME . ": '$varName'");
        }

        // Проходим по массиву индексов
        $result = self::getVarPart($data, $varParts[0]);
        for ($i = 1; $i < $partsCount; $i++) {
            $result = self::getVarPart($result, $varParts[$i]);
        }

        // Применяем модификатор, если он есть
        // raw - Отмена экранирования html
        // e - Экранирование html
        if ($modifier !== 'raw' && (self::AUTO_ESCAPE || $modifier === 'e')) {
            $result = htmlspecialchars($result);
        }

        return $result;
    }



    /**
     * Получение элемента $index из переменной $base в зависимости от его типа
     * @param array $data$data
     * @param string $index
     * @return mixed
     * @throws ViewBaseException
     */
    protected static function getVarPart($data, $index)
    {
        switch (gettype($data)) {
            case 'array':
                return $data[$index];

            case 'object':
                return $data->$index;

            default:
                throw new ViewBaseException(
                    ViewBaseException::L_TPL_WRONG_VAR_INDEX . ": '$index' in " . var_export($data, true)
                );
        }
    }



    /**
     * Проверка наличия переменной в контексте
     * @param mixed $data Контекст шаблона
     * @param string $varName Полное имя переменной (var.index.index2.index3.#)
     * @return bool
     * @throws ViewBaseException
     */
    public static function hasVar($data, $varName)
    {
        $varName = explode('.', $varName);
        $base = &$data;
        for ($i = 0; $i < count($varName); $i++) {
            switch (gettype($base)) {
                case 'array':
                    if (!array_key_exists($varName[$i], $base)) {
                        return false;
                    }
                    $base = &$base[$varName[$i]];
                    break;

                case 'object':
                    if (!property_exists($base, $varName[$i])) {
                        return false;
                    }
                    $propName = $varName[$i];
                    $base = &$base->$propName;
                    break;

                default:
                    throw new ViewBaseException(
                        ViewBaseException::L_TPL_WRONG_VAR_INDEX . ": '$varName' in " . var_export($data, true)
                    );
            }
        }
        return true;
    }
} 