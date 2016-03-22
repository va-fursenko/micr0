<?php
/**
 * Поэтапное получение отчёта
 * User: viktor
 * Date: 09.03.16
 * Time: 10:07
 */

if (!isset($_GET['action'])) {
    exit("Прощай, со всех вокзалов поезда уходят в дальние края. Прощай, мы расстаёмся навсегда под белым небом янваааааряяя!...");
}
$act = $_GET['action'];


// Конфиг
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/lib/autoload.php');

// Рабочий модуль
require_once(__DIR__ . '/work/report.php');




try {

    switch ($act) {

        // Открываем файл и читаем список айдишников в столбце
        case 'openBase':
            $result = readBaseCol($xlsFilename);
            if ($result['success']) {
                $result['nextStep'] = 'openNew';
                $result['nextMessage'] = "# Импорт нового списка айдишников";
            }
            break;

        // Открываем новый файл и читаем список новых айдишников в первом столбце
        case 'openNew':
            $result = readNewCol($newFilename);
            if ($result['success']) {
                $result['nextStep'] = 'mergeCols';
                $result['nextMessage'] = "# Слияние массивов в общий список";
            }
            break;

        // Сливаем оба полученных массива вместе, получая список новых элементов
        case 'mergeCols':
            $result = mergeCols();
            if ($result['success']) {
                //$result['nextStep'] = 'MergeCols';
                //$result['nextMessage'] = "# Слияние массивов в общий список";
            }
            break;

        // O_o
        default:
            $result = [
                'success' => false,
                'message' => "Неизвестная команда $act",
            ];
    }

// А что, а вдруг?
}catch(Exception $e){
    Log::save(Log::dumpException($e), CONFIG::ERROR_LOG_FILE);
    $result = [
        'success'   => false,
        'message'   => $e->getMessage(),
    ];
}


// Возвращаем результат
echo json_encode($result);

