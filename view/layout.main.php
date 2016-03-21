<!DOCTYPE html>
<html>

<head>
    <title><?= CONFIG::PAGE_TITLE ?></title>
    <meta charset="<?= CONFIG::PAGE_CHARSET ?>">
    <!-- js -->
    <script src="<?= CONFIG::HOST ?>/js/jquery.js"></script>
    <script src="<?= CONFIG::HOST ?>/js/bootstrap.js"></script>
    <script src="<?= CONFIG::HOST ?>/js/common.js"></script>
    <!-- css -->
    <link rel="stylesheet" href="<?= CONFIG::HOST ?>/css/bootstrap.css">
    <link rel="stylesheet" href="<?= CONFIG::HOST ?>/css/common.css">
</head>

<body>
    <div class="container" style="margin-top: 100px;">
        <?= $content ?>
    </div>
</body>
</html>
