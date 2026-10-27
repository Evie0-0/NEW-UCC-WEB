<?php
$currentPage = strtolower(basename($_SERVER['SCRIPT_NAME']));

$academicsLinks = [
    'units.php'            => 'Units',
    'programs.php'         => 'Programs',
    'unitinfo.php' => 'Unit Information',
    'unitpost.php'        => 'Unit News',
    'programposts.php'     => 'Program News',
];
$officialsLinks = [
    'executive.php' => 'Executive Officials',
    'regents.php'    => 'Board of Regents',
];
$componentsLinks = [
    'campuses.php'            => 'Campuses',
    'contact.php' => 'Contact Information',
    'about.php'           => 'About UCC',
    'eservices.php'          => 'E-Services',
    'archive.php'             => 'Archive',
    'quicklinks.php'         => 'Quick Links',
    'calendar.php'            => 'Calendar',
];

$isActive = function (string $file) use ($currentPage): bool {
    return strtolower($file) === $currentPage;
};
$groupHasActive = function (array $links) use ($currentPage): bool {
    foreach (array_keys($links) as $file) {
        if (strtolower($file) === $currentPage) {
            return true;
        }
    }
    return false;
};
$renderGroup = function (string $icon, string $label, array $links) use ($isActive, $groupHasActive): void {
    $open = $groupHasActive($links);
    ?>
        <div class="menu-group<?= $open ? ' open' : '' ?>">
            <button class="menu-title<?= $open ? ' active' : '' ?>" type="button" aria-expanded="<?= $open ? 'true' : 'false' ?>">
                <div>
                    <i data-lucide="<?= $icon ?>"></i>
                    <span><?= htmlspecialchars($label) ?></span>
                </div>
                <i data-lucide="chevron-down" class="arrow"></i>
            </button>
            <div class="submenu">
                <?php foreach ($links as $file => $text): ?>
                <a href="<?= $file ?>"<?= $isActive($file) ? ' class="active"' : '' ?>><?= htmlspecialchars($text) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php
};
?>
<aside class="sidebar">
    <div class="sidebar-top">
        <div class="logo-wrap">
            <img src="assets/images/ucc-LOGO.png" alt="UCC Logo">
            <div>
                <h2>UCC Admin</h2>
                <p>University of Caloocan City</p>
            </div>
        </div>
    </div>
    <div class="gold-line"></div>
    <nav class="sidebar-nav">
        <a href="Dashboard.php"<?= $isActive('Dashboard.php') ? ' class="active"' : '' ?>>
            <i data-lucide="house"></i>
            <span>Dashboard</span>
        </a>
        <a href="Posts.php"<?= $isActive('Posts.php') ? ' class="active"' : '' ?>>
            <i data-lucide="newspaper"></i>
            <span>Posts</span>
        </a>

        <?php $renderGroup('graduation-cap', 'Academics', $academicsLinks); ?>
        <?php $renderGroup('users', 'Officials', $officialsLinks); ?>
        <?php $renderGroup('building-2', 'Components', $componentsLinks); ?>

        <a href="User-Management.php"<?= $isActive('User-Management.php') ? ' class="active"' : '' ?>>
            <i data-lucide="user"></i>
            <span>User Management</span>
        </a>
        <a href="audit.php"<?= $isActive('audit.php') ? ' class="active"' : '' ?>>
            <i data-lucide="scroll-text"></i>
            <span>Audit Logs</span>
        </a>
    </nav>
    <div class="sidebar-bottom">
        <a href="Password.php"<?= $isActive('Password.php') ? ' class="active"' : '' ?>>
            <i data-lucide="lock"></i>
            <span>Password</span>
        </a>
        <a href="Logout.php">
            <i data-lucide="log-out"></i>
            <span>Logout</span>
        </a>
    </div>
    <div class="sidebar-art"></div>
</aside>