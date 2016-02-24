<?php
/**
 * Log explorer сlass (PHP 5 >= 5.0.0)
 * Special thanks to: all, http://www.php.net
 * Copyright (c)    viktor Belgorod, 2009-2016
 * Email		    vinjoy@bk.ru
 * Version		    2.4.0
 * Last modified	19:46 19.02.16
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the MIT License (MIT)
 * @see https://opensource.org/licenses/MIT
 *
 * Не удаляйте данный комментарий, если вы хотите использовать скрипт, и всё будет хорошо :)
 * Do not delete this comment, if you want to use the script, and everything will be okay :)
 */



/**
 * Класс работы с логами
 * @author    viktor
 * @version   2.3.0
 * @copyright viktor
 */
class Log {
    protected static $logDb = null;

    # Языковые константы класса
    const L_LOG_FILE_UNREADABLE = 'Файл лога недоступен для чтения';
    const L_LOG_FILE_UNWRITABLE = 'Файл лога недоступен для записи';
    const L_LOG_EMPTY           = 'Файл лога пока пуст';
    const L_EMPTY_MESSAGE       = 'Запись лога пуста или имеет неправильный формат';

    # Прочие константы
    const MESSAGE_SEPARATOR = "\n\n\n\n";
    const MESSAGE_HTML_SEPARATOR = '<br>';



    # Методы класса
    /** 
     * Преобразовывает массив параметров в текстовое представление ошибки
     * @param array $messageArray Сообщение, выводимое на экран
     * @param array $captions - массив названий на текущем языке для полей записи
     * @return string
     */
    public static function parseMessage($messageArray, $captions = array(
        /* Все возможные русские заголовки строк */
        'datetime'              => '',
        'type_name'             => 'Тип события',
        'text_message'          => 'Ошибка',
        'db_exception_message'  => 'Сообщение СУБД',
        'db_last_query'         => 'Крайний запрос',
        'db_query_type'         => 'Тип запроса',
        'db_rows_affected'      => 'Число измененных строк',
        'db_username'           => 'Пользователь БД',
        'db_name'               => 'Имя БД',
        'db_host'               => 'Хост БД',
        'db_port'               => 'Порт БД',
        'db_encoding'           => 'Кодировка БД',
        'db_ping'               => 'Пинг БД',
        'db_status'             => 'Статус',
        'db_result'             => 'Результат',
        'db_last_error'         => 'Ошибка запроса к БД',
        'db_connect_error'      => 'Ошибка сединения с БД',
        'php_file_name'         => 'Файл',
        'php_file_line'         => 'Строка',
        'php_trace'             => 'Стек вызова',
        'php_error_code'        => 'Код ошибки PHP',
        'http_request_method'   => 'Метод запроса',
        'http_server_name'      => 'Сервер',
        'http_request_uri'      => 'Параметры запроса',
        'http_user_agent'       => 'Браузер и ОС клиент',
        'http_remote_addr'      => 'IP адрес клиента',
        'exception_message'     => 'Сообщение ошибки',
        'session_id'            => 'id PHP сессии',
        'session_user_id'       => 'id пользователя'
    )
    ) {
        $result = '<table cellspacing=4 cellpadding=0 border=0 style="font-size:8pt;"><col width=190px;><col>';
        if (is_array($messageArray)) {
            foreach ($messageArray as $caption => $data) {
                switch ($caption) {
                    case 'datetime' : $data = '<b>' . $data . '</b>';
                        break;
                    case 'php_trace' : $data =
                                '<div style="height:500px; max-height:500px; overflow-x:scroll; overflow-y:scroll; font-size:7pt; border:1px dashed; padding:2px 0px 4px 6px; background-color:#dddddd;">
                                    <pre style="font-size:8pt;">' .
                                        //@self::printObject(unserialize($data)) .  // Класс mysqli ещё не разрешён к выводу, так что пропускаем этот момент
                                        Filter::sqlUnfilter(@var_export(unserialize($data), true)) . // Класс mysqli ещё не разрешён к выводу, так что пропускаем этот момент
                                   '</pre>
                                </div>';
                        break;
                    default: 
                        $data = self::printObject($data, is_array($data));
                }
                $result .=
                        '<tr>' .
                            '<td style="text-align:right; vertical-align:top;"><b>' .
                                $captions[$caption] . ($captions[$caption] == '' ? '' : ':') . '</b>
                             </td>' .
                            '<td>' . $data . '</td>' .
                        '</tr>';
            }
            $result .= '</table>';
            //$result = self::printObject($result);
        } else {
            $result = self::L_EMPTY_MESSAGE;
        }
        return $result . self::MESSAGE_HTML_SEPARATOR;
    }



