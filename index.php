<?php

// Подключаем конфиг
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/lib/inc.common.php');

// Рабочий модуль
require_once(__DIR__ . '/work/report.php');




// Генерим контент
$content = makeDaddyHappy('data/1.xlsx');





// Рисуем шаблон
require_once(CONFIG::ROOT . DIRECTORY_SEPARATOR . CONFIG::TPL_DIR . '/layout.main.php');