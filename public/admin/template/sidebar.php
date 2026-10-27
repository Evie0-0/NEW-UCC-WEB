<?php
declare(strict_types=1);

use Serv\UserServices;

require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/Support/helper.php';

$currentPage = strtolower(basename($_SERVER['SCRIPT_NAME'] ?? ''));

$isActive = static fn (string $file): bool => strtolower($file) === $currentPage;

$academicsLinks = [
    'units.php'        => 'Units',
    'programs.php'     => 'Programs',
    'unit-info.php'     => 'Unit Information',
    'unit-post.php'     => 'Unit Posts',
    'program-posts.php' => 'Program Posts',
];
$officialsLinks = [
    'executive.php' => 'Executive Officials',
    'regents.php'   => 'Board of Regents',
];
$componentsLinks = [
    'campuses.php'   => 'Campuses',
    'contact.php'    => 'Contact Information',
    'about.php'      => 'About UCC',
    'eservices.php'  => 'E-Services',
    'archive.php'    => 'Archive',
    'quick-links.php' => 'Quick Links',
    'calendar.php'   => 'Calendar',
];

$renderLink = static function (string $file, string $label, string $icon = '') use ($isActive): void {
    $active = $isActive($file);

    ?>

    <a href="<?= e($file) ?>"<?= $active ? ' class="active" aria-current="page"' : '' ?>>
        <?php if ($icon !== ''): ?>
            <i data-lucide="<?= e($icon) ?>"></i>
        <?php endif; ?>

        <span><?= e($label) ?></span>
    </a>

    <?php
};

$renderGroup = static function (array $links, string $label, string $icon) use ($currentPage): void {
    $open = in_array($currentPage, array_map('strtolower', array_keys($links)), true);
    $groupId = 'menu-' . preg_replace('/[^a-z0-9-]/', '-', strtolower($label));

    ?>

    <div class="menu-group<?= $open ? ' open' : '' ?>">
        <button class="menu-title<?= $open ? ' active' : '' ?>" type="button"
                aria-expanded="<?= $open ? 'true' : 'false' ?>" aria-controls="<?= e($groupId) ?>">
            <div>
                <i data-lucide="<?= e($icon) ?>"></i>
                <span><?= e($label) ?></span>
            </div>
            <i data-lucide="chevron-down" class="arrow"></i>
        </button>
        <div class="submenu" id="<?= e($groupId) ?>">
            <?php foreach ($links as $file => $text): $active = strtolower($file) === $currentPage; ?>
                <a href="<?= e($file) ?>"<?= $active ? ' class="active" aria-current="page"' : '' ?>>
                    <?= e($text) ?>
                </a>
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
                <h2 style="cursor: default;">UCC Admin</h2>
                <p style="cursor: default;">University of Caloocan City</p>
            </div>
        </div>
    </div>
    <div class="gold-line"></div>
    <nav class="sidebar-nav" aria-label="Admin navigation">
        <?php
        if (UserServices::isAuthorized([UserServices::SUPERADMIN_KEY])) {
            $renderLink('index.php', 'Dashboard', 'house');
            $renderLink('posts.php', 'Posts', 'newspaper');
            $renderGroup($academicsLinks, 'Academics', 'graduation-cap');
            $renderGroup($officialsLinks, 'Officials', 'users');
            $renderGroup($componentsLinks, 'Components', 'building-2');
            $renderLink('users.php', 'User Management', 'user');
            $renderLink('audit.php', 'Audit Trail', 'scroll-text');
        } elseif (UserServices::isAuthorized([UserServices::DEAN_KEY])) {
            $renderLink('index.php', 'Dashboard', 'house');
            $renderLink('unit-info.php', 'Unit Information', 'book-open-text');
            $renderLink('unit-posts.php', 'Unit Posts', 'newspaper');
        } elseif (UserServices::isAuthorized([UserServices::PROGRAM_HEAD_KEY])) {
            $renderLink('index.php', 'Dashboard', 'house');
            $renderLink('program-posts.php', 'Program Posts', 'newspaper');
        }
        ?>
    </nav>
    <div class="sidebar-bottom">
        <?php
            $renderLink('password.php', 'Password', 'lock');
            $renderLink('logout.php', 'Logout', 'log-out');
        ?>
    </div>
    <div class="sidebar-art"></div>
</aside>

<script>
document.querySelectorAll('.sidebar .menu-title').forEach(button => {
    button.addEventListener('click', () => {
        const group = button.closest('.menu-group');
        if (!group) return;

        if (group.querySelector('.submenu a[aria-current="page"]')) return;

        const open = group.classList.toggle('open');
        button.setAttribute('aria-expanded', String(open));
    });
});
</script>