    /** 
     * Запись в файл лога сообщения 
     * @param string $filename Имя файла
     * @param array $messageArray Сообщение, записываемое в файл
     * @return bool
     * @throws Exception
     */
    protected static function write2File($filename, $messageArray) {
        if (!is_writable($filename)) {
            throw new Exception(self::L_LOG_FILE_UNWRITABLE . ' - ' . $filename);
        }
        if (!isset($messageArray['datetime'])) {
            $messageArray = array('datetime' => date("Y-m-d H:i:s")) + $messageArray;// Дата должна идти первой в ассоциативном массиве сообщения
        }
        return error_log(addslashes(serialize($messageArray)) . self::MESSAGE_SEPARATOR, 3, $filename);
    }



    /**
     * Запись в таблицу логов БД сообщения
     * @param array $messageArray Сообщение, записываемое в лог
     * @return bool
     */
    protected static function write2Db($messageArray){
        if (!isset($messageArray['datetime'])) {
            $messageArray['datetime'] = date("Y-m-d H:i:s");
        }
        $messageArray = Filter::sqlFilterAll($messageArray);
        /** @todo Дописать нормальную работу с БД */
        $result = self::$logDb->query($messageArray);
        return $result;
    }



    /**
     * Запись в файл лога или определённую таблицу БД сообщения
     * @param array $object Сообщение, записываемое в лог
     * @param mixed $filename,.. Имя файла логов
     * @return bool
     */
    public static function write($object, $filename = null){
        if (CONFIG::LOG_USE_DB){
            return self::write2Db($object);
        }else{
            return self::write2File($filename ? $filename : CONFIG::LOG_FILE, $object);
        }
    }



    /** 
     * Выводит на экран список логов
     * @param string $typeName Тип логов
     * @param int $startFrom Начальная позиция в выборке
     * @param int $limit Число выбираемых записей
     * @param bool $descOrder,.. Флаг - порядок вывода записей(обратный или прямой)
     * @return string
     */
    public static function showDbLog($typeName, $startFrom, $limit, $descOrder = true) {
        /** @todo Дописать нормальную работу с БД */
        return self::$logDb->query($typeName, $startFrom, $limit, 'datetime', $descOrder);
    }   



    /** 
     * Получает колчиство записей в логе
     * @param string $typeName Тип логов
     */
    public static function checkDbLog($typeName){
        /** @todo Дописать нормальную работу с БД */
        return self::$logDb->query($typeName);
    }



    /** 
     * Выводит на экран файл лога
     * @param string $fileName Имя файла
     * @param bool $descOrder,.. Флаг - порядок вывода записей(обратный или прямой)
     * @return string
     * @throws Exception
     */
    public static function showLogFile($fileName, $descOrder = true) {
        if (!is_readable(CONFIG::LOG_DIR . $fileName)) {
            throw new Ex(self::L_LOG_FILE_UNREADABLE . ' - ' . $fileName);
        }
        $content = explode(self::MESSAGE_SEPARATOR, file_get_contents(CONFIG::LOG_DIR . $fileName));
        $result = '';
        if (count($content) > 1) {
            foreach ($content as $key => $message) {
                if ($message == '') {
                    break;
                }
                if ($descOrder){
                    $result = self::parseMessage(unserialize(stripslashes($message))) . $result;
                }else{
                    $result .= self::parseMessage(unserialize(stripslashes($message)));
                }
            }
        } else {
            $result = self::L_LOG_EMPTY;
        }
        return $result;
    }



    /** 
     * Вывод сложного объекта в строку с подробной информацией 
     * @param mixed $object Выводимый объект
     * @param bool $withPre Флаг - оборачивать или нет результат тегами <pre>
     * @return string
     */
    public static function dumpObject($object, $withPre = false) {
        ob_start();
        var_dump($object);
        $strObject = ob_get_contents();
        ob_end_clean();
        return $withPre ? '<pre>' . $strObject . '</pre>' : $strObject;
    }

    /** 
     * Вывод сложного объекта в строку или на экран 
     * @param mixed $object Выводимый объект
     * @param bool $withPre Флаг - оборачивать или нет результат тегами <pre>
     * @return string Строковое представление элемента, или bool в случае вывода его в буфер вывода
     */
    public static function printObject($object, $withPre = false) {
        $result = print_r($object, true);
        return $withPre ? '<pre>' . $result . '</pre>' : $result;
    }
}

