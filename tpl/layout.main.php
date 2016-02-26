<!DOCTYPE html>
<html>

<head>
    <title><?= CONFIG::PAGE_TITLE ?></title>
    <meta charset="<?= CONFIG::PAGE_CHARSET ?>">
    <!-- js -->
    <script src="<?= CONFIG::ROOT ?>js/jquery.js"></script>
    <script src="<?= CONFIG::ROOT ?>js/bootstrap.js"></script>
    <script src="<?= CONFIG::ROOT ?>js/common.js"></script>
    <!-- css -->
    <link rel="stylesheet" href="<?= CONFIG::ROOT ?>css/bootstrap.css">
    <link rel="stylesheet" href="<?= CONFIG::ROOT ?>css/common.css">
</head>

<body>
    <?= $content ?>
</body>
</html>
