<?php

// Подключаем конфиг
require_once(__DIR__ . '/config.php');
require_once(CONFIG::ROOT . '/lib/inc.base.php');


$content = 'Hello';


try {
    $db = new Db(
        CONFIG::DB_DSN,
        CONFIG::DB_USER,
        CONFIG::DB_PASSWORD
    );
    echo Log::printObject($db->db);
    $stmt = $db->associateQuery("SELECT * FROM help_category LIMIT 0, 50", PDO::FETCH_NAMED);
    $content = Log::printObject($stmt, true);

    //$content = $db->getServerInfo();
   // $content = $db->getAttribute(PDO::ATTR_DRIVER_NAME  );
    echo Log::printObject(Db::getInstance(), true);

}catch (DbException $ex){
    $ex->toLog('Что-то сломалось при подключении');
}






/*
function fileExt($filename){
    $result = explode('.', $filename);
    return is_array($result) && count($result) > 1 ? end($result) : '';
}
*/


// Рисуем шаблон
require_once(CONFIG::ROOT . DIRECTORY_SEPARATOR . CONFIG::TPL_DIR . '/layout.main.php');