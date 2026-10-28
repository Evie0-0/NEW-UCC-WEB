<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/app/Support/helper.php';

$username = $username;
$role = $role;

$pageTitle = $pageTitle;
?>
<header class="top-header">
    <div class="header-left">
        <h1 id="pageTitle"><?= e($pageTitle) ?></h1>
    </div>
    <div class="header-right">
        <a href="../index.php" class="visit-website-btn">
            <i data-lucide="external-link"></i>
            <span>Visit Website</span>
        </a>
        <div class="admin-profile">
            <div class="admin-text">
                <small><?= e($role) ?></small>
                <span>Welcome, <?= e($username) ?>!</span>
            </div>
            <div class="avatar">
                <i data-lucide="circle-user-round"></i>
            </div>
        </div>
    </div>
    <div class="header-art"></div>
    <div class="gold-divider">
        <span></span>
    </div>
</header>
