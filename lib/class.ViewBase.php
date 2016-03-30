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

/** @todo Сложные переменные в тегах {% %} */



/** Собственное исключение класса и его потомков */
class ViewBaseException extends BaseException
{
    # Языковые константы класса
    const L_TPL_FILE_UNREACHABLE = 'Файл с шаблоном недоступен';
    const L_TPL_WRONG_VAR_NAME = 'Неизвестное имя переменной';
    const L_TPL_WRONG_VAR_INDEX = 'Неизвестное имя переменной';
}


/**
 * Абстрактный класс-предок для видов (Господи, слово-то какое непривычное...)
 * @author      viktor
 * @version     1.0
 * @package     Micr0
 */
abstract class ViewBase
{
    # Параметры класса
    /** @const Режим дебага шаблонов */
    const DEBUG = CONFIG::VIEW_DEBUG;
    /** @const Режим автоэкранирования */
    const AUTO_ESCAPE = CONFIG::VIEW_AUTO_ESCAPE;
    /** @const Расширение файлов шаблонов */
    const FILE_EXT = '.html';
    /** @const Папка для хранения шаблонов */
    const DIR = CONFIG::ROOT . DIRECTORY_SEPARATOR . CONFIG::VIEW_DIR . DIRECTORY_SEPARATOR;
    /** @const Папка для хранения кэша шаблонов */
    const DIR_RUNTIME = CONFIG::ROOT . DIRECTORY_SEPARATOR . CONFIG::RUNTIME_DIR .
    DIRECTORY_SEPARATOR . CONFIG::VIEW_DIR . DIRECTORY_SEPARATOR;


    # Параметры регулярных выражения
    # Переменные
    /**
     * @const Регулярное выражение простой переменной
     * {{ имя_переменной }}
     */
    const VAR_BEGIN = '{{';
    const VAR_END = '}}';
    const EXPR_VAR_BEGIN = '\{\{'; # Предыдущие 2 константы, экранированные для регулярных выражений
    const EXPR_VAR_END = '\}\}';

    const EXPR_VAR_NAME     = '\w+';
    const EXPR_VAR_MODIFIER = '(\|(?<modifier>raw|e))?';
    const EXPR_VAR_INDEX    = '(\w+|#(?![\w\.]))';
    const EXPR_VAR          = self::EXPR_VAR_NAME . '(\.' . self::EXPR_VAR_INDEX . ')*';


    # Блоки
    const BLOCK_BEGIN = '{%';
    const BLOCK_END = '%}';
    const EXPR_BLOCK_BEGIN = '\{%'; # Предыдущие 2 константы, экранированные для регулярных выражений
    const EXPR_BLOCK_END = '%\}';
    # if else
    /**
     * @const Регулярное выражение условного блока
     * {% if имя_блока %}
     */
    const EXPR_IF = 'if\s(?<block_name>' . self::EXPR_VAR . ')';

    /**
     * @const Регулярное выражение условного блока
     * {% else %} или {% else имя_блока %}
     */
    const EXPR_ELSE = 'else(\s\g<block_name>)?';

    /**
     * @const Регулярное выражение условного блока
     * {% endif %} или {% endif имя_блока %}
     */
    const EXPR_ENDIF = 'endif(\s\g<block_name>)?';


    # for a in b
    /**
     * @const Регулярное выражение блока-итератора
     * {% for имя_ряда in имя_блока %}
     */
    const EXPR_FOR = 'for\s(?<row_name>' . self::EXPR_VAR_NAME . ')\sin\s(?<block_name>' . self::EXPR_VAR . ')';

    /**
     * @const Регулярное выражение блока-итератора
     * {% endfor %} или {% endfor имя_блока %}
     */
    const EXPR_ENDFOR = 'endfor(\s\g<block_name>)?';



    /**
     * Выбор в тексте шаблона $tplString переменных
     * @param string $tplString Шаблон в строке
     * @return string
     */
    protected static function pregMatchStrings($tplString)
    {
        /**
         * Регулярное выражение для переменных
         * {{ var_name }} {{ var_name.index1.index2... }} {{ # }} {{ var_name|raw }} {{ var_name|e }}
         * var_name и index состоят из символов \w - буквы, цифры, подчёркивание
         *
         * /\{\{\s((?<var_name>self::EXPR_VAR)(self::EXPR_MODIFIER)?\s\}\}/msx
         * /\{\{\s(?<var_name>\w+(\.\w+)*)(\|(?<modifier>raw|e))?\s\}\}/msx
         */
        if (preg_match_all(
            '/' . self::EXPR_VAR_BEGIN . '\s(?<var_name>' . self::EXPR_VAR . ')' . self::EXPR_VAR_MODIFIER . '\s' . self::EXPR_VAR_END . '/ms',
            $tplString,
            $matches
        )) {
            return $matches;
        }
        return $tplString;
    }


