<?php
$pageTitle = $pageTitle ?? 'Admin';
?>

<header class="top-header">
    <div class="header-left">
        <h1 id="pageTitle"><?= htmlspecialchars($pageTitle) ?></h1>
    </div>
    <div class="header-right">
        <a href="/../../homepage.php" class="visit-website-btn">
            <i data-lucide="external-link"></i>
            <span>Visit Website</span>
        </a>
        <div class="admin-profile">
            <div class="admin-text">
                <small>Administrator</small>
                <span>Welcome, Admin!</span>
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