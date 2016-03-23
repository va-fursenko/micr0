<?php
/**
 * Templates parser сlass (PHP 5 >= 5.6.0)
 * Special thanks to: all, http://www.php.net
 * Copyright (c)    viktor Belgorod, 2009-2016
 * Email		    vinjoy@bk.ru
 * Version		    2.5.0
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the MIT License (MIT)
 * @see https://opensource.org/licenses/MIT
 */


require_once(__DIR__ . DIRECTORY_SEPARATOR . 'class.Filter.php');
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'class.BaseException.php');



/** Собственное исключение класса */
class ViewParserException extends BaseException
{
    # Языковые константы класса
    const L_TPL_FILE_UNREACHABLE = 'Файл с шаблоном недоступен';
    const L_TPL_DB_UNREACHABLE   = 'База данных с темплейтами недоступна';
    const L_TPL_BLOCK_UNKNOWN    = 'Шаблон не найден';
}


/** @todo Класс View как объединение фукционала */
/** @todo Блоки с множественными альтернативами (switch) */
/** @todo Подумать насчёт того, чтобы скопировать синтаксис блоков с твига */




/**
 * Класс парсера шаблонов
 * @author      viktor
 * @version     2.5
 * @package     Micr0
 */
class ViewParser
{
    # Собственные константы
    /** @const Режим дебага шаблонов */
    const DEBUG = CONFIG::VIEW_DEBUG;
    /** @const Расширение файлов шаблонов */
    const FILE_EXT = '.html';
    /** @const Папка для хранения шаблонов */
    const DIR = CONFIG::ROOT . DIRECTORY_SEPARATOR . CONFIG::VIEW_DIR . DIRECTORY_SEPARATOR;



    # Параметры регулярных выражения
    # Переменные
    /**
     * @const Регулярное выражение простой переменной
     * {{ имя_переменной }}
     */
    const EXPR_VAR_BEGIN = '\{\{';
    const EXPR_VAR_END   = '\}\}';
    const EXPR_VAR = '(?<var_name>\w+|#)';


    # Блоки
    const EXPR_BLOCK_BEGIN = '\{%';
    const EXPR_BLOCK_END   = '%\}';
    # if else
    /**
     * @const Регулярное выражение условного блока
     * {% if имя_блока %}
     */
    const EXPR_IF =     'if\s(?<block_name>\w+)';

    /**
     * @const Регулярное выражение условного блока
     * {% else %} или {% else имя_блока %}
     */
    const EXPR_ELSE =   'else(\s\g<block_name>)?';

    /**
     * @const Регулярное выражение условного блока
     * {% endif %} или {% endif имя_блока %}
     */
    const EXPR_ENDIF =  'endif(\s\g<block_name>)?';



    # for a in b
    /**
     * @const Регулярное выражение блока-итератора
     * {% for имя_ряда in имя_блока %}
     */
    const EXPR_FOR =    'for\s(?<row_name>\w+)\sin\s(?<block_name>\w+)';

    /**
     * @const Регулярное выражение блока-итератора
     * {% endfor %} или {% endfor имя_блока %}
     */
    const EXPR_ENDFOR = 'endfor(\s\g<block_name>)?';






    /**
     * Замена в тексте шаблона $tplString строковых и числовых переменных данными из массива $dataItems
     * @param string $tplString Шаблон в строке
     * @param array  $dataItems Ассоциативный массив с контекстом шаблона
     * @param string $prefix Префикс имён переменных, например 'row', добавляемый с точкой: {{ row.var_name }}
     * @return string
     */
    protected static function parseStrings($tplString, $dataItems, $prefix = '')
    {
        /**
         * str_replace('{{ имя_переменной }}', $dataItems['имя_переменной'], $tplString)
         * Вообще в классе имя_переменной ожидается из символов \w - буквы, цифры, подчёркивание,
         * но в данном методе для скорости используется str_replace, которая может заменить всё, что угодно
         */
        foreach ($dataItems as $varName => $value) {
            if (is_string($value) || is_numeric($value)) {
                $tplString = str_replace('{{ ' . (strlen($prefix) > 0 ? $prefix . '.' : '') . $varName . ' }}', $value, $tplString);
            }
        }
        return $tplString;
    }



