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
        ['New York', 'USA', '10kk'],
        ['Las Vegas', 'USA', '2kk'],
        ['London', 'Great Britain', '12kk'],
    ],
    'place_button' => true,
    'echo_bool'    => 0,
    'flag3'        => 1,
    'some_flag' => 'warning',
];

// Генерим контент
//$content = ViewParser::parseFile('base', $data);
ViewTranslator::translateFile('base');
$content = View::display('base', $data);

// Рисуем шаблон
//require_once(CONFIG::ROOT . DIRECTORY_SEPARATOR . CONFIG::VIEW_DIR . '/layout.Main.php');

$time = microtime(true) - $start;
printf('Скрипт выполнялся %.4F сек.', $time);


$str = '   {{ data.rows.2.2 }}              ';


var_dump(preg_match_all('/(?<var_name>(?<=\{\{\s)\w+(\.(\w+|#(?![\w\.])))*(?=\s\}\}))/i', $str, $matches));



function parseVar($base, $varname, $indexValue = null)
{
    function getVarPart($base, $name)
    {
        // Простые переменные присваиваем целиком, а в массивах и объектах копируем указатели
        switch (gettype($base)) {
            case 'boolean':
            case 'integer':
            case 'double':
            case 'string':
            case 'NULL':
                $result = $base;
                break;
            case 'array':
                $result = &$base[$name];
                break;
            case 'object':
                $result = &$base->$name;
                break;
            default:
                throw new \Exception("Wrong variable index: '$name'");
        }
        return $result;
    }

    $varParts = explode('.', $varname);
    if (count($varParts) < 1) {
        throw new \Exception("Wrong variable name: '$varname'");
    }
    $result = getVarPart($base, $varParts[0]);
    for ($i = 1; $i < count($varParts) - 1; $i++) {
        $result = getVarPart($result, $varParts[$i]);
    }
    return $varParts[$i] == '#'
        ? $result[$indexValue]
        : getVarPart($result, $varParts[$i]);
}

var_dump($matches);


var_dump(parseVar(['data' => $data], $matches['var_name'][0]));