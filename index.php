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
//$content = View::display('base', $data);


// Генерим контент и рисуем шаблон
//$content = ViewParser::parseFile('base', $data);
//require_once(CONFIG::ROOT . DIRECTORY_SEPARATOR . CONFIG::VIEW_DIR . '/layout.Main.php');




//$db = new Db(CONFIG::DB_DSN, CONFIG::DB_USER, CONFIG::DB_PASSWORD);
//var_dump($db->selectOne("SELECT value FROM system_parameters"));

//var_dump(Filter::isDatetime('2006-03-31', ''));
$var = DateTime::createFromFormat('Y-m-d H:i:s', '2007-02-29 12:24:36');


var_dump(Filter::slashesAdd("A\"B\"'C'"));
//var_dump('-1000000000', Filter::getDatetime(-1000000000));


$var = Filter::getDatetime('2005', '%Y');
var_dump($var);
//var_dump($var->getLastErrors());


$time = microtime(true) - $start;
printf('Скрипт выполнялся %.4F сек.', $time);
