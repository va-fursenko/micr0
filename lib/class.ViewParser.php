<?php
/**
 * Templates parser сlass (PHP 5 >= 5.6.0)
 * Special thanks to: all, http://www.php.net
 * Copyright (c)    viktor Belgorod, 2009-2016
 * Email            vinjoy@bk.ru
 * Version            2.5.0
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the MIT License (MIT)
 * @see https://opensource.org/licenses/MIT
 */


require_once(__DIR__ . DIRECTORY_SEPARATOR . 'class.Filter.php');
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'class.BaseException.php');


/** Собственное исключение класса */
class ViewParserException extends ViewBaseException
{
    # Языковые константы класса
    const L_TPL_BLOCK_UNKNOWN = 'Шаблон не найден';
}


/** @todo Класс View как объединение фукционала */


/**
 * Класс парсера шаблонов
 * @author      viktor
 * @version     2.5
 * @package     Micr0
 */
class ViewParser extends ViewBase
{
    /**
     * Замена в тексте шаблона $tplString строковых и числовых переменных данными из массива $dataItems
     * Выполняется с помощью str_replace, так что не поддерживает сложные переменные и модификаторы
     * @param string $tplString Шаблон в строке
     * @param array $data Ассоциативный массив с контекстом шаблона
     * @param string $prefix Префикс имён переменных, например 'row', добавляемый с точкой: {{ row.var_name }}
     * @return string
     */
    protected static function replaceStrings($tplString, $data, $prefix = '')
    {
        /**
         * str_replace('{{ var_name }}', "var_value", $tplString)
         * Вообще в классе имя_переменной ожидается из символов \w - буквы, цифры, подчёркивание,
         * но в данном методе для скорости используется str_replace, которая может заменить всё, что угодно
         */
        foreach ($data as $varName => $value) {
            if (is_string($value) || is_numeric($value)) {
                $tplString = str_replace(
                    self::VAR_BEGIN . ' ' . (strlen($prefix) > 0 ? $prefix . '.' : '') . $varName . ' ' . self::EXPR_VAR_END,
                    $value,
                    $tplString
                );
            }
        }
        return $tplString;
    }


    /**
     * Замена в тексте шаблона $tplString строковых и числовых переменных данными из массива $dataItems
     * @param string $tplString Шаблон в строке
     * @param array $data Ассоциативный массив с контекстом шаблона
     * @return string
     * @throws ViewParserException
     */
    protected static function parseStrings($tplString, $data)
    {
        // Получаем результат выполнения регулярного выражения поиска переменных
        if ($matches = self::pregMatchStrings($tplString)) {

            // Проходим по всем найденным переменным
            foreach ($matches['var_name'] as $varIndex => $varName) {

                // Если переменной с таким именем нет в контексте, пропускаем итерацию
                if (!self::hasVar($data, $varName)) {
                    continue;
                }

                // Из строкового имени переменной получаем её имя
                $replacement = self::parseVar($data, $varName, $matches['modifier'][$varIndex]);

                // Меняем выбранную переменную
                $tplString = str_replace($matches[0][$varIndex], $replacement, $tplString);
            }
        }

        return $tplString;
    }


    /**
     * Замена в тексте шаблона $tplString условных блоков данными из массива $dataItems
     * Флаг проверяется как bool
     * @param string $tplString Шаблон в строке
     * @param array $data Ассоциативный массив с контекстом шаблона
     * @return string
     */
    protected static function parseConditionals($tplString, $data)
    {
        // Получаем результат выполнения регулярного выражения поиска условных блоков
        if ($matches = self::pregMatchConditionals($tplString)) {
            // Проходим по всем найденным блокам
            foreach ($matches[0] as $blockIndex => $blockDeclaration) {
                $varName = $matches['block_name'][$blockIndex];

                // Если искомой переменной в параметрах шаблона нет, пропускам итерацию
                if (!self::hasVar($data, $varName)) {
                    continue;
                }

                // Положительный вариант
                if (self::parseVar($data, $varName)) {
                    $tplString = str_replace($blockDeclaration, trim($matches['block_true'][$blockIndex]), $tplString);

                // Отрицательный вариант, или отсутствие блока
                } else {
                    $tplString = str_replace($blockDeclaration, trim($matches['block_false'][$blockIndex]), $tplString);

                }
            }
        }
        return $tplString;
    }


