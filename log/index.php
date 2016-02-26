<?php 
	require_once('..' . DIRECTORY_SEPARATOR . 'config.php'); 
	require_once('..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'class.Filter.php'); 
	require_once('..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'class.Log.php'); 
?>
<!DOCTYPE html>
<html>

<head>
    <title><?= CONFIG::PAGE_TITLE ?></title>
    <meta charset="<?= CONFIG::PAGE_CHARSET ?>">
</head>

<body>
<?php

	if (isset($_GET['action']) && ($_GET['action'] == 'clear')){
		fclose(fopen('error.log', 'w'));
		fclose(fopen('db.error.log', 'w'));
		fclose(fopen('db.log', 'w'));
		header('Location: index.php');
	}

?>
<form name="test" method="post" action="index.php?action=clear">
	<div align=center>
		<input type=submit value='Очистить все логи'>
	</div>
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