    /**
     * Замена в тексте шаблона $tplString условных блоков данными из массива $dataItems
     * Флаг проверяется
     * @param string $tplString Шаблон в строке
     * @param array  $dataItems Ассоциативный массив с контекстом шаблона
     * @return string
     */
    protected static function parseConditionals($tplString, $dataItems)
    {
        /**
         * Регулярное выражение для условных операторов if () {} else {}
         * {% if block_name %}...{% else %}...{% endif %}
         * или сокращённый вариант:
         * {% if block_name %}...             {% endif %}
         * имя_блока состоит из символов \w - буквы, цифры, подчёркивание

        /
            \{%\sif\s(?<block_name>\w+)\s%\}        # {% if block_name %}
                (?<block_true>.*?)                  # Контент для положительного варианта
            (?<has_false>                           # Если данный блок пуст, значит второй части шаблона нет
            \{%\selse\s(\g<block_name>\s)?%\}       # {% else %} или {% else block_name %}
                (?<block_false>.*?)                 # Контент для отрицательного варианта
            )?                                      # Отрицательного варианта может и не быть
            \{%\sendif\s(\g<block_name>\s)?%\}      # {% endif %} или {% endif block_name %}
        /msx                                        # /i - РегистроНЕзависимый
                                                      /m - многострочный,
                                                      /s - \. включает в себя \n,
                                                      /x - неэкранированные пробелы и комментарии после # опускаются

         * Доступ к маске по номеру: \1, \g1 или \g{1}
         * Маска левее места вызова: \g{-2}
         * Именованная маска: (?P<name>...), (?'name'...), (?<name>...)
         * Вызов именованной маски: (?P=name), \k<name>, \k'name', \k{name}, \g{name}
         */
        if (preg_match_all(
            '/' .
                self::EXPR_BLOCK_BEGIN . '\s' . self::EXPR_IF . '\s' . self::EXPR_BLOCK_END .
                    '(?<block_true>.*?)(?<has_false>' .
                self::EXPR_BLOCK_BEGIN . '\s' . self::EXPR_ELSE . '\s' . self::EXPR_BLOCK_END .
                    '(?<block_false>.*?))?' .
                self::EXPR_BLOCK_BEGIN . '\s' . self::EXPR_ENDIF . '\s' . self::EXPR_BLOCK_END .
            '/ms',
            $tplString,
            $matches
        )) {
            // Проходим по всем найденным блокам
            foreach ($matches[0] as $blockIndex => $blockDeclaration) {
                // Если искомой переменной в параметрах шаблона нет, пропускам итерацию
                if (!array_key_exists($matches['block_name'][$blockIndex], $dataItems)) {
                    continue;
                }

                // Положительный вариант
                if ($dataItems[$matches['block_name'][$blockIndex]]) {
                    $tplString = str_replace($blockDeclaration, trim($matches['block_true'][$blockIndex]), $tplString);

                // В случае отрицательного варианта проверяем существование подблока для него
                } elseif (strlen($matches['has_false'][$blockIndex]) > 0) {
                    $tplString = str_replace($blockDeclaration, trim($matches['block_false'][$blockIndex]), $tplString);

                // Если положительное условие не выполнено, а подблока для отрицательного нет, удаляем весь блок
                } else {
                    $tplString = str_replace($blockDeclaration, '', $tplString);
                }
            }
        }
        return $tplString;
    }



