<?php
/**
 * Templates explorer сlass (PHP 5 >= 5.6.0)
 * Special thanks to: all, http://www.php.net
 * Copyright (c)    viktor Belgorod, 2009-2016
 * Email		    vinjoy@bk.ru
 * Version		    2.4.0
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the MIT License (MIT)
 * @see https://opensource.org/licenses/MIT
 */


require_once(__DIR__ . DIRECTORY_SEPARATOR . 'class.Db.php');
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'class.Filter.php');
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'class.BaseException.php');



/** Собственное исключение класса */
class TplException extends BaseException
{
    # Языковые константы класса
    const L_TPL_FILE_UNREACHABLE = 'Файл с шаблоном недоступен';
    const L_TPL_DB_UNREACHABLE   = 'База данных с темплейтами недоступна';
    const L_TPL_BLOCK_UNKNOWN    = 'Шаблон не найден';
}


/** @todo Добавить скрипт создания связанной таблицы БД */
/** @todo Закончить работу с БД */
/** @todo Добавить кеширование шаблонов */
/** @todo Транслячция шаблонов в PHP-код */
/** @todo Блоки с множественными альтернативами (switch) */




/**
 * Класс шаблонизатора
 * @author      viktor
 * @version     2.4
 * @package     Micr0
 */
class Tpl
{
    # Подключаем трейты
    use UseDb; # Статическое свойство и методы для работы с объектом Db



    # Собственные константы
    /** @const Режим дебага шаблонов */
    const DEBUG = CONFIG::TPL_DEBUG;
    /** @const Использование БД вместо файлов для работы с шаблонами */
    const USE_DB = CONFIG::TPL_USE_DB;
    /** @const Таблица БД с шаблонами */
    const DB_TABLE = CONFIG::TPL_DB_TABLE;
    /** @const Расширение файлов шаблонов */
    const FILE_EXT = '.html';
    /** @const Папка для хранения шаблонов */
    const DIR = CONFIG::ROOT . DIRECTORY_SEPARATOR . CONFIG::TPL_DIR . DIRECTORY_SEPARATOR;






