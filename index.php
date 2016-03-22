<?php

// Конфиг
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/lib/autoload.php');

// Рабочий модуль
//require_once(__DIR__ . '/work/report.php');

// Генерим контент
$content = ViewParser::parseFile(
    'base',
    [
        'lines' => 'Hello, world!',
        'button_text' => 'Жмакни меня',
        'email' => 'helloworld@mail.ru',
        'rows' => [
            ['Belgorod', 'Russia', '360k'],
            ['St. Peterburg','Russia','5kk'],
            ['New York', 'USA', '10kk'],
            ['Las Vegas', 'USA', '2kk'],
            ['London', 'Great Britain', '12kk'],
        ],
        'place_button' => true,
        'echo_bool'    => false,
        'flag3'        => 0,
        'some_flag' => 'warning'
    ]
);

// Рисуем шаблон
require_once(CONFIG::ROOT . DIRECTORY_SEPARATOR . CONFIG::VIEW_DIR . '/layout.Main.php');


/*
(?<case_block>
    \{\{\? (?<block_name>\w+) ((?<sign>=|==|>|>=|<|<=) (?<q>[\'"`]?) (?<condition>\w+) \g<q> )? \}\}    # {{?имя_блока}}
        (?<block_true>.*?)                                     # Контент для положительного варианта
)+
*/