    /**
     * Замена в тексте шаблона &$tplString повторяющихся блоков данными из массива $dataItems
     * @param string $tplString Шаблон в строке
     * @param array  $dataItems Ассоциативный массив с контекстом шаблона
     * @return string
     * @throws ViewParserException
     */
    protected static function parseArrays($tplString, $dataItems)
    {
        /**
         * Регулярное выражение для повторяющихся блоков
         * {% for row_name in block_name %} ... {{ row_name.var1 }}, {{ row_name.var2 }} ... {% endfor %}
         * имя_блока состоит из символов \w - буквы, цифры, подчёркивание

        /
            \{%\sfor\s(?<row_name>\w+)\sin\s(?<block_name>\w+)\s%\}     # {% for row_name in block_name %}
                (?<block>.*?)                                           # Контент повторяющегося блока
            \{%\sendfor\s(\g<block_name>\s)?%\}                         # {% endfor %} или {% endfor block_name %}
        /msx                                                            # /i - РегистроНЕзависимый
                                                                          /m - многострочный,
                                                                          /s - \. включает в себя \n,
                                                                          /x - неэкранированные пробелы и комментарии после # опускаются

         * На всякий случай,
         * Доступ к маске по номеру: \1, \g1 или \g{1}
         * Маска левее места вызова: \g{-2}
         * Именованная маска: (?P<name>...), (?'name'...), (?<name>...)
         * Вызов именованной маски: (?P=name), \k<name>, \k'name', \k{name}, \g{name}
         */
        if (preg_match_all(
            '/' .
                self::EXPR_BLOCK_BEGIN . '\s' . self::EXPR_FOR . '\s' . self::EXPR_BLOCK_END .
                    '(?<block>.*?)' .
                self::EXPR_BLOCK_BEGIN . '\s' . self::EXPR_ENDFOR . '\s' . self::EXPR_BLOCK_END .
            '/ms',
            $tplString,
            $matches
        )) {
            // Проходим по всем найденным блокам
            foreach ($matches[0] as $blockIndex => $blockDeclaration) {
                $blockName = $matches['block_name'][$blockIndex];
                // Если искомой переменной в параметрах шаблона нет, пропускам итерацию
                if (!array_key_exists($blockName, $dataItems)) {
                    continue;
                }
                // Если вместо массива передано что-то другое, стоит или пропустить итерацию, или бросить исключение
                if (!is_array($dataItems[$blockName])) {
                    throw new ViewParserException(ViewParserException::L_WRONG_PARAMETERS);
                }
                // Если массив входных параметров для данного блока пустой, удаляем блок из шаблона и переходим к следующей итерации
                if (count($dataItems[$blockName]) == 0) {
                    $tplString = str_replace($blockDeclaration, '', $tplString);
                    continue;
                }

                $blocks = '';
                $blockHTML = trim($matches['block'][$blockIndex]);

                /*
                 * Найдём все внутренние переменные блока и переиндексируем входные данные
                 * именами найденных переменных, чтобы не обязательно было передавать на вход ассоциативный массив
                 * {{ row_name.row_var_name }}
                 */
                if (preg_match_all(
                    '/' . self::EXPR_VAR_BEGIN . '\s' .
                        $matches['row_name'][$blockIndex] . '\.' . self::EXPR_VAR .
                    '\s' .self::EXPR_VAR_END . '/ms',
                    $blockHTML,
                    $blockVars
                )) {
                    // Проходим по всем найденным в блоке переменным
                    $varsOmitted = 0; // Флаг пропущенных служебных переменных. Мы считаем по порядку только пользовательские
                    foreach ($blockVars['var_name'] as $varIndex => $varName) {
                        if ($varName == '#') {
                            $varsOmitted++;
                            continue;
                        }
                        // Проходим по всем рядам входных данных и если нужного индекса в ряде нет,
                        // но есть переменная с таким же порядковым номером,
                        // то добавляем индекс со ссылкой на неё: $var[$row]['user_name'] = &$var[$row][$index]
                        foreach ($dataItems[$blockName] as $rowIndex => &$dataRow) {
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
                if (strpos(
                        $blockHTML,
                        stripslashes(self::EXPR_VAR_BEGIN . ' ' . $matches['row_name'][$blockIndex] . '.# ' . self::EXPR_VAR_END)
                    ) !== false
                ) {
                    $counter = 1;    // Инициализуем порядковый счётчик
                }else{
                    $counter = null; // Указываем на то, что он не используется
                }
                // Заполняем блок переменными и прибавляем к представлению
                foreach ($dataItems[$blockName] as $rowItems) {
                    // Вообще ожидается, что имя пользовательской переменной во входных данных
                    // не может содержать знак '#', но это не проверяется
                    if (isset($counter)) {
                        $rowItems['#'] = $counter++;
                    }
                    $blocks .= self::parseStrings($blockHTML, $rowItems, $matches['row_name'][$blockIndex]);
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
     * @param array  $dataItems Ассоциативный массив с контекстом шаблона
     * @return string
     */
    protected static function parseString($tplString, $dataItems)
    {
        // Сначала заменяем все строковые переменные, потому что они могут участвовать в других выражениях
        $tplString = self::parseStrings($tplString, $dataItems);
        // Далее обрабатываем условные блоки
        $tplString = self::parseConditionals($tplString, $dataItems);
        // В самом конце обрабатываем повторяющиеся блоки
        $tplString = self::parseArrays($tplString, $dataItems);
        return $tplString;
    }



    /**
     * Заполнение контейнера, заданного именем секции
     * @param string $containerName Имя блока шаблона
     * @param array  $dataItems Массив с полями шаблона
     * @return string
     */
    public static function parseBlock($containerName, $dataItems)
    {
        return self::parseString(
            self::getFile($containerName),
            $dataItems
        );
    }



    /**
     * Обработка целого файла или одного блока в нём
     * @param string $filename  Имя файла для парсинга
     * @param array  $dataItems Массив с  шаблона
     * @param string $blockName Имя блока
     * @return string
     * @throws ViewParserException
     */
    public static function parseFile($filename, $dataItems, $blockName = '')
    {
        return self::parseString(
            $blockName ?
                  Filter::strBetween(self::getFile($filename), '[[$' . $blockName . ']]', '[[/$' . $blockName . ']]')
                : self::getFile($filename),
            $dataItems
        );
    }



    /**
     * Чтение файла в директории шаблонов self::DIR
     * Если имя файла не оканчивается на расширение self::FILE_EXT, оно будет добавлено автоматически.
     * Сравнение регистрозависимое. По умоланию self::FILE_EXT == '.html'
     * @param string $filename
     * @return string
     * @throws ViewParserException
     */
    public static function getFile($filename)
    {
        // Если имя файла не оканчивается ожидаемым расширением, добавляем его
        if (strlen($filename) < 6 || pathinfo($filename, PATHINFO_EXTENSION) != self::FILE_EXT) {
            $filename .= self::FILE_EXT;
        }
        if (!is_readable(self::DIR . $filename)) {
            throw new ViewParserException(ViewParserException::L_TPL_FILE_UNREACHABLE . ': ' . $filename, E_USER_WARNING);
        }
        return file_get_contents(self::DIR . $filename);
    }
}