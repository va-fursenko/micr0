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


/*
 * При работе в режиме отладки темплейты хранятся в файлах. Возможно расположение несольких темплейтов в одном файле
 * Фрагменты html-кода заключены в именованных блоках, выделяемых тегами [$имя блока] и [/$имя блока]
 * Языковые константы обозначатся тегами {L_ИМЯ_КОНСТАНТЫ}
 * Прочие фрагменты текста - {имя фрагмента}
 */


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
     * Замена в тексте шаблона &$tplString строковых и числовых переменных данными из массива $dataItems
     * @param string &$tplString Шаблон в строке. Изменяется по ссылке
     * @param array $dataItems Массив с параметрами шаблона
     * @return int Число заменённых вхождений переменных
     */
    protected static function _parseVars(&$tplString, $dataItems)
    {
        $result = 0;
        // Сначала заменяем все строковые переменные, потому что они могут участвовать в других выражениях
        foreach ($dataItems as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $tplString = str_replace('{{' . $key . '}}', $value, $tplString, $count);
                $result += $count;
            }
        }
        return $result;
    }



    /**
     * Замена в тексте шаблона &$tplString условных операторов данными из массива $dataItems
     * @param string &$tplString Шаблон в строке. Изменяется по ссылке
     * @param array $dataItems Массив с параметрами шаблона
     * @return int|false Число найденных в шаблоне блоков или false в случае ошибки
     */
    protected static function _parseConditionals(&$tplString, $dataItems)
    {
        /**
         * Регулярное выражение для условных операторов if () {} else {}
         * {{?имя_блока}}...{{!имя_блока}}...{{;имя_блока}}
         * или сокращённый вариант:
         * {{?имя_блока}}...                 {{;имя_блока}}
         * имя_блока состоит из символов \w - буквы, цифры, подчёркивание
         *
        /
        \{\{\?(?<block_name>\w+)\}\}  # {{?имя_блока}}
        (?<true>.*?)              # Контент для положительного варианта
        (?<has_else>                  # Если данный блок пуст, значит второй части шаблона нет
        \{\{\!\g<block_name>\}\}  # {{!имя_блока}}
        (?<false>.*?)             # Контент для отрицательного варианта
        )?                            # 0 или 1
        \{\{\;\g<block_name>\}\}      # {{;имя_блока}}
        /msx                              # РегистроЗАВИСИМЫЙ, многострочный, \. включает в себя \n, неэкранированные пробелы и комментарии опускаются
         *
         * На всякий случай,
         * Доступ к маске по номеру: \1, \g1 или \g{1}
         * Маска левее места вызова: \g{-2}
         * Именованная маска: (?P<name>...), (?'name'...), (?<name>...)
         * Вызов именованной маски: (?P=name), \k<name>, \k'name', \k{name}, \g{name}
         */
        $result = preg_match_all(
            '/\{\{\?(?<block_name>\w+)\}\}(?<true>.*?)(?<has_else>\{\{\!\g<block_name>\}\}(?<false>.*?))?\{\{\;\g<block_name>\}\}/ms',
            $tplString,
            $matches
        );
        if ($result) {
            // Проходим по всем найденным условным операторам
            foreach ($matches[0] as $i => $match) {
                // Если искомой переменной в параметрах шаблона нет, пропускам итерацию
                if (!array_key_exists($matches['block_name'][$i], $dataItems)) {
                    continue;
                }

                // Положительный вариант
                if ($dataItems[$matches['block_name'][$i]]) {
                    $tplString = str_replace($matches[0][$i], $matches['true'][$i], $tplString);

                    // В случае отрицательного варианта проверяем существование подблока для него
                } elseif (array_key_exists('has_else', $matches[$i]) && strlen($matches[$i]['has_else']) > 0) {
                    $tplString = str_replace($matches[0][$i], $matches['false'][$i], $tplString);

                    // Если положительное условие не выполнено, а подблока для отрицательного нет, удаляем весь блок
                } else {
                    $tplString = str_replace($matches[0][$i], '', $tplString);
                }
            }
        }
        return $result;
    }



    /**
     * Заполнение текстового шаблона данными из массива
     * @param string $tplString Шаблон в строке
     * @param array $dataItems Массив с параметрами шаблона
     * @return string
     */
    protected static function _parseString($tplString, $dataItems)
    {
        // Поскольку, защищённые методы нигде более не будут вызываться, без риска запутать других и запутаться самому мы можем
        // передавать текст шаблона по ссылке и менять его в одном месте, вместо того, чтобы каждый раз создавать локальную копию

        // Сначала заменяем все строковые переменные, потому что они могут участвовать в других выражениях
        self::_parseVars($tplString, $dataItems);

        // Далее обрабатываем условные операторы
        self::_parseConditionals($tplString, $dataItems);

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
        return self::_parseString(
            self::getBlock($containerName),
            $data
        );
    }



    /**
     * Обработка целого файла или одного блока в нём
     * @param string $filename Имя файла для парсинга
     * @param array $data Массив с полями шаблона
     * @param string $blockName Имя блока
     * @return string
     * @throws TplException
     */
    public static function parseFile($filename, $data, $blockName = '')
    {
        return self::_parseString(
            $blockName ?
                  Filter::strBetween(self::getFile($filename), '[[$' . $blockName . ']]', '[[/$' . $blockName . ']]')
                : self::getFile($filename),
            $data
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


/*
 * Какая-то неудачная идея
 *
 *

    /**
     * Рекурсивное кеширование обдного блока
     * @param string $dir Директория для кеширования
     * @param string $blockName Имя блока
     * @param string $blockContent Контент блока
     * @return string
     * @throws TplException
     *
protected static function cacheBlock($dir, $blockName, $blockContent)
{
    preg_match_all("/<\\!\\-\\-(\\w+)\\[\\-\\->(.*?)<\\!\\-\\-\\]\\1\\-\\->/ims", $blockContent, $matches, PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER);
    if (!is_array($matches) || !isset($matches[1]) || count($matches[1]) == 0) {
        return false;
    }
    foreach ($matches[1] as $index => $block){
        $blockContent = substr_replace($blockContent, "<!--{$block[0]}[]-->", $matches[0][$index][1], strlen($matches[0][$index][0]));
        self::cacheBlock(
            $dir . $blockName . DIRECTORY_SEPARATOR,
            $block[0],
            $matches[2][$index][0]
        );
    }
    if (!file_exists($dir) && !mkdir($dir, 0777, true)) {
        throw new TplException(TplException::L_TPL_FILE_UNREACHABLE . ": $dir");
    }
    if (!file_put_contents($dir . $blockName . '.html', $blockContent)) {
        throw new TplException(TplException::L_TPL_FILE_UNREACHABLE . ": $dir$blockName.html");
    }
    return true;
}



    /**
     * Загрузка содержимого файла в отдельные файлы блоков
     * @param string $filename Имя файла для загрузки данных
     * @return bool
     * @throws TplException
     *
    public static function cacheFile($filename)
{
    self::cacheBlock(
        self::DIR,
        basename($filename, '.php'),
        self::getFile($filename)
    );
    return true;
}

*/