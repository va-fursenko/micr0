<?php

/*
 *        MySQL prepared statements connect сlass (PHP 5 >= 5.0.0)
 *        Special thanks to: Loki, Kronos, Semen, http://www.php.su
 *        Copyright (c) Enjoy! Belgorod, 2011.
 *        Email               vinjoy@bk.ru
 *        version             1.0
 *        Last modifed        15:54 31.07.11
 *        
 *        This library is free software; you can redistribute it and/or
 *        modify it under the terms of the GNU Lesser General Public
 *        License as published by the Free Software Foundation; either
 *        version 2.1 of the License, or (at your option) any later version.
 *        See http://www.gnu.org/copyleft/lesser.html
 *        
 *        Не удаляйте данный комментарий, если вы хотите использовать скрипт! 
 *        Do not delete this comment if you want to use the script!
 *
 */



// ------------------------------------------        Раздел подключения вспомогательных модулей ---------------------------- //

include_once(ROOT . 'lib/class.MysqlDb.php');



// ------------------------------------------        Секция объявления служебных констант ---------------------------------- //

/**
 * Класс объектной работы в MySQL, включающий операции с подготовленными выражениями
 * ВНИМАНИЕ! Ни ОДИН метод класса ещё ни разу не запускался ввиду консервации разработки.
 *
 * @author Enjoy
 * @version 1.0
 * @copyright Enjoy
 */
class StmtMysqlDb extends MysqlDb {

    // Данные 
    protected $stmt;                  // Дескриптор подготовленного выражения

    // Языковые константы класса
    const L_UNABLE_TO_PROCESS_PREPARED_QUERY = 'Невозможно обработать подготовленный запрос';

// Методы класса
// ------------------------------------------   Работа с подготовленными выражениями    ----------------------------- //

    /** Получение нутреннего подготовленного выражения */
    public function getSTMT() {
        return $this->stmt;
    }

    /**
     * Инициализация подготовленного выражения 
     * @param boolean $rewriteInner Флаг автоматического сохранения результата во внутренней переменной
     */
    public function stmtInit($rewriteInner = true) {
        $result = mysqli_stmt_init();
        if ($rewriteInner) {
            $this->stmt = $result;
        }
        return $result;
    }
    
    /** Закрывает подготовленное выражение */
    public function stmtClose($stmt) {
        return mysqli_stmt_close(func_num_args() ? $stmt : $this->getSTMT());
    }

    /** Возвращает строковое представленик ошибки подготовленного выражения */
    public function stmtGetErrorMessage($stmt) {
        return mysqli_stmt_error(func_num_args() ? $stmt : $this->getSTMT());
    }

    /** Возвращает числовой код ошибки подготовленного выражения */
    public function stmtGetErrorNumber($stmt) {
        return mysqli_stmt_errno(func_num_args() ? $stmt : $this->getSTMT());
    }

    /** Возвращает строковое представление и числовой код ошибки подготовленного выражения */
    public function stmtGetError($stmt) {
        $argsCount = func_num_args();
        $errn = mysqli_stmt_errno($argsCount ? $stmt : $this->getSTMT());
        return $errn . ' - ' . ($errn != 0 ? mysqli_stmt_error($argsCount ? $stmt : $this->getSTMT()) : 'Ok');
    }

    /** Получает состояние подготовленного выражения */
    public function stmtGetSQLState($stmt) {
        return mysqli_stmt_sqlstate(func_num_args() ? $stmt : $this->getSTMT());
    }

    /**
     * Подготовка выражения к выполнению
     * @param string $query Текст подготавливаемого выражения
     * @param boolean $rewriteInner Флаг автоматического сохранения результата во внутренней переменной
     */
    public function stmtPrepare($query, $rewriteInner = true) {
        $result = mysqli_prepare($this->getDb(), $query);
        if ($rewriteInner) {
            $this->stmt = $result;
        }
        return $result;
    }

    /**
     * Связывание параметров с внешним подготовленным выражением 
     * @param mysqli_stmt $stmt Подготовленное выражение
     * @param string $types Обозначение типов параметров в одной строке (i-integer,  d-double, s-string, b-blob)
     * @param mixed $params,... Параметры запроса
     */
    public function stmtBindExternalParameters() {
        return call_user_func_array('mysqli_stmt_bind_param', func_get_args());
    }

    /**
     * Связывание параметров с внутренним подготовленным выражением 
     * @param string $types Обозначение типов параметров в одной строке (i-integer,  d-double, s-string, b-blob)
     * @param array $arguments Массив параметров запроса
     */
    public function stmtBindParameters($types, $arguments) {
        return call_user_func_array('mysqli_stmt_bind_param', array_unshift($arguments, $this->getSTMT(), $types));
    }

