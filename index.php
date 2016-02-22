<?php

// Подключаем конфиг
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/lib/class.Log.php');
require_once(__DIR__ . '/lib/class.Db.php');

$db = new Db('mysql:host=localhost;dbname=yii;', 'root', 'root');

print_r($db);


// Рисуем шаблон
//require_once(ROOT . 'tpl/layout.main.php');

//preg_match_all('/host=([a-zA-Z0-9._]*)/', 'mysql:host=127.0.0.1;port=6666;password=passw;charset=utf8;username=root', $matches);
//print_r($matches);