<?php
declare(strict_types=1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?= $this->helper->escape((string) ($data['result']['status'] ?? 500)) ?></title>
</head>
<body>
    <main>
        <h1>
            <?= $this->helper->escape((string) ($data['result']['status'] ?? 500)) ?>
            - <?= $this->helper->escape($data['result']['title'] ?? 'Internal Server Error') ?>
        </h1>
        <p><?= $this->helper->escape($data['result']['message'] ?? 'Something unexpected happened.') ?></p>

        <p>Hint: <?= $this->helper->escape($data['hint'] ?? 'If the error persists, contact your administrator') ?></p>

        <?php if (isset($data['action']['url'], $data['action']['label'])): ?>
            <p>
                <a href="<?= $this->helper->escape($data['action']['url']) ?>">
                    <?= $this->helper->escape($data['action']['label']) ?>
                </a>
            </p>
        <?php endif; ?>
    </main>
</body>
</html>