    /**
     * Выполнение внешнего(если указано) или внутреннего подготовленного выражения
     * @param $stmp Внешнее подготовленное выражение для выполнения
     */
    public function stmtExecute($stmp) {
        return mysqi_stmt_execute(func_num_args() ? $stmt : $this->getSTMT());
    }

    /**
     * Связывает внешний результат с переменными
     * @param mysqli_stmt $stmt Подготовленное выражение
     * @param mixed $params,... Параметры запроса
     */
    public function stmtBindExternalResult() {
        return call_user_func_array('mysqli_stmt_bind_result', func_get_args());
    }

    /**
     * Связывает внутренний результат с массивом переменных
     * @param array $parameters Параметры запроса
     */
    public function stmtBindResult($parameters) {
        return call_user_func_array('mysqli_stmt_bind_result', array_unshift($parameters, $this->getSTMT()));
    }

    /** Выводит в связанные ранее переменные одну строку результата или возвращает false */
    public function stmtFetch($stmt) {
        return mysqli_stmt_fetch(func_num_args() ? $stmt : $this->getSTMT());
    }

    /** Передаёт результат выполнения подготовленного выражения дескриптору */
    public function stmtStoreResult($stmt) {
        return mysqli_stmt_store_result(func_num_args() ? $stmt : $this->getSTMT());
    }

    /**
     * Получает метаданные результата выполнения подготовленного выражения.
     * Полученный результат может быть передан в другие функции mysqli для определения его параметров
     */
    public function stmtGetResultMetadata($stmt) {
        return mysqli_stmt_result_metadata(func_num_args() ? $stmt : $this->getSTMT());
    }

    /** Освобождает результат подготовленного выражения */
    public function stmtFreeResult($stmt) {
        return mysqli_stmt_free_result(func_num_args() ? $stmt : $this->getSTMT());
    }

    /** Возвращает количество параметров в переданном подготовленном выражении */
    public function stmtParametersCount($stmt) {
        return mysqli_stmt_param_count(func_num_args() ? $stmt : $this->getSTMT());
    }

    /** Возвращает количество строк в результате переданного подготовленного выражения */
    public function stmtNumRows($stmt) {
        return mysqli_stmt_num_rows(func_num_args() ? $stmt : $this->getSTMT());
    }

    /** Возвращает смещение текущего поля в результате переданного подготовленного выражения */
    public function stmtFieldTell($stmt) {
        return mysqli_field_tell(func_num_args() ? $stmt : $this->getSTMT());
    }

    /** Устанавливает смещение текущего поля в результате переданного подготовленного выражения */
    public function stmtFieldSeek($stmt, $fieldnr) {
        return mysqli_field_seek(func_num_args() ? $stmt : $this->getSTMT(), $fieldnr);
    }

    /** Возвращает значение следующего поля в результате переданного подготовленного выражения */
    public function stmtFetchField($stmt) {
        return mysqli_fetch_field(func_num_args() ? $stmt : $this->getSTMT(), $fieldnr);
    }

    /**
     * Запрос выборки к БД с использованием подготовленных выражений
     * @param string $query Текст запроса
     * @param string $types Обозначения типов параметров в строке
     * @param array $parameters Массив параметров запроса
     */
    public function stmtSelectQuery($query, $types, $parameters) {
        try {
            if (!$this->stmtPrepare($query)) {
                $this->throwException('Unable to prepare');
            }
            if (!$this->stmtBindParameters($types, $parameters)) {
                $this->throwException('Unable to bind parameters');
            }
            if (!$this->stmtExecute()){
                $this->throwException('Unable to execute');
            }
            $resultRow = $parameters;
            $result = array();
            if (!$this->stmtBindResult($resultRow)){
                $this->throwException('Unable to bind result');
            }
            while ($this->stmtFetch()){
                $result[] = $resultRow;
            }
            if (!$this->stmtClose()){
                $this->throwException('Unable to close result');
            }
        } catch (MysqlException $ex) {
            return $this->catchException($ex, self::L_UNABLE_TO_PROCESS_PREPARED_QUERY);
        }
    }
        
    /**
     * Запрос к БД без выборки с использованием подготовленных выражений
     * @param string $query Текст запроса
     * @param string $types Обозначения типов параметров в строке
     * @param array $parameters Массив параметров запроса
     */
    public function stmtQuery($query, $types, $parameters) {
        try {
            if (!$this->stmtPrepare($query)) {
                $this->throwException('Unable to prepare');
            }
            if (!$this->stmtBindParameters($types, $parameters)) {
                $this->throwException('Unable to bind parameters');
            }
            if (!$this->stmtExecute()){
                $this->throwException('Unable to execute');
            }
            if (!$this->stmtClose()){
                $this->throwException('Unable to close result');
            }
        } catch (MysqlException $ex) {
            return $this->catchException($ex, self::L_UNABLE_TO_PROCESS_PREPARED_QUERY);
        }
    }

}

?>