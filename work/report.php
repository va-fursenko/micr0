<?php
/**
 * Created by PhpStorm.
 * User: viktor
 * Date: 04.03.16
 * Time: 14:59
 */

// Папка для рабочих данных
define('DATA_ROOT', CONFIG::ROOT . DIRECTORY_SEPARATOR . 'data/');

// Исходный экселевский файл
$xlsFilename = DATA_ROOT . 'Template(month)new.xls';
// Новый текстовый файл
$newFilename = DATA_ROOT . 'reclama3.xls';




// Индекс страницы в файле ШАБЛОНА (нумерация с 0)
define('TPL_SHEET', 1);
// Стартовая строка, с которой начинается полезная информация в файле ШАБЛОНА (нумерация с 0)
define('TPL_FIRST_ROW', 8);
// Столбец, в котором расположен "старый" список айдишников в файле ШАБЛОНА (нумерация с 0)
define('TPL_FIRST_COL', 7);




/**
 * Mr Hankey's christmas classics
 * @param array $a
 * @return string
 */
function showArr($a){
    $result = '';
    foreach ($a as $k => $v){
        $result .= "\t" . Filter::strPad("'$k'", 32) . " => [" . implode(', ', $v) . "]\n";
    }
    return "[\n$result]";
}




/**
 * Читаем из указанного файла с указанной страницы указанный столбец, начиная с таки-указанной строки
 * @param string $filename
 * @param int $sheetN
 * @param int $colN
 * @param int $rowStartFrom
 * @return mixed
 */
function getCol($filename, $sheetN, $colN, $rowStartFrom){
    // Открываем файл
    $xls = PHPExcel_IOFactory::load($filename);
    // Устанавливаем индекс активного листа
    $xls->setActiveSheetIndex($sheetN);
    // Получаем активный лист
    $sheet = $xls->getActiveSheet();

    // Строка, с которой начинаем сбор айдишников в столбце
    $i = $rowStartFrom;

    //Массив "старых" айдишников для дальнейшего стравнения
    $col = [];

    // Смотрим, что в первой рабочей строке
    $val = trim($sheet->getCellByColumnAndRow($colN, $i)->getValue());
    while ($val !== '' && $val !== 'Total') { // Грубовато, но заканчиваем чтение стобца

        //$col[$val] = $val;
        $col[$val] = [
            intval($sheet->getCellByColumnAndRow($colN + 1, $i)->getValue()),
            intval($sheet->getCellByColumnAndRow($colN + 2, $i)->getValue()),
            intval($sheet->getCellByColumnAndRow($colN + 3, $i)->getValue()),
            intval($sheet->getCellByColumnAndRow($colN + 4, $i)->getValue()),
            intval($sheet->getCellByColumnAndRow($colN + 5, $i)->getValue()),
            intval($sheet->getCellByColumnAndRow($colN + 6, $i)->getValue()),
        ];

        $i++;
        $val = trim($sheet->getCellByColumnAndRow($colN, $i)->getValue());
    }
    return $col;
};






/**
 * Читаем старый столбец с айдишниками
 * @param string $filename
 * @return mixed
 */
function readBaseCol($filename){
    // Получаем искомый столбец
    $firstCol = getCol($filename, TPL_SHEET, TPL_FIRST_COL, TPL_FIRST_ROW);

    // Сохраняем в сериализованном виде
    file_put_contents(DATA_ROOT . 'baseCol', json_encode($firstCol));

    return [
        'success' => true,
        'message' => "Считано записей: " . count($firstCol)
    ];
}




/**
 * Читаем новый столбец с айдишниками
 * @param string $filename
 * @return mixed
 */
function readNewCol($filename){
    $firstCol = getCol($filename, 0, 0, 3);

    // Сохраняем в сериализованном виде
    file_put_contents(DATA_ROOT . 'newCol', json_encode($firstCol));

    return [
        'success' => true,
        'message' => "Считано записей: " . count($firstCol)
    ];
}










/**
 * Слияние старого и нового списка айдишников
 * @return mixed
 */
function mergeCols(){
    // Достаём подготовленные ранее данные
    $baseCol = json_decode(file_get_contents(DATA_ROOT . 'baseCol'), true);
    $newCol = json_decode(file_get_contents(DATA_ROOT . 'newCol'), true);

    // Сливаем
    $diff = array_diff_key($newCol, $baseCol);

    $result = $baseCol + $diff;

    return [
        'success'   => true,
        'message'   => "Записей в объединённом массиве: " . count($result) . "\nРазличия:\n" . showArr($diff),
        //    'message'   => Log::printObject($baseCol),
    ];
}