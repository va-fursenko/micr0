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




/**
 * Класс конфига со статическими свойствами. Так будет проще с ним потом работать
 * @see http://php.net/manual/ru/language.oop5.magic.php#language.oop5.magic.debuginfo
 * @todo Попытаться закрыть данные от дебага с помощью магических методов
 */
class CONFIG
{
    # Общие
    /** @const bool Флаг дебага */
    const DEBUG = true;
    /** @const string Базовая директория */
    const ROOT = __DIR__;
    /** @const Домен проекта */
    const HOST = HOST;



    # Страница
    /** @const string Кодировка страниц */
    const PAGE_CHARSET = 'UTF-8';
    /** @const string Общий заголовок страниц */
    const PAGE_TITLE = 'micro';



    # БД
    /** @const bool Флаг дебага БД */
    const DB_DEBUG = true;
    /** @const string Строка подключения к БД */
    const DB_DSN = 'mysql:host=localhost;dbname=mysql';
    /** @const string Хост БД */
    const DB_HOST = 'localhost';
    /** @const int Порт БД */
    const DB_PORT = 3306;
    /** @const string Имя БД */
    const DB_NAME = 'yii';
    /** @const string Пользователь БД */
    const DB_USER = 'root';
    /** @const string Пароль БД @deprecated Подразумевается, что не используется в продакшне */
    const DB_PASSWORD = 'root';
    /** @const string Кодировка БД */
    const DB_CHARSET = 'utf8';
    /** @const string Лог БД */
    const DB_LOG_FILE = 'db.log';
    /** @const string Лог ошибок БД */
    const DB_ERROR_LOG_FILE = 'db.error.log';



    # Логи
    /** @const bool Флаг логгирования в БД или файл */
    const LOG_USE_DB = false;
    /** @const string Директория логов */
    const LOG_DIR = 'log';
    /** @const string Лог */
    const LOG_FILE = 'common.log';
    /** @const string Лог ошибок */
    const ERROR_LOG_FILE = 'error.log';



    # Шаблонизатор
    /** @const bool Флаг дебага шаблонизатора */
    const VIEW_DEBUG = true;
    /** @const string Директория шаблонов */
    const VIEW_DIR = 'views';
    /** @const string Язык интерфейса по умолчанию */
    const VIEW_DEFAULT_LANGUAGE = 'RU';
    /** @const Таблица в БД со справочником доступных языков интерфейса */
    const VIEW_LANGUAGES_DB_DICTIONARY = '`interface_languages`';
}