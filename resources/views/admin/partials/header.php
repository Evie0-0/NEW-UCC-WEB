<?php
declare(strict_types=1);

$pageTitle = $data['site']['page_title'] ?? '';
$username = $data['user']['username'] ?? '';
$role = $data['user']['role'] ?? '';
?>

<header>
    <h1><?= $this->helper->escape($pageTitle) ?></h1>
    <p>
        <?= $this->helper->escape($username) ?>
        -
        <?= $this->helper->escape($role) ?>
    </p>
</header>
