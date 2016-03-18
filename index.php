<?php

// Конфиг
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/lib/init.common.php');

// Рабочий модуль
//require_once(__DIR__ . '/work/report.php');

// Генерим контент
$content = Tpl::parseFile(
    'base',
    [
        'lines' => 'Hello, world!',
        'button_text' => 'Жмакни меня',
        'email' => 'helloworld@mail.ru',
        'rows' => [
            [
                'city'       => 'Belgorod',
                'country'    => 'Russia',
                'population' => '360k'
            ],
            [
                'city'       => 'St. Peterburg',
                'country'    => 'Russia',
                'population' => '5kk'
            ],
            ['New York', 'USA', '10kk'],
            ['Las Vegas', 'USA', '2kk'],
            ['London', 'Great Britain', '12kk'],
        ],
        'place_button' => true,
        'echo_bool'    => true,
        'flag3'        => 0,
        'some_flag' => 'warning'
    ]
);



// Рисуем шаблон
require_once(CONFIG::ROOT . DIRECTORY_SEPARATOR . CONFIG::TPL_DIR . '/layout.main.php');
