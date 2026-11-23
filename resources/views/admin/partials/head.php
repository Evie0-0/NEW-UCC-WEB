<?php
declare(strict_types=1);

$titleSuffix = $data['site']['admin_title_suffix'] ?? '';
$pageTitle = $data['site']['page_title'] ?? '';
$title = $pageTitle . ' | ' . $titleSuffix;
$css = $data['files']['css'] ?? null;
$js = $data['files']['js'] ?? null;
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->helper->escape($title) ?></title>

    <?php if (is_string($css) && $css !== ''): ?>
        <link rel="stylesheet" href="<?= $this->helper->escape($this->asset($css)) ?>">
    <?php endif; ?>
    <?php if (is_string($js) && $js !== ''): ?>
        <script type="module" src="<?= $this->helper->escape($this->asset($js)) ?>"></script>
    <?php endif; ?>
</head>
