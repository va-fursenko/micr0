<?php
/**
 * Filter helper сlass  (PHP 5 >= 5.3.0)
 * Special thanks to:   all, http://www.php.net
 * Copyright (c)        viktor, Belgorod, 2010-2016
 * Email                vinjoy@bk.ru
 * version              2.0.4
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the MIT License (MIT)
 * @see https://opensource.org/licenses/MIT
 */


/**
 * Класс фильтрации параметров
 * @author    Enjoy
 * @version   2.0.4
 * @package   Micr0
 */
class Filter
{
    /**
     * @const Константы для метода strTrim(), определяющие направление обрезки
     */
    const TRIM_LEFT  = 'ltrim';
    const TRIM_BOTH  = 'trim';
    const TRIM_RIGHT = 'rtrim';


    /**
     * Возвращает массив $arg после применения к его элементам функции $func
     * @param callable $func Функция вида mixed function (mixed $el){...}
     * @param mixed|array $arg Аргумент или массив аргументов
     * @return mixed
     */
    public static function map(callable $func, $arg)
    {
        return is_array($arg) ? array_map($func, $arg) : $func($arg);
    }


    /**
     * Возвращает массив $arg, к элементам которого рекурсивно применили функцию $func
     * @param callable $func Функция вида mixed function (mixed $el){...}
     * @param mixed|array $arg Аргумент или массив аргументов
     * @return mixed
     */
    public static function mapRecursive(callable $func, array $arg)
    {
        foreach ($arg as $key => $value) {
            $arg[$key] = is_array($value) ? self::mapRecursive($func, $value) : $func($value);
        }
        return $arg;
    }


    /**
     * Применение ко всем элементам массива $arg функции $func и логическое сложение && результатов
     * Прерывается при получении первого false в процессе вычисления
     * @param callable $func Функция вида bool function(mixed $el){...}
     * @param mixed|array $arg Аргумент или массив аргументов
     * @return bool
     */
    public static function mapBool(callable $func, $arg)
    {
        $map = function (array $arr) use ($func)
        {
            foreach ($arr as $el) {
                if (!$func($el)) {
                    return false;
                }
            }
            return count($arr) > 0; // Для пустого массива стоит вернуть false
        };

        return is_array($arg) ? $map($arg) : $func($arg);
    }


    /**
     * Проверка аргумента или массива аргументов на принадлежность к типу, указанному во втором
     * @param mixed|array $var Аргумент или массив аргументов
     * @param mixed $type Тип данных
     * @return bool
     */
    public static function is($var, $type)
    {
        return self::mapBool(
            function ($el) use ($type)
            {
                return is_a($el, $type, false);
            },
            $var
        );
    }


    /**
     * Проверка на число и попадание в указанный диапазон аргумента, или массива аргументов
     * Опциональная проверка на принадлженость диапазону
     * @param mixed|array $var Аргумент или массив аргументов
     * @param float $from Начало диапазона допустимых значений
     * @param float $to Конец диапазона допустимых значений
     * @param int $flag Флаг фильтрации - FILTER_VALIDATE_INT, FILTER_VALIDATE_FLOAT
     * @return string
     */
    protected static function isNumberBetween($var, $from, $to, $flag)
    {
        $func = function ($el) use ($from, $to, $flag)
        {
            // Забавно, но false функция filter_var числом не считает
            if ($el === true) {
                return false;
            }
            $num = filter_var($el, $flag);
            return $num !== false && ($from === null || $num >= $from) && ($to === null || $num <= $to);
        };
        return is_array($var) ? self::mapBool($func, $var) : $func($var);
    }


    /**
     * Проверка на целочисленность и попадание в указанный диапазон аргумента, или массива аргументов
     * @param mixed|array $var Аргумент или массив аргументов
     * @param float $from Начало диапазона допустимых значений
     * @param float $to Конец диапазона допустимых значений
     * @return bool
     */
    public static function isInteger($var, $from = null, $to = null)
    {
        return self::isNumberBetween($var, $from, $to, FILTER_VALIDATE_INT);
    }


    /**
     * Проверка на натуральность и попадание в указанный диапазон аргумента, или массива аргументов.
     * 0 считается натуральным
     * @param mixed|array $var Аргумент или массив аргументов
     * @param float $from Начало диапазона допустимых значений. Значения ниже 0 заменятся на 0
     * @param float $to Конец диапазона допустимых значений
     * @return bool
     */
    public static function isNatural($var, $from = null, $to = null)
    {
        $from = $from === null || $from < 0 ? 0 : $from;
        return self::isNumberBetween($var, $from, $to, FILTER_VALIDATE_INT);
    }


