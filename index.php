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
    $a = [PDO::FETCH_OBJ, PDO::FETCH_ASSOC, PDO::FETCH_NAMED, PDO::FETCH_BOTH, PDO::FETCH_NUM];
    $stmt = $db->associateQuery('SELECT * FROM help_category', PDO::FETCH_CLASS);
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