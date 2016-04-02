<?php
/**
 * Файл подключения общих модулей. Подразумевается, что конфиг подключён ранее
 * User: Виктор
 * Date: 01.03.2016
 * Time: 21:33
 */

/* Base libs */
require_once(CONFIG::ROOT . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'trait.Instances.php');
require_once(CONFIG::ROOT . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'trait.UseDb.php');

require_once(CONFIG::ROOT . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'class.BaseException.php');
require_once(CONFIG::ROOT . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'class.Log.php');
require_once(CONFIG::ROOT . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'class.Db.php');
require_once(CONFIG::ROOT . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'class.ViewVar.php');
require_once(CONFIG::ROOT . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'class.ViewBase.php');
require_once(CONFIG::ROOT . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'class.ViewParser.php');
require_once(CONFIG::ROOT . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'class.ViewTranslator.php');
require_once(CONFIG::ROOT . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'class.ViewInstance.php');
require_once(CONFIG::ROOT . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'class.View.php');
require_once(CONFIG::ROOT . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'class.ErrorHandler.php');





/* External libs */


/* Initialization */

/*
new Db(
    CONFIG::DB_DSN,
    CONFIG::DB_USER,
    CONFIG::DB_PASSWORD,
    [
        Db::ATTR_INSTANCE_INDEX => 'main',
    ]
);
*/