    /**
     * Проверка на вещественное число аргумента, или массива аргументов
     * @param mixed|array $var Аргумент или массив аргументов
     * @param float $from Начало диапазона допустимых значений
     * @param float $to Конец диапазона допустимых значений
     * @return bool
     */
    public static function isNumeric($var, $from = null, $to = null)
    {
        return self::isNumberBetween($var, $from, $to, FILTER_VALIDATE_FLOAT);
    }


    /**
     * Проверка на строку аргумента, или массива аргументов
     * @param mixed|array $var Аргумент или массив аргументов
     * @param array $params Ассоциативный массив параметров метода
     * 'match' - проверяется соответстве регулярному выражению
     * 'mix' - минимальная длина
     * 'max' - максимальная длина
     * 'ip', 'email', 'url', 'mac' - соответствие строки указанными типам
     * @return bool
     * @todo $params ['match' => '/.../..', 'min' => int, 'max' => int]
     */
    public static function isString($var, array $params = [])
    {
        $func = function ($el) use ($params)
        {
            return is_string($el);
        };
        return self::mapBool($func, $var);
    }


    /**
     * Проверка на массив одного или нескольких аргументов
     * @param mixed $var,... Аргумент, или несколько аргументов
     * В отличие от других подобных методов класса параметры данного метода
     * по понятным причинам не принимаются в массиве, а передаются прямо в метод,
     * в произвольном количестве
     * @return bool
     */
    public static function isArray($var)
    {
        return self::mapBool('is_array', func_get_args());
    }


    /**
     * Проверка на логическое значение аргумента, или массива аргументов
     * @param mixed|array $var Аргумент или массив аргументов
     * @return bool
     */
    public static function isBool($var)
    {
        return self::mapBool('is_bool', $var);
    }


    /**
     * Проверка с помощью функции filter_var
     * @param mixed $var Аргумент или массив аргументов
     * @param int $filter Флаг функции filter_var()
     * @param int $options Флаг или комбинация флагов
     * @return bool
     * @see http://php.net/manual/ru/function.filter-var.php
     * @see http://php.net/manual/ru/filter.filters.php
     */
    protected static function filterVar($var, $filter, $options = null)
    {
        return self::mapBool(
            function ($el) use ($filter, $options)
            {
                return filter_var($el, $filter, $options) !== false;
            },
            $var
        );
    }


    /**
     * Проверка на email
     * @param mixed $var Аргумент или массив аргументов
     * @return bool
     */
    public static function isEmail($var)
    {
        return self::filterVar($var, FILTER_VALIDATE_EMAIL);
    }


    /**
     * Проверка на IP
     * @param mixed $var Аргумент или массив аргументов
     * @param int $options Флаг или комбинация флагов
     * FILTER_FLAG_IPV4, FILTER_FLAG_IPV6, FILTER_FLAG_NO_PRIV_RANGE, FILTER_FLAG_NO_RES_RANGE
     * @see http://php.net/manual/ru/filter.filters.flags.php
     * @return bool
     */
    public static function isIP($var, $options = FILTER_FLAG_IPV4)
    {
        return self::filterVar($var, FILTER_VALIDATE_IP, $options);
    }


    /**
     * Проверка на MAC
     * @param mixed $var Аргумент или массив аргументов
     * @return bool
     */
    public static function isMAC($var)
    {
        return self::filterVar($var, FILTER_VALIDATE_MAC);
    }


    /**
     * Проверка на url
     * @param mixed $var Аргумент или массив аргументов
     * @param int $options Флаг или комбинация флагов
     * FILTER_FLAG_PATH_REQUIRED, FILTER_FLAG_NO_RES_RANGE
     * @see http://php.net/manual/ru/filter.filters.flags.php
     * @return bool
     */
    public static function isUrl($var, $options = null)
    {
        return self::filterVar($var, FILTER_VALIDATE_URL, $options);
    }


