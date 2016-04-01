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
    const TAG_ELSE   = "\n\t\t} else {\n";
    const TAG_ENDIF  = "\n\t\t}\n";
    const TAG_ENDFOR = "\n\t\t}\n";

    /** @const Расширение файлов шаблонов */
    const FILE_EXT = '.php';


    /**
     * Замена в повторяющемся блоке строковых и числовых переменных
     * @param string $tplString Шаблон в строке
     * @param string $rowName Имя переменной, означаяющей ряд
     * @return string
     * @throws ViewTranslatorException
     */
    protected static function translateArrayStrings($tplString, $rowName)
    {
        // Получаем результат выполнения регулярного выражения поиска переменных
        if ($matches = self::pregMatchStrings($tplString)) {
            foreach ($matches['var_name'] as $varIndex => $varName) {
                if ($rowName == $varName) {
                    $tplString = str_replace(
                        $matches[0][$varIndex],
                        $matches['var_index'][$varIndex] == '#'
                            ? "' . (\$index + 1) . '"
                            : "' . (isset(\${$rowName}['" . $matches['var_index'][$varIndex] . "']) ? \${$rowName}['" . $matches['var_index'][$varIndex] . "'] : \${$rowName}[" . $indexes[$matches['var_index'][$varIndex]] . "]) . '",
                        $tplString
                    );
                }
            }
        }
        return $tplString;
    }


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
                        $matches['modifier'][$varIndex],

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
                    "\t\t\t\$result .= '" . trim($matches['block_true'][$blockIndex]) . "';" .
                    (strlen($matches['block_false'][$blockIndex]) > 0
                        ? self::TAG_ELSE . "\t\t\t\$result .= '" . trim($matches['block_false'][$blockIndex]) . "';"
                        : ''
                    ) .
                    self::TAG_ENDIF .
                    "\t\t\$result .=  '",
                    $tplString
                );
            }
        }
        return $tplString;
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
            foreach ($matches[0] as $blockIndex => $blockDeclaration) {

                $tplString = str_replace(
                    $blockDeclaration,
                    self::tagFor(
                        $matches['block_name'][$blockIndex],
                        '',
                        $matches['row_name'][$blockIndex]
                    ) .
                        "\t\t\t\$result .= '" .
                        self::translateArrayStrings(
                            trim($matches['block'][$blockIndex]),
                            $matches['row_name'][$blockIndex]
                        ) .
                        "';" .
                        self::TAG_ENDFOR . "\t\t\$result .= '",
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
    protected static function tagFor($varName, $rowName = 'row')
    {
        return "';\n\t\tforeach (self::getVar('" . addslashes($varName) . "', false) as \$index => $$rowName) {\n";
    }


    /**
     * Вставка в код страницы PHP-тега с булевым флагом
     * @param mixed $varName
     * @return string
     */
    protected static function tagIf($varName)
    {
        return "';\n\t\tif (self::getVar('" . addslashes($varName) . "', false)) {\n";
    }


    /**
     * Вставка в код страницы PHP-тега с выводом одной переменной
     * @param mixed $varName
     * @param mixed $varModifier
     * @return string
     */
    protected static function tagVar($varName, $varModifier = '')
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
        return "' . self::getVar('" . addslashes($varName) . "', $escape) . '";
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