    /**
     * Замена в тексте шаблона $tplString строковых и числовых переменных данными из массива $dataItems
     * @param string $tplString Шаблон в строке
     * @param array  $dataItems Ассоциативный массив с контекстом шаблона
     * @return string
     */
    protected static function _parseStrings($tplString, $dataItems)
    {
        /**
         * str_replace('{{имя_переменной}}', $dataItems['имя_переменной'], $tplString)
         * Вообще в классе имя_переменной ожидается из символов \w - буквы, цифры, подчёркивание,
         * но в данном методе для скорости используется str_replace, которая может заменить всё, что угодно
         */
        foreach ($dataItems as $varName => $value) {
            if (is_string($value) || is_numeric($value)) {
                $tplString = str_replace('{{' . $varName . '}}', $value, $tplString);
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
    protected static function _parseConditionals($tplString, $dataItems)
    {
        /**
         * Регулярное выражение для условных операторов if () {} else {}
         * {{?имя_блока}}...{{!имя_блока}}...{{;имя_блока}}
         * или сокращённый вариант:
         * {{?имя_блока}}...                 {{;имя_блока}}
         * имя_блока состоит из символов \w - буквы, цифры, подчёркивание

        /
            \{\{\?(?<block_name>\w+)\}\} # {{?имя_блока}}
                (?<block_true>.*?)       # Контент для положительного варианта
            (?<has_false>                # Если данный блок пуст, значит второй части шаблона нет
            \{\{\!\g<block_name>\}\}     # {{!имя_блока}}
                (?<block_false>.*?)       # Контент для отрицательного варианта
            )?                           # 0 или 1
            \{\{\;\g<block_name>\}\}     # {{;имя_блока}}
        /msx                             # /i - РегистроНЕзависимый
                                           /m - многострочный,
                                           /s - \. включает в себя \n,
                                           /x - неэкранированные пробелы и комментарии после # опускаются

         * Доступ к маске по номеру: \1, \g1 или \g{1}
         * Маска левее места вызова: \g{-2}
         * Именованная маска: (?P<name>...), (?'name'...), (?<name>...)
         * Вызов именованной маски: (?P=name), \k<name>, \k'name', \k{name}, \g{name}
         */
        if (preg_match_all(
            '/\{\{\?(?<block_name>\w+)\}\}(?<block_true>.*?)(?<has_false>\{\{\!\g<block_name>\}\}(?<block_false>.*?))?\{\{\;\g<block_name>\}\}/ms',
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
                    $tplString = str_replace($blockDeclaration, $matches['block_true'][$blockIndex], $tplString);

                // В случае отрицательного варианта проверяем существование подблока для него
                } elseif (strlen($matches['has_false'][$blockIndex]) > 0) {
                    $tplString = str_replace($blockDeclaration, $matches['block_false'][$blockIndex], $tplString);

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
     * @throws TplException
     */
    protected static function _parseArrays($tplString, $dataItems)
    {
        /**
         * Регулярное выражение для повторяющихся блоков
         * {{[имя_блока]}} ... {{переменная_1}}, {{переменная_2}} ... {{;имя_блока}}
         * имя_блока состоит из символов \w - буквы, цифры, подчёркивание

        /
            \{\{\[(?<block_name>\w+)\]\}\}  # {{[имя_блока]}}
                (?<block>.*?)               # Контент повторяющегося блока
            \{\{\;\g<block_name>\}\}        # {{;имя_блока}}
        /msx                                # /i - РегистроНЕзависимый
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
            '/\{\{\[(?<block_name>\w+)\]\}\}(?<block>.*?)\{\{\;\g<block_name>\}\}/ms',
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
                    throw new TplException(TplException::L_WRONG_PARAMETERS);
                }
                // Если массив входных параметров для данного блока пустой, удаляем блок из шаблона и переходим к следующей итерации
                if (count($dataItems[$blockName]) == 0) {
                    $tplString = str_replace($blockDeclaration, '', $tplString);
                    continue;
                }

                $blocks = '';
                $blockHTML = trim($matches['block'][$blockIndex]);

                // Найдём все переменные блока и переиндексируем входные данные именами найденных переменных,
                // чтобы не обязательно было передавать на вход ассоциативный массив
                if (preg_match_all('/\{\{(?<var_name>\w+)\}\}/ms', $blockHTML, $blockVars)) {
                    // Проходим по всем найденным в блоке переменным
                    foreach ($blockVars['var_name'] as $varIndex => $varName) {
                        // Проходим по всем рядам входных данных и если нужного индекса в ряде нет,
                        // но есть переменная с таким же порядковым номером,
                        // то добавляем индекс со ссылкой на неё: $var[$row]['user_name'] = &$var[$row][$index]
                        foreach ($dataItems[$blockName] as $rowIndex => &$dataRow) {
                            if (!isset($dataRow[$varName]) && isset($dataRow[$varIndex])) {
                                $dataRow[$varName] = &$dataRow[$varIndex];
                            }
                        }
                    }
                }

                // Парсим блок для каждого ряда массива $dataItems[$blockName]
                // Если в блоке присутствует автосчётчик, инициализируем его
                if (strpos($blockHTML, '{{#number}}') !== false) {
                    $counter = 1; // Инициализуем порядковый счётчик
                }
                // Заполняем блок переменными и прибавляем к представлению
                foreach ($dataItems[$blockName] as $rowItems) {
                    // Вообще ожидается, что имя пользовательской переменной во входных данных
                    // не может содержать знак '#', но это не проверяется
                    if (isset($counter)) {
                        $rowItems['#number'] = $counter++;
                    }
                    $blocks .= self::_parseStrings($blockHTML, $rowItems);
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
    protected static function _parseString($tplString, $dataItems)
    {
        // Сначала заменяем все строковые переменные, потому что они могут участвовать в других выражениях
        $tplString = self::_parseStrings($tplString, $dataItems);
        // Далее обрабатываем условные блоки
        $tplString = self::_parseConditionals($tplString, $dataItems);
        // В самом конце обрабатываем повторяющиеся блоки
        $tplString = self::_parseArrays($tplString, $dataItems);
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
        return self::_parseString(
            self::getBlock($containerName),
            $dataItems
        );
    }



    /**
     * Обработка целого файла или одного блока в нём
     * @param string $filename  Имя файла для парсинга
     * @param array  $dataItems Массив с  шаблона
     * @param string $blockName Имя блока
     * @return string
     * @throws TplException
     */
    public static function parseFile($filename, $dataItems, $blockName = '')
    {
        return self::_parseString(
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
     * @throws TplException
     */
    public static function getFile($filename)
    {
        // Если имя файла не оканчивается ожидаемым расширением, добавляем его
        if (strlen($filename) < 6 || pathinfo($filename, PATHINFO_EXTENSION) != self::FILE_EXT) {
            $filename .= self::FILE_EXT;
        }
        if (!is_readable(self::DIR . $filename)) {
            throw new TplException(TplException::L_TPL_FILE_UNREACHABLE . ': ' . $filename, E_USER_WARNING);
        }
        return file_get_contents(self::DIR . $filename);
    }



    /**
     * Получение из файла или БД заданного блока шаблона
     * @param string $name Имя блока шаблона
     * @return string
     * @throws TplException
     */
    public function getBlock($name)
    {
        if (self::USE_DB) {
            $result = self::db()->scalarQuery(
                "SELECT `body` FROM `" . self::DB_TABLE . "` WHERE `name` = '" . Filter::slashesAdd($name) . "' LIMIT 1",
                ''
            );
            if ($result === false) {
                throw new TplException(TplException::L_TPL_BLOCK_UNKNOWN . ': ' . $name, E_USER_WARNING);
            }
        } else {
            $result = self::getFile($name);
        }
        return $result;
    }
}