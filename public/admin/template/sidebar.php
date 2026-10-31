<?php
$currentPage = strtolower(basename($_SERVER['SCRIPT_NAME']));

$academicsLinks = [
    'units.php'        => 'Units',
    'programs.php'     => 'Programs',
    'unitinfo.php'     => 'Unit Information',
    'unitpost.php'     => 'Unit Posts',
    'programposts.php' => 'Program Posts',
];
$officialsLinks = [
    'executive.php' => 'Executive Officials',
    'regents.php'   => 'Board of Regents',
];
$componentsLinks = [
    'campuses.php'  => 'Campuses',
    'contact.php'   => 'Contact Information',
    'about.php'     => 'About UCC',
    'eservices.php' => 'E-Services',
    'archive.php'   => 'Archive',
    'quicklinks.php' => 'Quick Links',
    'calendar.php'  => 'Calendar',
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
        <a href="dashboard.php"<?= $isActive('dashboard.php') ? ' class="active"' : '' ?>>
            <i data-lucide="house"></i>
            <span>Dashboard</span>
        </a>

        <a href="posts.php"<?= $isActive('posts.php') ? ' class="active"' : '' ?>>
            <i data-lucide="newspaper"></i>
            <span>Posts</span>
        </a>

        <?php $renderGroup('graduation-cap', 'Academics', $academicsLinks); ?>
        <?php $renderGroup('users', 'Officials', $officialsLinks); ?>
        <?php $renderGroup('building-2', 'Components', $componentsLinks); ?>

        <a href="users.php"<?= $isActive('users.php') ? ' class="active"' : '' ?>>
            <i data-lucide="user"></i>
            <span>User Management</span>
        </a>
        <a href="audit.php"<?= $isActive('audit.php') ? ' class="active"' : '' ?>>
            <i data-lucide="scroll-text"></i>
            <span>Audit Trail</span>
        </a>
    </nav>
    <div class="sidebar-bottom">
        <a href="password.php"<?= $isActive('password.php') ? ' class="active"' : '' ?>>
            <i data-lucide="lock"></i>
            <span>Password</span>
        </a>
        <a href="logout.php">
            <i data-lucide="log-out"></i>
            <span>Logout</span>
        </a>
    </div>
    <div class="sidebar-art"></div>
</aside>
<script>
(function () {
    var sidebar = document.querySelector('.sidebar');
    if (!sidebar || sidebar.dataset.menuReady) { return; }
    sidebar.dataset.menuReady = '1';
    sidebar.addEventListener('click', function (e) {
        var button = e.target.closest('.menu-title');
        if (!button || !sidebar.contains(button)) { return; }
        e.preventDefault();
        e.stopPropagation();
        var group = button.closest('.menu-group');
        var isOpen = group.classList.toggle('open');
        button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }, true);
    if (window.lucide) { window.lucide.createIcons(); }
})();
</script>