<?php
/**
 * Templates to PHP translator сlass (PHP 5 >= 5.6.0)
 * Special thanks to: all, http://www.php.net
 * Copyright (c)    viktor Belgorod, 2016-2016
 * Email            vinjoy@bk.ru
 * Version          1.1.0
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the MIT License (MIT)
 * @see https://opensource.org/licenses/MIT
 */


require_once(__DIR__ . DIRECTORY_SEPARATOR . 'class.BaseException.php');


/** Собственное исключение класса */
class ViewTranslatorException extends ViewParserException
{
}


/**
 * Класс транслятора шаблонов в PHP-код
 * @author      viktor
 * @version     1.1.0
 * @package     Micr0
 */
class ViewTranslator extends ViewBase
{
    /**
     * Константные теги
     */
    const BLOCK_ELSE   = "\n\t\t} else {\n";
    const BLOCK_ENDIF  = "\n\t\t}\n";
    const BLOCK_ENDFOR = "\n\t\t}\n";

    /** @const Расширение файлов шаблонов */
    const FILE_EXT = '.php';


    /**
     * Замена в тексте шаблона $tplString строковых и числовых переменных PHP-кодом
     * вставки данных из контекста генерируемого шаблона
     * @param string $tplString Шаблон в строке
     * @return string
     */
    protected static function translateStrings($tplString)
    {
        // Получаем результат выполнения регулярного выражения поиска переменных
        if ($matches = self::pregMatchStrings($tplString)) {
            foreach ($matches['var_name'] as $varIndex => $varName) {
                $tplString = str_replace(
                    $matches[0][$varIndex],
                    self::blockVar(
                        $varName,
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
            foreach ($matches[0] as $blockIndex => $blockHTML) {
                $blockPHP = self::blockIf($matches['block_name'][$blockIndex]) .
                    "\t\t\t\$result .= '" . trim($matches['block_true'][$blockIndex]) . "';";
                if (strlen($matches['block_false'][$blockIndex]) > 0) {
                    $blockPHP .= self::BLOCK_ELSE .
                        "\t\t\t\$result .= '" . trim($matches['block_false'][$blockIndex]) . "';";
                }
                $blockPHP .= self::BLOCK_ENDIF;

                $tplString = str_replace(
                    $blockHTML,
                    "';\n\t\t" . $blockPHP . "\t\t\$result .= '",
                    $tplString
                );
            }
        }
        return $tplString;
    }


    /**
     * Замена в повторяющемся блоке строковых и числовых переменных
     * @param string $tplString Шаблон в строке
     * @param string $rowName Имя переменной, означаяющей ряд
     * @return string
     */
    protected static function translateArrayStrings($tplString, $rowName)
    {
        if ($matches = self::pregMatchStrings($tplString)) {
            $varsOmitted = 0;
            // Проходим по всем найденным переменным
            foreach ($matches['var_name'] as $varIndex => $varName) {
                // Пропускаем переменные не из данного блока
                if (strpos($varName, $rowName) === 0) {
                    // Отдельно обрабатываем служебные переменные. Пока только #, так что поступаем по-простому
                    if (substr($matches['var_name'][$varIndex], -1) == '#') {
                        $replace = "' . (\$index + 1) . '";
                        $varsOmitted++;
                    } else {
                        $replace = self::blockVar($varName, $matches['modifier'][$varIndex], $rowName, $varIndex - $varsOmitted);
                    }
                    $tplString = str_replace(
                        $matches[0][$varIndex],
                        $replace,
                        $tplString
                    );
                } else {
                    $varsOmitted++;
                }
            }
        }
        return "\t\t\t\$result .= '" . $tplString . "';";
    }


    /**
     * Замена в тексте шаблона $tplString повторяющихся блоков PHP-кодом
     * вставки данных из контекста генерируемого шаблона
     * @param string $tplString Шаблон в строке
     * @return string
     */
    protected static function translateArrays($tplString)
    {
        // Получаем результат выполнения регулярного выражения поиска условных блоков
        if ($matches = self::pregMatchArrays($tplString)) {
            foreach ($matches[0] as $blockIndex => $blockHTML) {
                $blockPHP = self::blockFor(
                    $matches['block_name'][$blockIndex],
                    $matches['row_name'][$blockIndex]
                );
                $blockPHP .= self::translateArrayStrings(
                        trim($matches['block'][$blockIndex]),
                        $matches['row_name'][$blockIndex]
                    ) .
                    self::BLOCK_ENDFOR ;
                $tplString = str_replace(
                    $blockHTML,
                    "';\n\t\t" . $blockPHP . "\t\t\$result .= '",
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
     * @param string $rowName Имя переменной, по которой будет идти итерация
     * @return string
     */
    protected static function blockFor($varName, $rowName = 'row')
    {
        return "foreach (self::getVar('" . addslashes($varName) . "', false) as \$index => $$rowName) {\n";
    }


    /**
     * Вставка в код страницы PHP-тега с булевым флагом
     * @param mixed $varName
     * @return string
     */
    protected static function blockIf($varName)
    {
        return "if (self::getVar('" . addslashes($varName) . "', false)) {\n";
    }


    /**
     * Вставка в код страницы PHP-тега с выводом одной переменной
     * @param mixed $varName Полное имя переменной
     * @param mixed $varModifier Модификатор вывода
     * @param string $baseName Имя переменной с контекстом
     * @param string $altIndex Альтернативный индекс переменной в контексте (для итераторов)
     * @return string
     */
    protected static function blockVar($varName, $varModifier = '', $baseName = null, $altIndex = null)
    {
        // Применяем модификатор, если он есть
        switch ($varModifier) {
            case 'raw':
                $params = 'false';
                break;
            case 'e':
                $params = 'true';
                break;
            default:
                $params = self::AUTO_ESCAPE ? 'true' : 'false';
        }
        $params .= $baseName !== null ? ", ['$baseName' => \$$baseName]" : '';
        $params .= $altIndex !== null ? ", '$baseName.$altIndex'" : '';
        return "' . self::getVar('" . addslashes($varName) . "', $params) . '";
    }


    /**
     * Получение имени класса в кешированном шаболне по его имени
     * @param string $filename
     * @return string
     */
    public static function getTplClassName($filename)
    {
        return 'Tpl_' . md5($filename) . '_Class';
    }



    /**
     * Формирование текста класса для шаблона
     * @param string $tplString
     * @param string $className
     * @return string
     */
    protected static function formTplClass($tplString, $className){
        $result = "<?php\n" .
            "class $className extends ViewInstance\n{\n" .
            "\tpublic static function display(\$data)\n\t{\n" .
            "\t\tparent::display(\$data); \n" .
            "\t\t\$result = '$tplString';\n" .
            "\t\treturn \$result;\n" .
            "\t}\n}";
        return $result;
    }


    /**
     * Трансляция шаблона в исполняемый файл PHP
     * @param string $filename
     * Да, я понимаю, что там не должно быть ни пробелов, ни чего-то подобного, но это просто экспериментальный класс
     * @return int|false Число записанных ф вайл байт, или false в случае неудачи
     * @throws ViewTranslatorException
     */
    public static function translateFile($filename)
    {
        $f = fopen(self::DIR_RUNTIME . $filename . self::FILE_EXT, 'w');
        if (!$f) {
            throw new ViewTranslatorException(ViewTranslatorException::L_TPL_FILE_UNREACHABLE .
                ': ' . CONFIG::RUNTIME_DIR . DIRECTORY_SEPARATOR . self::DIR . DIRECTORY_SEPARATOR . $filename . self::FILE_EXT
            );
        }
        fclose($f);
        // Не будем городить макаронку
        $tplString = addcslashes(self::getFile($filename), "'");
        $tplString = self::translateConditionals($tplString);
        $tplString = self::translateArrays($tplString);
        $tplString = self::translateStrings($tplString);
        $tplString = self::formTplClass($tplString, self::getTplClassName($filename));
        return file_put_contents(
            self::DIR_RUNTIME . $filename . self::FILE_EXT,
            $tplString
        );
    }
} 