    /**
     * Получение русской даты в произвольном формате, включая склоняемый месяц. Например, 1 января 2016
     * @param int|string|DateTime $time Время для форматирования. Текущее, если не указано
     * @param mixed $format Формат даты для функции strftime()
     * @return string
     * @see http://php.net/manual/ru/function.strftime.php
     */
    public static function dateRus($time = null, $format = '%d %bg %Y') {
        if ($time === null) {
            $time = time();
        } elseif ($time instanceof DateTime) {
            $time = $time->getTimestamp();
        } elseif (is_string($time)) {
            $time = (new DateTime($time))->getTimestamp();
        }
        setlocale(LC_ALL, 'ru_RU.cp1251');
        $months = ['', 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
        $format = str_replace('%bg', $months[date('n', $time)], $format);
        return strftime($format, $time);
    }


    /**
     * Проверка на правильну дату и время формата "yyyy-mm-dd hh:mm:ss"
     * @param mixed $var Аргумент или массив аргументов
     * @param string $format Формат даты и времени для функции DateTime::createFromFormat
     * @param string|datetime $from Начало диапазона допустимых значений
     * @param string|datetime $to Конец диапазона допустимых значений
     * @todo Доделать диапазон допустимых значений
     * @see http://php.net/manual/ru/datetime.createfromformat.php
     * @return bool
     */
    public static function isDatetime($var, $format = 'Y-m-d H:i:s', $from = null, $to = null)
    {
        $func = function ($el) use ($format, $from, $to)
        {
            return DateTime::createFromFormat($format, $el) !== false;
        };
        return self::mapBool($func, $var);
    }


    /**
     * Проверка на правильну дату формата "yyyy-mm-dd". Полностью аналогична isDatetime()
     * @param mixed $var Аргумент или массив аргументов
     * @param string $format Формат даты для функции DateTime::createFromFormat.
     * Параметр не проверяется и, если опрелелить в нём формат не даты, а времени,
     * (или и даты, и времени) то от строки с соответствующими данными, вернётся положительный результат
     * @param string|datetime $from Начало диапазона допустимых значений
     * @param string|datetime $to Конец диапазона допустимых значений
     * @return bool
     */
    public static function isDate($var, $format = 'Y-m-d', $from = null, $to = null)
    {
        return self::isDatetime($var, $format, $from, $to);
    }



# - - - - - - - - - - - - - - - - - - - - - - - - - - - Функции экранирования - - - - - - - - - - - - - - - - - - - - - - - - - - - - #

    /**
     * Замена html-тегов и спецсимволов их html-сущностями
     * @param mixed $var Обрабатываемая строка или массив строк
     * @param int $flags Способ обработки кавычек, аналогичен второму параметру htmlspecialchars
     * @return string
     */
    public static function htmlEncode($var, $flags = ENT_QUOTES)
    {
        $flags = $flags !== null ?: ENT_COMPAT | ENT_HTML401;
        return self::map(
            function ($el) use ($flags)
            {
                return htmlspecialchars($el, $flags);
            },
            $var
        );
    }


    /**
     * Замена html-сущностей тегов и спецсимволов их реальными символами
     * @param mixed $var Обрабатываемая строка или массив строк
     * @param int $flags Способ обработки кавычек, аналогичен второму параметру htmlspecialchars_decode
     * @return string
     */
    public function htmlDecode($var, $flags = ENT_QUOTES)
    {
        $flags = $flags !== null ?: ENT_COMPAT | ENT_HTML401;
        return self::map(
            function ($el) use ($flags)
            {
                return htmlspecialchars_decode($el, $flags);
            },
            $var
        );
    }


    /**
     * Экранирование спесцимволов в стиле языка С
     * @param mixed $var Обрабатываемая строка или массив строк
     * @return mixed
     */
    public static function slashesAdd($var)
    {
        return self::map(
            function ($el)
            {
                return addslashes($el);
            },
            $var
        );
    }


    /**
     * Отмена экранирования спесцимволов в стиле языка С
     * @param mixed $var Обрабатываемая строка или массив строк
     * @return mixed
     */
    public static function slashesStrip($var)
    {
        return self::map(
            function ($el)
            {
                return stripslashes($el);
            },
            $var
        );
    }





# - - - - - - - - - - - - - - - - - - - - - - - Функции обработки строк и массивов - - - - - - - - - - - - - - - - - - - - - - - - - #

    /**
     * Переиндексация ассоциативного двухмерного массива по указанному индексу в строках
     * @param array $arr Переиндексовываемый массив
     * @param string $index Новый индекс - один из индексов во всех строках массива. Сохраняется первое вхождение всех дублируемых индексов
     * @return array
     */
    public static function arrayReindex(array $arr, $index)
    {
        $result = [];
        foreach ($arr as $el) {
            if (isset($el[$index]) && !isset($result[$el[$index]])) {
                $result[$el[$index]] = $el;
            }
        }
        return $result;
    }


    /**
     * Выбирает из двухмерного массива множество значений столбца
     * @todo Как-то коряво смотрится
     * @param array $arr Исходный массив
     * @param string $index Индекс столбца
     * @param bool $arrayReindex Флаг, указывающий та то, что индексация результата будет проведена значениями полученного массива
     * @return array
     */
    public static function arrayExtract(array $arr, $index, $arrayReindex = false)
    {
        $result = [];
        if ($arrayReindex) {
            foreach ($arr as $el) {
                if (isset($el[$index]) && !isset($result[$el[$index]])) {
                    $result[$el[$index]] = $el[$index];
                }
            }
        } else {
            foreach ($arr as $el) {
                if (isset($el[$index]) && (array_search($el[$index], $result) === false)) {
                    $result[] = $el[$index];
                }
            }
        }
        return $result;
    }


    /**
     * Проверяет существование в массиве ключа, или массива ключей
     * @param bool|int|string|array $keys Ключ, или массив ключей массива
     * @param array $arr Проверяемый массив
     * @return bool
     */
    public static function arrayKeysExists($keys, array $arr)
    {
        $func = function ($el) use ($arr)
        {
            return array_key_exists($el, $arr);
        };
        return is_array($keys)
            ? self::mapBool($func, $keys)
            : $func($keys);
    }


    /**
     * Замена указанной подстроки или указанных подстрок на другую подстроку(подстроки).
     * @param string|array $search Старая подстрока(подстроки)
     * @param string|array $replacement Новая подстрока(подстроки)
     * @param string|array $subject Обрабатываемая строка, или массив строк
     * @return string|array
     */
    public static function strReplace($search, $replacement, $subject)
    {
        $func = function ($el) use ($search, $replacement)
        {
            return str_replace($search, $replacement, $el);
        };
        return is_array($subject)
            ? $func($subject)
            : self::map($func, $subject);
    }


    /**
     * Получение подстроки $str, заключенной между $sMarker и $fMarker. Регистрозависима
     * @param string $str Строка, в которой ищется подстрока
     * @param string $strFrom Маркер начала
     * @param string $strTo Маркер конца
     * @param int $initOffset
     * @return string
     * Похоже, что тут вызов от массива строк не нужен
     */
    public static function strBetween($str, $strFrom, $strTo, $initOffset = 0)
    {
        $s = strpos($str, $strFrom, $initOffset);
        if ($s !== false) {
            $s += strlen($strFrom);
            $f = strpos($str, $strTo, $s);
            if ($f !== false) {
                return substr($str, $s, $f - $s);
            }
        }
        return '';
    }


    /**
     * Увеличение строки до $padLength символов
     * @param string|array $var Исходная строка, или массив строк
     * @param int $padLength Длина, до которой будет дополняться исходная строка
     * @param string $padStr Строка, которой будет дополняться исходная строка
     * @param int $direct Направление дополнения - STR_PAD_RIGHT, STR_PAD_LEFT, STR_PAD_BOTH
     * @return string
     * @see http://php.net/manual/ru/function.str-pad.php
     */
    public static function strPad($var, $padLength, $padStr = ' ', $direct = STR_PAD_RIGHT)
    {
        $func = function ($el) use ($padLength, $padStr, $direct)
        {
            return str_pad($el, $padLength, $padStr, $direct);
        };
        return is_array($var)
            ? $func($var)
            : self::map($func, $var);
    }


    /**
     * Ограничивает строку указанной длинной
     * @param string|array $var Обрабатываемая строка, или массив строк
     * @param int $length Длина, до которой сокращается строка
     * @param string $strEnd Окончание укорачиваемой строки
     * @return string
     */
    public static function strSlice($var, $length, $strEnd = '')
    {
        $func = function ($el) use ($length, $strEnd)
        {
            return substr($el, 0, $length) . $strEnd;
        };
        return is_array($var)
            ? $func($var)
            : self::map($func, $var);
    }


    /**
     * Удаляет в строке пробелы в начале, конце, или с обеих сторон
     * @param string|array $var Обрабатываемая строка, или массив строк
     * @param callable $direct направление, с которого удаляются символы.
     * Одна из собственных констант TRIM_BOTH, TRIM_RIGHT, TRIM_LEFT
     * @param string $charMask Набор удаляемых сисволов. Диапазон можно указывать через ..
     * @return string
     */
    public static function strTrim($var, callable $direct = self::TRIM_BOTH, $charMask = " \t\n\r\0\x0B")
    {
        $func = function ($el) use ($direct, $charMask)
        {
            return $direct($el, $charMask);
        };
        return is_array($var)
            ? $func($var)
            : self::map($func, $var);
    }
}

