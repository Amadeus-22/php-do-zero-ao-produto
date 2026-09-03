<?php use App\Support\View; ?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::e($titulo ?? 'CRM') ?></title>
    <meta name="csrf-token" content="<?= View::e(\App\Support\Csrf::token()) ?>">
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
    <?php require __DIR__ . '/../partials/nav.php'; ?>
    <?php require __DIR__ . '/../partials/flash.php'; ?>

    <main class="container">
        <?= $content ?>
    </main>
</body>
</html>
