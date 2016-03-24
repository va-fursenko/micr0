<?php

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
    const VAR_END   = '}}';
    const EXPR_VAR_BEGIN = '\{\{'; # Предыдущие 2 константы, экранированные для регулярных выражений
    const EXPR_VAR_END   = '\}\}';
    const EXPR_VAR_MODIFIER = '(\|(?<modifier>raw|e))?';
    const EXPR_VAR_INDEX = '(\.(?<var_index>\w+|#))?';
    const EXPR_VAR = '(?<var_name>\w+)' . self::EXPR_VAR_INDEX . self::EXPR_VAR_MODIFIER; // Пока из модификаторов поддерживается только raw - неэкранированный вывод


    # Блоки
    const BLOCK_BEGIN = '{%';
    const BLOCK_END   = '%}';
    const EXPR_BLOCK_BEGIN = '\{%'; # Предыдущие 2 константы, экранированные для регулярных выражений
    const EXPR_BLOCK_END   = '%\}';
    # if else
    /**
     * @const Регулярное выражение условного блока
     * {% if имя_блока %}
     */
    const EXPR_IF = 'if\s(?<block_name>\w+)';

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
    const EXPR_FOR = 'for\s(?<row_name>\w+)\sin\s(?<block_name>\w+)';

    /**
     * @const Регулярное выражение блока-итератора
     * {% endfor %} или {% endfor имя_блока %}
     */
    const EXPR_ENDFOR = 'endfor(\s\g<block_name>)?';

    /**
     * @const Регулярное выражение имени переменной в повторяющемся блоке
     * 'var_name' или '#'
     */
    const EXPR_VAR_FOR = '(?<var_name>\w+|#)';






    /**
     * Выбор в тексте шаблона $tplString переменных
     * @param string $tplString Шаблон в строке
     * @return string
     */
    protected static function pregMatchStrings($tplString)
    {
        /**
         * Регулярное выражение для переменных
         * {{ var_name }} {{ var_name.var_index }} {{ # }} {{ var_name|raw }} {{ var_name|e }}
         * var_name и var_index состоят из символов \w - буквы, цифры, подчёркивание

            /\{\{\s(?<var_name>\w+)(\.(?<var_index>\w+))?(\|(?<modifier>raw|e))?\s\}\}/msx

         */
        if (preg_match_all(
            '/' . self::EXPR_VAR_BEGIN . '\s' . self::EXPR_VAR . '\s' . self::EXPR_VAR_END . '/ms',
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
         * block_name состоит из символов \w - буквы, цифры, подчёркивание

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
                self::EXPR_BLOCK_BEGIN . '\s' . self::EXPR_IF .    '\s' . self::EXPR_BLOCK_END .
                    '(?<block_true>.*?)(?<has_false>' .
                self::EXPR_BLOCK_BEGIN . '\s' . self::EXPR_ELSE .  '\s' . self::EXPR_BLOCK_END .
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

        /
            \{%\sfor\s(?<row_name>\w+)\sin\s(?<block_name>\w+)\s%\}     # {% for row_name in block_name %}
                (?<block>.*?)                                           # Контент повторяющегося блока
            \{%\sendfor\s(\g<block_name>\s)?%\}                         # {% endfor %} или {% endfor block_name %}
        /msx                                                            # /i - РегистроНЕзависимый
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
                self::EXPR_BLOCK_BEGIN . '\s' . self::EXPR_FOR .    '\s' . self::EXPR_BLOCK_END .
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
     * @throws ViewParserException
     */
    public static function getFile($filename)
    {
        // Если имя файла не оканчивается ожидаемым расширением, добавляем его
        if (strlen($filename) < 6 || '.' . pathinfo($filename, PATHINFO_EXTENSION) != self::FILE_EXT) {
            $filename .= self::FILE_EXT;
        }
        if (!is_readable(self::DIR . $filename)) {
            throw new ViewParserException(ViewParserException::L_TPL_FILE_UNREACHABLE . ': ' . $filename, E_USER_WARNING);
        }
        return file_get_contents(self::DIR . $filename);
    }
} 