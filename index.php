<?php

// Подключаем конфиг
require_once('config.php');
require_once('lib/class.Db.php');

$db = new Db(CONFIG::DB_HOST, CONFIG::DB_NAME, CONFIG::DB_USER, CONFIG::DB_PASSWORD, CONFIG::DB_PORT, CONFIG::DB_ENCODING);

// Рисуем шаблон
require_once(ROOT . 'tpl/layout.main.php');