    /**
     * Замена в тексте шаблона $tplString повторяющихся блоков данными из массива $dataItems
     * @param string $tplString Шаблон в строке
     * @param array $data Ассоциативный массив с контекстом шаблона
     * @return string
     * @throws ViewParserException
     */
    protected static function parseArrays($tplString, $data)
    {
        // Получаем результат выполнения регулярного выражения поиска повторяющихся блоков
        if ($matches = self::pregMatchArrays($tplString)) {
            // Проходим по всем найденным блокам
            foreach ($matches[0] as $blockIndex => $blockDeclaration) {
                $blockName = $matches['block_name'][$blockIndex];
                $rowName = $matches['row_name'][$blockIndex];

                // Если переменной с таким именем нет в контексте, пропускаем итерацию
                if (!self::hasVar($data, $blockName)) {
                    continue;
                }

                $rows = self::parseVar($data, $blockName);
                // Если вместо массива передано что-то другое, стоит или пропустить итерацию, или бросить исключение
                if (!is_array($rows)) {
                    throw new ViewParserException(ViewParserException::L_WRONG_PARAMETERS);
                }
                // Если массив входных параметров для данного блока пустой, удаляем блок из шаблона и переходим к следующей итерации
                if (count($rows) == 0) {
                    $tplString = str_replace($blockDeclaration, '', $tplString);
                    continue;
                }

                $blocks = '';
                $blockHTML = trim($matches['block'][$blockIndex]);

                /*
                 * Найдём все внутренние переменные блока и переиндексируем входные данные
                 * именами найденных переменных, чтобы не обязательно было передавать на вход ассоциативный массив
                 * {{ row_name.var1..X }}
                 */
                if (preg_match_all(
                    '/' . self::EXPR_VAR_BEGIN . '\s' .
                    $rowName . '\.(?<var_name>' . self::EXPR_VAR_INDEX . ')' . self::EXPR_VAR_MODIFIER.
                    '\s' . self::EXPR_VAR_END . '/ms',
                    $blockHTML,
                    $blockVars
                )) {
                    // Проходим по всем найденным в блоке переменным
                    // Флаг пропущенных служебных переменных. Мы считаем по порядку только пользовательские
                    $varsOmitted = 0;
                    foreach ($blockVars['var_name'] as $varIndex => $varName) {
                        // Пока пропускается только одна переменная
                        if ($varName === '#') {
                            $varsOmitted++;
                            continue;
                        }
                        /*
                         * Проходим по всем рядам входных данных и если нужного индекса в ряде нет,
                         * но есть переменная с таким же порядковым номером,
                         * то добавляем индекс со ссылкой на неё: $var[$row]['user_name'] = &$var[$row][0]
                         */
                        foreach ($rows as $rowIndex => &$dataRow) {
                            if (!isset($dataRow[$varName]) && isset($dataRow[$varIndex - $varsOmitted])) {
                                $dataRow[$varName] = &$dataRow[$varIndex - $varsOmitted];
                            }
                        }
                    }
                }

                /*
                 * Парсим блок для каждого ряда массива $dataItems[$blockName]
                 * Если в блоке присутствует автосчётчик, инициализируем его
                 */
                if (strpos($blockHTML, self::VAR_BEGIN . " $rowName.# " . self::VAR_END) !== false) {
                    $counter = 0;    // Инициализуем порядковый счётчик
                } else {
                    $counter = null; // Указываем на то, что он не используется
                }

                // Заполняем блок переменными и прибавляем к представлению
                foreach ($rows as &$rowItems) {
                    // Вообще ожидается, что имя пользовательской переменной во входных данных
                    // не может содержать знак '#', но это не проверяется. В любом случае, затираем
                    if (isset($counter)) {
                        $rowItems['#'] = $counter + 1;
                    }
                    $blocks .= self::parseStrings(
                        $blockHTML,
                        [
                            $rowName => $rowItems
                        ]
                    );
                    $counter++;
                }

                // Заменяем объявление блока в тексте шаблона на полученное представление
                $tplString = str_replace($blockDeclaration, $blocks, $tplString);
            }
        }
        return $tplString;
    }


    /**
     * Заполнение текстового шаблона данными из массива
     * @param string $tplString Шаблон в строке
     * @param array $data Ассоциативный массив с контекстом шаблона
     * @return string
     */
    protected static function parseString($tplString, $data)
    {
        // Сначала заменяем все строковые переменные, потому что они могут участвовать в других выражениях
        $tplString = self::parseStrings($tplString, $data);
        // Далее обрабатываем условные блоки
        $tplString = self::parseConditionals($tplString, $data);
        // В самом конце обрабатываем повторяющиеся блоки
        $tplString = self::parseArrays($tplString, $data);
        return $tplString;
    }


    /**
     * Заполнение контейнера, заданного именем секции
     * @param string $containerName Имя блока шаблона
     * @param array $data Массив с полями шаблона
     * @return string
     */
    public static function parseBlock($containerName, $data)
    {
        return self::parseString(
            self::getFile($containerName),
            $data
        );
    }


    /**
     * Обработка целого файла или одного блока в нём
     * @param string $filename Имя файла для парсинга
     * @param array $data Массив с  шаблона
     * @param string $blockName Имя блока
     * @return string
     * @throws ViewParserException
     */
    public static function parseFile($filename, $data, $blockName = '')
    {
        return self::parseString(
            $blockName ?
                Filter::strBetween(self::getFile($filename), '[[$' . $blockName . ']]', '[[/$' . $blockName . ']]')
                : self::getFile($filename),
            $data
        );
    }
}