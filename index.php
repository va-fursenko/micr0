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
    'some_el' => ['rows' => [
        ['Belgorod', 'Russia', '360k'],
        ['St. Peterburg','Russia','5kk'],
        ['<a>New York</a>', 'USA', '10kk'],
        ['Las Vegas', 'USA', '2kk'],
        ['London', 'Great Britain', '12kk'],
    ]],
    'place_button' => false,
    'echo_bool'    => 1,
    'flag3'        => 0,
    'some_flag' => 'warning',
];

$content = 'Hello, world!';
//ViewTranslator::translateFile('base');
$content = View::display('base', $data);


// Генерим контент и рисуем шаблон
//$content = ViewParser::parseFile('base', $data);
require_once(CONFIG::ROOT . DIRECTORY_SEPARATOR . CONFIG::VIEW_DIR . '/layout.Main.php');




//$db = new Db(CONFIG::DB_DSN, CONFIG::DB_USER, CONFIG::DB_PASSWORD);
//var_dump($db->selectOne("SELECT value FROM system_parameters"));





$time = microtime(true) - $start;
printf('Скрипт выполнялся %.4F сек.', $time);
