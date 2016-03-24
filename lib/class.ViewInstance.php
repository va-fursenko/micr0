<?php

/** Собственное исключение класса */
class ViewInstanceException extends BaseException
{
    # Языковые константы класса
    const L_TPL_FILE_UNREACHABLE = 'Файл с шаблоном недоступен';
    const L_TPL_BLOCK_UNKNOWN    = 'Шаблон не найден';
}

/**
 * Абстрактный класс-предок для кешированных в PHP шаблонов
 * @author      viktor
 * @version     1.0
 * @package     Micr0
 */
abstract class ViewInstance
{
    /**
     * @var array $data Контекст шаблона
     */
    protected static $data = [];


    /**
     * Метод вывода в PHP одной переменной
     * @param string $varName  Имя переменной (индекс в массиве контекста self::$data)
     * @param string $varIndex Индекс переменной (если есть) - индекс в подмассиве, или свойство
     * @param bool   $escape Флаг экранирования html
     * @return string
     * @throws ViewInstanceException
     */
    protected static function getVar($varName, $varIndex = '', $escape = false)
    {
        if (!array_key_exists($varName, self::$data)) {
            return ViewBase::VAR_BEGIN . " '$varName'" . ($varIndex ? ".'$varIndex'" : '') . ' ' . ViewBase::VAR_END;
            /* Или так, или так...
            return '';
            throw new ViewInstanceException(
                ViewInstanceException::L_WRONG_PARAMETERS .
                    ": '$varName'" . ($varIndex ? ".'$varIndex'" : '')
            )
            */
        }

        if ($varIndex === '') {
            $result = self::$data[$varName];

        } elseif (is_array(self::$data[$varName])) {
            $result = self::$data[$varName][$varIndex];

        } elseif (is_object(self::$data[$varName])) {
            $result = self::$data[$varName]->$varIndex;

        // Значит во входных данных что-то неприемлемое
        } else {
            throw new ViewInstanceException(
                ViewInstanceException::L_WRONG_PARAMETERS .
                    ": '$varName'" . ($varIndex ? ".'$varIndex'" : '')
            );
        }

        return $escape ? htmlspecialchars($result) : $result;
    }



    /**
     * Вывод текущего шаблона
     * @param array $data Контекст шаблона
     */
    public static function display($data)
    {
        self::$data = $data;
    }
} 