    /**
     * Выбор в тексте шаблона $tplString условных блоков
     * @param string $tplString Шаблон в строке
     * @return string
     */
    protected static function pregMatchConditionals($tplString)
    {
        /**
         * Регулярное выражение для условных операторов if () {} else {}
         * {% if block_name %}...{% else %}...{% endif %}
         * или сокращённый вариант:
         * {% if block_name %}...             {% endif %}
         * block_name == self::EXPR_VAR
         *
         * /
         *      \{%\sif\s(?<block_name>\w+(\.\w+)*)\s%\}    # {% if block_name %}
         *          (?<block_true>.*?)                      # Блок true
         *      (
         *      \{%\selse\s(\g<block_name>\s)?%\}           # {% else %} или {% else block_name %}
         *          (?<block_false>.*?)                     # Блок false
         *      )?                                          # Отрицательного варианта может и не быть
         *      \{%\sendif\s(\g<block_name>\s)?%\}          # {% endif %} или {% endif block_name %}
         * /msx                                             # /i - РегистроНЕзависимый
         *                                                    /m - многострочный,
         *                                                    /s - \. включает в себя \n,
         *                                                    /x - неэкранированные пробелы и комментарии после # опускаются
         * Доступ к маске по номеру: \1, \g1 или \g{1}
         * Маска левее места вызова: \g{-2}
         * Именованная маска: (?P<name>...), (?'name'...), (?<name>...)
         * Вызов именованной маски: (?P=name), \k<name>, \k'name', \k{name}, \g{name}
         */
        if (preg_match_all(
            '/' .
            self::EXPR_BLOCK_BEGIN . '\s' . self::EXPR_IF . '\s' . self::EXPR_BLOCK_END .
            '(?<block_true>.*?)(' .
            self::EXPR_BLOCK_BEGIN . '\s' . self::EXPR_ELSE . '\s' . self::EXPR_BLOCK_END .
            '(?<block_false>.*?))?' .
            self::EXPR_BLOCK_BEGIN . '\s' . self::EXPR_ENDIF . '\s' . self::EXPR_BLOCK_END .
            '/ms',
            $tplString,
            $matches
        )) {
            return $matches;
        }
        return $tplString;
    }


    /**
     * Выбор в тексте шаблона $tplString повторяющихся блоков
     * @param string $tplString Шаблон в строке
     * @return string
     */
    protected static function pregMatchArrays($tplString)
    {
        /**
         * Регулярное выражение для повторяющихся блоков
         * {% for row_name in block_name %} ... {{ row_name.var1 }}, {{ row_name.var2 }} ... {% endfor %}
         * block_name и row_name состоят из символов \w - буквы, цифры, подчёркивание
         * /
         *      \{%\sfor\s(?<row_name>\w+)\sin\s(?<block_name>\w+(\.\w+)*)\s%\}     # {% for row_name in block_name %}
         *          (?<block>.*?)                                                   # Повторяющийся блок
         *      \{%\sendfor\s(\g<block_name>\s)?%\}                                 # {% endfor %} или {% endfor block_name %}
         * /msx
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
            return $matches;
        }
        return false;
    }


    /**
     * Чтение файла в директории шаблонов self::DIR
     * Если имя файла не оканчивается на расширение self::FILE_EXT, оно будет добавлено автоматически.
     * Сравнение регистрозависимое. По умоланию self::FILE_EXT == '.html'
     * @param string $filename
     * @return string
     * @throws ViewBaseException
     */
    public static function getFile($filename)
    {
        // Если имя файла не оканчивается ожидаемым расширением, добавляем его
        if (strlen($filename) < 6 || '.' . pathinfo($filename, PATHINFO_EXTENSION) != self::FILE_EXT) {
            $filename .= self::FILE_EXT;
        }
        if (!is_readable(self::DIR . $filename)) {
            throw new ViewBaseException(ViewBaseException::L_TPL_FILE_UNREACHABLE . ": '$filename'");
        }
        return file_get_contents(self::DIR . $filename);
    }



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
                throw new ViewBaseException(ViewBaseException::L_TPL_WRONG_VAR_INDEX . ": '$index' in " . var_export($data, true));
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
                    throw new ViewBaseException(ViewBaseException::L_TPL_WRONG_VAR_INDEX . ": '$varName' in " . var_export($data, true));
            }
        }
        return true;
    }
} 