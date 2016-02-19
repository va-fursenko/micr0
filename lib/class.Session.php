<?php

/**
 * Класс работы с сессией
 * @author    Enjoy
 * @version   1.0.3
 * @copyright Enjoy
 * @package   se-engine
 */
class Session {
    
    /**
     * Стартует сессию
     * @return int
     */
    public static function start(){
        return session_start();
    }
    
    /**
     * Завершает сессию
     * @return int
     */
    public static function destroy(){
        return session_destroy();
    }
    
    /**
     * Возвращает ID текущей сессии
     * @return string
     */
    public static function getId(){
        return session_id();
    }
    
    /** 
     * Удаляет одну переменную сессии или все 
     * @param string $index,.. Индекс переменной в сессии
     * @return bool
     */
    public static function delete($index = null){
        if ($index !== null){
            unset($index);
            return true;
        }else{
            return session_unset();
        }
    }
    
    /** 
     * Получает один из элементов сессии или всю её. Если выбирается вложенный в переменную сессии элемент, 
     * то результат по умолчанию должен обязательно быть указан в последнем параметре
     * @param string $index,.. Индекс переменной в сессии
     * @param mixed $defValue,.. Значение переменной по умолчанию
     * @return bool
     */
    public static function get($index = null, $defValue = null){
        if (func_num_args() < 3){
            return $index === null ? $_SESSION : (isset($_SESSION[$index]) ? $_SESSION[$index] : $defValue);
        }else{
            $args = func_get_args();
            $argsCount = count($args) - 2;
            $s = $_SESSION;
            $i = 0;
            while (($i < count($argsCount)) && isset($s[$args[$i]])){
                $s = $s[$args[$i]];
                $i++;
            }
            return isset($s[$args[$i]]) ? $s[$args[$i]] : $args[$i + 1];
        }
    }
    
    /**
     * Устанавливает одну переменную сессии
     * @param mixed  $index Индекс переменной сессии
     * @param string $newValue,.. Новое значение переменной сессии
     * @return bool
     */
    public static function set($index, $newValue = null){
        return (bool)($_SESSION[$index] = $newValue);
    }
    
    /**
     * Возвращает одну временную переменную сессии
     * @param mixed  $index Индекс переменной сессии
     * @param string $defValue,.. Значение переменной сессии по умолчанию
     * @return mixed Массив данных temp или $defValue
     */
    public static function tempGet($index = null, $defValue = null){
        if ($index === null){
            return isset($_SESSION['temp']) ? $_SESSION['temp'] : $defValue;
        }else{
            return isset($_SESSION['temp'][$index]) ? $_SESSION['temp'][$index] : $defValue;
        }
    }
    
    /**
     * Возвращает одну временную переменную сессии
     * @param mixed  $index Индекс переменной сессии, если определён второй параметр или новое значение переменной сессии
     * @param string $newValue,.. Новое значение переменной сессии
     * @return bool
     */
    public static function tempSet($index, $newValue = null){
        if ($newValue === null){
            return (bool)($_SESSION['temp'] = $index);
        }else{
            return (bool)($_SESSION['temp'][$index] = $newValue);
        }
    }
    
    
    /**
     * Возвращает одну временную переменную сессии
     * @param mixed  $index Индекс переменной сессии
     * @param string $defValue,.. Значение переменной сессии по умолчанию
     * @return mixed Массив данных юзера или $defValue
     */
    public static function getUser($index = null, $defValue = null){
        if ($index === null){
            return isset($_SESSION['user']) ? $_SESSION['user'] : $defValue;
        }else{
            return isset($_SESSION['user'][$index]) ? $_SESSION['user'][$index] : $defValue;
        }
    }
    
    /**
     * Возвращает одну временную переменную сессии
     * @param mixed  $index Индекс переменной сессии, если определён второй параметр или новое значение переменной сессии
     * @param string $newValue,.. Новое значение переменной сессии
     * @return bool
     */
    public static function userSet($index, $newValue = null){
        if ($newValue === null){
            return (bool)($_SESSION['user'] = $index);
        }else{
            return (bool)($_SESSION['user'][$index] = $newValue);
        }
    }
}

?>
