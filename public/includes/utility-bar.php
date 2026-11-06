
<?php
$currentPage = basename($_SERVER['PHP_SELF']);

$isNewsActive = in_array($currentPage, ['news.php', 'postArticle.php'], true);
?>

<div class="utility-bar">
    <div class="utility-inner">
        <div class="utility-links">

            <a href="e-services.php" class="<?= $currentPage === 'e-services.php' ? 'active' : '' ?>">E-SERVICES</a>

            <a href="student-life.php" class="<?= $currentPage === 'student-life.php' ? 'active' : '' ?>">STUDENT LIFE</a>

            <a href="community-extension.php" class="<?= $currentPage === 'community-extension.php' ? 'active' : '' ?>">COMMUNITY EXTENSION SERVICES</a>

            <a href="admission.php" class="<?= $currentPage === 'admission.php' ? 'active' : '' ?>">ADMISSION</a>

            <a href="news.php" class="<?= $isNewsActive ? 'active' : '' ?>">NEWS</a>

            <a href="alumni.php" class="<?= $currentPage === 'alumni.php' ? 'active' : '' ?>">ALUMNI</a>

            <a href="careers.php" class="<?= $currentPage === 'careers.php' ? 'active' : '' ?>">CAREERS</a>

        </div>
    </div>
</div>
