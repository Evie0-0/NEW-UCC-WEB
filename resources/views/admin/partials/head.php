<?php
declare(strict_types=1);

$titleSuffix = $data['site']['admin_title_suffix'] ?? '';
$pageTitle = $data['site']['page_title'] ?? '';
$title = $pageTitle . ' | ' . $titleSuffix;
$files = $data['files'] ?? [];
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->helper->escape($title) ?></title>

    <?php foreach ((array) ($files['css'] ?? []) as $path): ?>
        <link rel="stylesheet" href="<?= $this->helper->escape($this->asset('admin', $path)) ?>">
    <?php endforeach; ?>

    <?php foreach ((array) ($files['js'] ?? []) as $path): ?>
        <script type="module" src="<?= $this->helper->escape($this->asset('admin', $path)) ?>"></script>
    <?php endforeach; ?>
</head>
