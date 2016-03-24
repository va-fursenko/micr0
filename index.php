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
        'some_data' => '<button class="btn btn-success btn-lg">Ещё одна кнопка</button>',
        'button' => ['text' => 'Жмакни меня'],
        'lines' => 'Hello, world!',
        'email' => 'helloworld@mail.ru',
        'rows' => [
            ['Belgorod', 'Russia', '360k'],
            ['St. Peterburg','Russia','5kk'],
            ['New York', 'USA', '10kk'],
            ['Las Vegas', 'USA', '2kk'],
            ['London', 'Great Britain', '12kk'],
        ],
        'place_button' => true,
        'echo_bool'    => 0,
        'flag3'        => 1,
        'some_flag' => 'warning',
    ]
);

// Рисуем шаблон
require_once(CONFIG::ROOT . DIRECTORY_SEPARATOR . CONFIG::VIEW_DIR . '/layout.Main.php');


//ViewTranslator::translateFile('base');

/*
(?<case_block>
    \{\{\? (?<block_name>\w+) ((?<sign>=|==|>|>=|<|<=) (?<q>[\'"`]?) (?<condition>\w+) \g<q> )? \}\}    # {{?имя_блока}}
        (?<block_true>.*?)                                     # Контент для положительного варианта
)+
*/