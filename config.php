<?php
/**
 * Общий конфиш
 * Created by PhpStorm.
 * User: Виктор
 * Date: 12.02.2016
 * Time: 0:15
 */


/** @const Базовая директория */
define('BASE_DIR', '/');
/** @const Базовая директория на сервере */
define('ROOT', BASE_DIR);
/** @const Хост */
define('HOST', 'http://' . $_SERVER['HTTP_HOST'] . BASE_DIR);


/**
 * Класс конфига со статическими свойствами. Так будет проще с ним потом работать
 */
class CONFIG{

    # Общие
    /** @const Флаг дебага */
    const DEBUG = true;


    # БД
    /** @const Флаг дебага БД */
    const DB_DEBUG = true;
    /** @const Лог БД */
    const DB_LOG_FILE = 'db.log';
    /** @const Лог ошибок БД */
    const DB_ERROR_LOG_FILE = 'db.error.log';

    /** @const Хост БД */
    const DB_HOST = 'localhost';
    /** @const Порт БД */
    const DB_PORT = 3306;
    /** @const Имя БД */
    const DB_NAME = 'report';
    /** @const Пользователь БД */
    const DB_USER = 'root';
    /** @const Пароль БД */
    const DB_PASSWORD = '';
    /** @const Кодировка БД */
    const DB_ENCODING = 'utf-8';


    # Страница
    /** @const Кодировка страниц */
    const PAGE_CHARSET = 'UTF-8';
    /** @const Общий заголовок страниц */
    const PAGE_TITLE = 'GET REPORT';


    # Логи
    /** @const Флаг логгирования в БД или файл */
    const LOG_USE_DB = false;
    /** @const Лог */
    const LOG_FILE = 'common.log';
    /** @const Лог ошибок */
    const LOG_ERROR_FILE = 'error.log';

}