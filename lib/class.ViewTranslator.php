<?php
/**
 * Templates to PHP translator сlass (PHP 5 >= 5.6.0)
 * Special thanks to: all, http://www.php.net
 * Copyright (c)    viktor Belgorod, 2016-2016
 * Email            vinjoy@bk.ru
 * Version            1.0.0
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the MIT License (MIT)
 * @see https://opensource.org/licenses/MIT
 */


require_once(__DIR__ . DIRECTORY_SEPARATOR . 'class.BaseException.php');


/** Собственное исключение класса */
class ViewTranslatorException extends BaseException
{
    # Языковые константы класса
    const L_TPL_FILE_UNREACHABLE = 'Файл с шаблоном недоступен';
}


/** @todo Трансляция шаблонов в PHP-код */
/** @todo Формат имени файла кешированного шаблона, который указывал бы на имя оригинального шаблона */
/** @todo Работа с версией инстанса шаблона */


/**
 * Класс транслятора шаблонов в PHP-код
 * @author      viktor
 * @version     1.0
 * @package     Micr0
 */
class ViewTranslator extends ViewBase
{
    /**
     * Константные теги
     */
    const TAG_ELSE = '<?php else: ?>';
    const TAG_ENDIF = '<?php endif; ?>';
    const TAG_ENDFOR = '<?php endforeach; ?>';

    /** @const Версия шаблона. Переопределяется в потомках */
    const INSTANCE_VERSION = '';


    /**
     * Замена в тексте шаблона $tplString строковых и числовых переменных PHP-кодом
     * вставки данных из контекста генерируемого шаблона
     * @param string $tplString Шаблон в строке
     * @return string
     * @throws ViewTranslatorException
     */
    protected static function translateStrings($tplString)
    {
        // Получаем результат выполнения регулярного выражения поиска переменных
        if ($matches = self::pregMatchStrings($tplString)) {
            foreach ($matches['var_name'] as $varIndex => $varName) {
                $tplString = str_replace(
                    $matches[0][$varIndex],
                    self::tagVar(
                        $varName,
                        $matches['var_index'][$varIndex],
                        $matches['modifier'][$varIndex]
                    ),
                    $tplString
                );
            }
        }
        return $tplString;
    }


    /**
     * Замена в тексте шаблона $tplString условных блоков PHP-кодом
     * вставки данных из контекста генерируемого шаблона
     * Флаг проверяется как bool
     * @param string $tplString Шаблон в строке
     * @return string
     */
    protected static function translateConditionals($tplString)
    {
        // Получаем результат выполнения регулярного выражения поиска условных блоков
        if ($matches = self::pregMatchConditionals($tplString)) {
            foreach ($matches[0] as $blockIndex => $blockDeclaration) {

                $tplString = str_replace(
                    $blockDeclaration,
                    self::tagIf($matches['block_name'][$blockIndex]) .
                    trim($matches['block_true'][$blockIndex]) .
                    (strlen($matches['block_false'][$blockIndex]) > 0
                        ? self::TAG_ELSE . trim($matches['block_false'][$blockIndex])
                        : ''
                    ) .
                    self::TAG_ENDIF,
                    $tplString
                );
            }
        }
        return $tplString;
    }


    /**
     * Вставка в код страницы PHP-тега с началом цикла перебора элементов
     * self::[$varName], self::[$varName][$varIndex] или self::[$varName]->$varIndex
     * @param mixed $varName
     * @param mixed $varIndex
     * @param string $rowName Имя переменной, по которой будет идти итерация
     * @return string
     */
    protected static function tagFor($varName, $varIndex = '', $rowName = 'row')
    {
        return '<?php foreach(self::getVar("' . addslashes($varName) . '", "' . addslashes($varIndex) . '", false) as $' . $rowName . '): ?>';
    }


    /**
     * Вставка в код страницы PHP-тега с булевым флагом
     * @param mixed $varName
     * @param mixed $varIndex
     * @return string
     */
    protected static function tagIf($varName, $varIndex = '')
    {
        return '<?php if (self::getVar("' . addslashes($varName) . '", "' . addslashes($varIndex) . '", false)): ?>';
    }


    /**
     * Вставка в код страницы PHP-тега с выводом одной переменной
     * @param mixed $varName
     * @param mixed $varIndex
     * @param mixed $varModifier
     * @return string
     */
    protected static function tagVar($varName, $varIndex = '', $varModifier = '')
    {
        // Применяем модификатор, если он есть
        switch ($varModifier) {
            case 'raw':
                $escape = 'false';
                break;
            case 'e':
                $escape = 'true';
                break;
            default:
                $escape = self::AUTO_ESCAPE ? 'true' : 'false';
        }
        return '<?= self::getVar("' . addslashes($varName) . '", "' . addslashes($varIndex) . '", ' . $escape . '); ?>';
    }


    /**
     * Трансляция шаблона в исполняемый файл PHP
     * @param string $filename
     * @return int|false Число записанных ф вайл байт, или false в случае неудачи
     * @throws ViewTranslatorException
     */
    public static function translateFile($filename)
    {
        // Если имя файла не оканчивается ожидаемым расширением, добавляем его
        if (strlen($filename) < 6 || '.' . pathinfo($filename, PATHINFO_EXTENSION) != self::FILE_EXT) {
            $filename .= self::FILE_EXT;
        }
        /** @todo Убрать это, а взамен создавать файл автоматически */
        if (!is_writable(self::DIR_RUNTIME . $filename)) {
            throw new ViewTranslatorException(ViewTranslatorException::L_TPL_FILE_UNREACHABLE .
                ': ' . CONFIG::RUNTIME_DIR . DIRECTORY_SEPARATOR . self::DIR . DIRECTORY_SEPARATOR . $filename
            );
        }
        // Не будем городить макаронку
        $tplString = self::getFile($filename);
        $tplString = self::translateStrings($tplString);
        $tplString = self::translateConditionals($tplString);
        return file_put_contents(
            self::DIR_RUNTIME . $filename,
            $tplString
        );
    }
} 