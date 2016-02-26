<?php
/**
 * Общий конфиш
 * Created by PhpStorm.
 * User: Виктор
 * Date: 12.02.2016
 * Time: 0:15
 */



/** @const Хост */
define('HOST', 'http://' . $_SERVER['HTTP_HOST'] . '/');

// Использовать эти константы через класс всяко удобнее
define('_ROOT', __DIR__ . DIRECTORY_SEPARATOR);
define('_TPL_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR);
define('_LOG_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR);
define('_LOG_FILE', _LOG_DIR . 'common.log');
define('_LOG_ERROR_FILE', _LOG_DIR . 'error.log');
define('_DB_LOG_FILE', _LOG_DIR . 'db.log');
define('_DB_ERROR_LOG_FILE', _LOG_DIR . 'db.error.log');



/**
 * Класс конфига со статическими свойствами. Так будет проще с ним потом работать
 */
class CONFIG{

    # Общие
    /** @const Флаг дебага */
    const DEBUG = true;
    /** @const Базовая директория */
    const ROOT = _ROOT;
    /** @const Директория шаблонов */
    const TPL_DIR = _TPL_DIR;


    # Страница
    /** @const Кодировка страниц */
    const PAGE_CHARSET = 'UTF-8';
    /** @const Общий заголовок страниц */
    const PAGE_TITLE = 'MICR0';


    # Логи
    /** @const Флаг логгирования в БД или файл */
    const LOG_USE_DB = false;
    /** @const Директория логов */
    const LOG_DIR = _LOG_DIR;
    /** @const Лог */
    const LOG_FILE = _LOG_FILE;
    /** @const Лог ошибок */
    const LOG_ERROR_FILE = _LOG_ERROR_FILE;

    # БД
    /** @const Флаг дебага БД */
    const DB_DEBUG = true;
    /** @const Строка подключения к БД */
    const DB_DSN = 'mysql:host=localhost;dbname=mysql';
    /** @const Хост БД */
    const DB_HOST = 'localhost';
    /** @const Порт БД */
    const DB_PORT = 3306;
    /** @const Имя БД */
    const DB_NAME = 'yii';
    /** @const Пользователь БД */
    const DB_USER = 'root';
    /** @const Пароль БД @deprecated Подразумевается, что не используется в продакшне */
    const DB_PASSWORD = '';
    /** @const Кодировка БД */
    const DB_CHARSET = 'utf8';
    /** @const Лог БД */
    const DB_LOG_FILE = _DB_LOG_FILE;
    /** @const Лог ошибок БД */
    const DB_ERROR_LOG_FILE = _DB_ERROR_LOG_FILE;

}
