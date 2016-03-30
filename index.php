<?php
$start = microtime(true);
// Конфиг
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/lib/autoload.php');

$data = [
    'some_data' => '<button class="btn btn-success btn-lg">Ещё одна кнопка</button>',
    'button' => ['text' => 'Жмакни меня'],
    'lines' => 'Hello, world!',
    'email' => 'helloworld@mail.ru',
    'rows' => [
        ['Belgorod', 'Russia', '360k'],
        ['St. Peterburg','Russia','5kk'],
        ['<a>New York</a>', 'USA', '10kk'],
        ['Las Vegas', 'USA', '2kk'],
        ['London', 'Great Britain', '12kk'],
    ],
    'place_button' => true,
    'echo_bool'    => 0,
    'flag3'        => 1,
    'some_flag' => 'warning',
];

// Генерим контент
$content = ViewParser::parseFile('base', $data);
//ViewTranslator::translateFile('base');
//$content = View::display('base', $data);

// Рисуем шаблон
require_once(CONFIG::ROOT . DIRECTORY_SEPARATOR . CONFIG::VIEW_DIR . '/layout.Main.php');

$time = microtime(true) - $start;
printf('Скрипт выполнялся %.4F сек.', $time);


