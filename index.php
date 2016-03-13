<?php

// Конфиг
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/lib/init.common.php');

// Рабочий модуль
//require_once(__DIR__ . '/work/report.php');

// Генерим контент

$content = Tpl::parseFile(
    [
        'lines' => '' // Log::line("Импорт xls: $filename")
    ],
    'tpl.base.php'
);






// Рисуем шаблон
require_once(CONFIG::ROOT . DIRECTORY_SEPARATOR . CONFIG::TPL_DIR . '/layout.main.php');
