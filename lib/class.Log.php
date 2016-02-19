<?php

/**
 * 	Log explorer сlass (PHP 5 >= 5.0.0)
 * 	Special thanks to: http://www.php.su
 * 	Copyright (c)   Enjoy! Belgorod, 2009-2011
 * 	Email		vinjoy@bk.ru
 * 	Version		2.2.4
 * 	Last modifed	19:46 19.10.2011
 * 	
 * 	 This library is free software; you can redistribute it and/or
 * 	modify it under the terms of the GNU Lesser General Public
 * 	License as published by the Free Software Foundation; either
 * 	version 2.1 of the License, or (at your option) any later version.
 * 	@see http://www.gnu.org/copyleft/lesser.html
 * 	
 * 	Не удаляйте данный комментарий, если вы хотите использовать скрипт! 
 * 	Do not delete this comment if you want to use the script!
 *
 */

include_once(CORE_DIR . 'system/systemLOgsArchive.model.php');

/**
 * Класс работы с логами
 * @author    Enjoy
 * @version   2.2.4
 * @copyright Enjoy
 * @package   se-engine
 */
class Log {
// Языковые константы класса
    const L_LOG_FILE_UNREADABLE = 'Файл лога недоступен для чтения';
    const L_LOG_FILE_UNWRITABLE = 'Файл лога недоступен для записи';
    const L_LOG_EMPTY           = 'Файл лога пока пуст';
    const L_EMPTY_MESSAGE       = 'Запись лога пуста или имеет неправильный формат';

// Прочие константы
    const MESSAGE_SEPARATOR = "\n\n\n\n";
    const MESSAGE_HTML_SEPARATOR = '<br>';

// Методы класса
    /** 
     * Преобразовывает массив параметров в текстовое представление ошибки
     * @param array $messageArray Сообщение, выводимое на экран
     * @param array $captions - массив названий на текущем языке для полей записи
     */
    public static function parseMessage($messageArray, $captions = array(
        /* Все возможные русские заголовки строк */
        'datetime'              => '',
        'type_name'             => 'Тип события',
        'text_message'          => 'Ошибка',
        'db_ex_message'         => 'Сообщение СУБД',
        'db_query_text'         => 'Запрос',
        'db_query_type'         => 'Тип запроса',
        'db_affected_rows'      => 'Число измененных строк',
        'db_user_name'          => 'Пользователь БД',
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
                        $data = print_r($data, true);
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
     * @param string $fileName Имя файла
     * @param array $messageArray Сообщение, записываемое в файл
     * @return bool
     */
    public static function write2File($fileName, $messageArray) {
        if (!is_writable($fileName)) {
            Ex::throwEx(self::L_LOG_FILE_UNWRITABLE . ' - ' . $fileName);
        }
        if (!isset($messageArray['datetime'])) {
            $messageArray = array('datetime' => date("Y-m-d H:i:s")) + $messageArray;// Дата должна идти первой в ассоциативном массиве сообщения
        }
        return error_log(addslashes(serialize($messageArray)) . self::MESSAGE_SEPARATOR, 3, $fileName);
    }

    /**
     * Запись в таблицу логов БД сообщения
     * @param mixed $db Дескриптор соединения с БД
     * @param array $messageArray Сообщение, записываемое в лог
     * @return bool
     */
    public static function write2Db($db, $messageArray){
        if (!isset($messageArray['datetime'])) {
            $messageArray['datetime'] = date("Y-m-d H:i:s");
        }
        $messageArray = Filter::sqlFilterAll($messageArray);
        Db::blockLogging(true);
        $result = SystemLogsArchive::addOne($db, $messageArray);
        Db::blockLogging(false);
        return $result;
    }
    
    /**
     * Запись в файл лога или определённую таблицу БД сообщения
     * @param mixed $object,.. Имя файла логов или дескриптор соединения с БД в случае двух параметров
     * @param array $messageArray Сообщение, записываемое в лог
     * @return bool
     */
    public static function write($object, $messageArray = null){
        if (LOG_USE_DB){
            if (func_num_args() == 2){
                return self::write2Db($object, $messageArray);
            }else{
                return self::write2Db(Db::getInstance(), $object);
            }
        }else{
            if (func_num_args() == 2){
                return self::write2File($object, $messageArray);
            }else{
                return self::write2File(LOG_FILE, $object);
            }
        }
    }

    /** 
     * Выводит на экран список логов
     * @param object $db Дескриптор соединения с БД
     * @param string $typeName Тип логов
     * @param int $startFrom Начальная позиция в выборке
     * @param int $limit Число выбираемых записей
     * @param bool $descOrder,.. Флаг - порядок вывода записей(обратный или прямой)
     * @return string
     */
    public static function showDbLog($db, $typeName, $startFrom, $limit, $descOrder = true) {
        Db::blockLogging(true);
        $result = SystemLogsArchive::getList($db, $typeName, $startFrom, $limit, 'datetime', $descOrder);
        Db::blockLogging(false);
        return $result;
    }   

    /** 
     * Получает колчиство записей в логе
     * @param object $db Дескриптор соединения с БД
     * @param string $typeName Тип логов
     */
    public static function checkDbLog($db, $typeName){
        Db::blockLogging(true);
        $result = SystemLogsArchive::getCount($db, $typeName);
        Db::blockLogging(false);
        return $result;
    }

    /** 
     * Выводит на экран файл лога
     * @param string $fileName Имя файла
     * @param bool $descOrder,.. Флаг - порядок вывода записей(обратный или прямой)
     */
    public static function showLogFile($fileName, $descOrder = true) {
        if (!is_readable($fileName)) {
            Ex::throwEx(self::L_LOG_FILE_UNREADABLE . ' - ' . $fileName);
        }
        $content = explode(self::MESSAGE_SEPARATOR, file_get_contents($fileName));
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
     * @param object $object Выводимый объект
     * @param bool $withPre Флаг - оборачивать или нет результат тегами <pre>
     */
    public static function dumpObject($object, $withPre = true) {
        ob_start();
        var_dump($object);
        $strObject = ob_get_contents();
        ob_end_clean();
        return $withPre ? '<pre>' . $strObject . '</pre>' : $strObject;
    }

    /** 
     * Вывод сложного объекта в строку или на экран 
     * @param object $object Выводимый объект
     * @param bool $print Флаг - печатать объект в буфере вывода или возвращать как строку
     * @return string Строковое представление элемента, или bool в случае вывода его в буфер вывода
     */
    public static function printObject($object, $print = false) {
        return print_r($object, !$print);
    }
}

