<?php 
	require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'config.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'class.Filter.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'class.Log.php');

    if (isset($_GET['action']) && ($_GET['action'] == 'clear')){
        fclose(fopen('error.log', 'w'));
        fclose(fopen('db.error.log', 'w'));
        fclose(fopen('db.log', 'w'));
        header('Location: http://' . $_SERVER['SERVER_NAME'] . '/log/');
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <title><?= CONFIG::PAGE_TITLE ?></title>
        <meta charset="<?= CONFIG::PAGE_CHARSET ?>">
        <style>
            div{
                width:            800px;
                max-width:        800px;
                min-height:       100px;
                max-height:       500px;
                overflow-x:       scroll;
                overflow-y:       scroll;
                font-size:        7pt;
                border:           1px dashed;
                padding:          2px 0px 4px 6px;
                background-color: #dddddd;
            }

            pre{
                font-size:  8pt;
            }

            table{
                font-size: 8pt;
            }

            span{
                font-size: 12pt;
            }
        </style>
    </head>
    <body>
        <form name="test" method="post" action="index.php?action=clear">
            <p align=center>
                <input type=submit value='Очистить все логи'>
            </p>
        </form>
        <?php
            print '<br><br><br>ОШБИКИ:<br>';
            print Log::showLogFile('error.log');
            print '<br><br><br>ОШБИКИ БД:<br>';
            print Log::showLogFile('db.error.log');
            print '<br><br><br>ЗАПРОСЫ БД:<br>';
            print Log::showLogFile('db.log');
        ?>
    </body>
</html>


