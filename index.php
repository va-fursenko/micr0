<?php

// Подключаем конфиг
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/lib/class.Log.php');
require_once(__DIR__ . '/lib/class.Db.php');

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


}catch (DbException $ex){
    $db->logException($ex);
}







// Рисуем шаблон
require_once(CONFIG::TPL_DIR . 'layout.main.php');

//preg_match_all('/host=([a-zA-Z0-9._]*)/', 'mysql:host=127.0.0.1;port=6666;password=passw;charset=utf8;username=root', $matches);
//print_r($matches);