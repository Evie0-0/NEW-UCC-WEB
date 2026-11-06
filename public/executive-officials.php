<?php
declare(strict_types=1);
use Core\Database;
use Repo\PublicExecutiveOfficialsRepository;
require_once __DIR__ . '/../app/Repositories/PublicExecutiveOfficialsRepository.php';
$officialsRepository = new PublicExecutiveOfficialsRepository(Database::connect());
$president = $officialsRepository->getUniversityPresident();
$executiveOfficials = $officialsRepository->getAllExecOfficials();
/* Ensure the lowest order number appears first */
if (!empty($executiveOfficials)) {
	usort($executiveOfficials, static function ($a, $b) {
		return (int) ($a['order_number'] ?? PHP_INT_MAX) <=> (int) ($b['order_number'] ?? PHP_INT_MAX);
	});
}
/* Separate the first executive official as a featured card */
$featuredOfficial = !empty($executiveOfficials)
	? array_shift($executiveOfficials)
	: null;
function executiveImagePath(?string $filename): string
{
	return !empty($filename)
		? 'image.php?f=' . rawurlencode($filename)
		: 'images/UCC2.png';
}
function executiveEscape(?string $value): string
{
	return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>UCC | Executive Officials</title>
	<link rel="icon" type="image/png" href="images/UCC2.png">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="assets/About.css">
	<link rel="stylesheet" href="assets/utility-bar.css">
	<link rel="stylesheet" href="assets/navbar.css">
	<link rel="stylesheet" href="assets/footer.css">
</head>
<body>
	<div class="page-dots" aria-hidden="true">
		<span class="dot-pattern dot-1"></span>
		<span class="dot-pattern dot-2"></span>
		<span class="dot-pattern dot-3"></span>
		<span class="dot-pattern dot-4"></span>
	</div>
	<?php require_once __DIR__ . '/includes/utility-bar.php'; ?>
	<?php require_once __DIR__ . '/includes/navbar.php'; ?>
	<main class="executive-page board-page">
		<!-- PAGE HERO -->
		<section class="executive-hero board-hero">
			<div class="executive-hero-content board-hero-content">
				<div class="executive-eyebrow">UCC LEADERSHIP</div>
				<h1>EXECUTIVE OFFICIALS</h1>
				<p>Meet the individuals leading the University of Caloocan City.</p>
			</div>
		</section>
		<!-- FEATURED UNIVERSITY PRESIDENT -->
		<?php if ($president !== null): ?>
			<section class="executive-president-section chairman-section">
				<div class="container">
					<article class="executive-president-card chairman-card">
						<div class="executive-president-photo chairman-photo">
							<img
								src="<?= executiveEscape(executiveImagePath($president['featured_image'] ?? null)) ?>"
								alt="<?= executiveEscape($president['name'] ?? 'University President') ?>"
							>
						</div>
						<div class="executive-president-content chairman-content">
							<div class="executive-president-copy chairman-copy">
								<div class="executive-card-label">
									<span class="executive-label-dot"></span>
									UCC LEADERSHIP
								</div>
								<h2><?= executiveEscape($president['name'] ?? '') ?></h2>
								<div class="executive-gold-line"></div>
								<p class="executive-position chairman-position">
									<?= executiveEscape($president['position'] ?? 'UNIVERSITY PRESIDENT') ?>
								</p>
								<span class="president-caption">UNIVERSITY OF CALOOCAN CITY</span>
							</div>
							<div class="executive-decoration chairman-decoration"></div>
						</div>
					</article>
				</div>
			</section>
		<?php endif; ?>
		<!-- EXECUTIVE OFFICIALS SECTION -->
		<section class="executive-officials-section regents-section">
			
<div class="container">
	<!-- FIRST EXECUTIVE OFFICIAL - FEATURED CARD -->
	<?php if ($featuredOfficial !== null): ?>
		<div class="executive-first-featured">
			<article class="executive-president-card chairman-card">
				<div class="executive-president-photo chairman-photo">
					<img
						src="<?= executiveEscape(executiveImagePath($featuredOfficial['featured_image'] ?? null)) ?>"
						alt="<?= executiveEscape($featuredOfficial['name'] ?? 'Executive Official') ?>"
					>
				</div>
				<div class="executive-president-content chairman-content">
					<div class="executive-president-copy chairman-copy">
						<div class="executive-card-label">
							<span class="executive-label-dot"></span>
							EXECUTIVE LEADERSHIP
						</div>
						<h2><?= executiveEscape($featuredOfficial['name'] ?? '') ?></h2>
						<div class="executive-gold-line"></div>
						<p class="executive-position chairman-position">
							<?= executiveEscape($featuredOfficial['position'] ?? 'Executive Official') ?>
						</p>
						<?php if (!empty($featuredOfficial['biography'])): ?>
							<p class="executive-featured-biography">
								<?= nl2br(executiveEscape($featuredOfficial['biography'])) ?>
							</p>
						<?php endif; ?>
						<span class="president-caption">UNIVERSITY OF CALOOCAN CITY</span>
					</div>
					<div class="executive-decoration chairman-decoration"></div>
				</div>
			</article>
		</div>
	<?php endif; ?>
	<!-- MEMBERS HEADING - NOW BELOW THE FEATURED CARD -->
	<div class="regents-heading">
		<div class="regents-heading-line"></div>
		<h2>MEMBERS OF THE EXECUTIVE OFFICIALS</h2>
		<div class="regents-heading-line"></div>
	</div>
	<!-- REMAINING EXECUTIVE OFFICIALS - TIMELINE -->
	<div class="regents-grid">
		<?php if (!empty($executiveOfficials)): ?>
			<?php foreach ($executiveOfficials as $index => $official): ?>
				<article class="regent-card">
					<div class="regent-photo">
						<img
							src="<?= executiveEscape(executiveImagePath($official['featured_image'] ?? null)) ?>"
							alt="<?= executiveEscape($official['name'] ?? 'Executive Official') ?>"
							loading="lazy"
						>
					</div>
					<div class="regent-number">
						<?= sprintf('%02d', (int) ($official['order_number'] ?? ($index + 2))) ?>
					</div>
					<div class="regent-role">EXECUTIVE LEADERSHIP</div>
					<div class="regent-info">
						<h3><?= executiveEscape($official['name'] ?? '') ?></h3>
						<small><?= executiveEscape($official['position'] ?? 'Executive Official') ?></small>
						<?php if (!empty($official['biography'])): ?>
							<small><?= nl2br(executiveEscape($official['biography'])) ?></small>
						<?php else: ?>
							<small>
								Serves as an executive official, contributing
								to the administration and development of
								the University of Caloocan City.
							</small>
						<?php endif; ?>
						<span>UNIVERSITY OF CALOOCAN CITY</span>
					</div>
				</article>
			<?php endforeach; ?>
		<?php elseif ($featuredOfficial === null): ?>
			<div class="no-executive-officials">
				<span>✦</span>
				<h3>No Executive Officials Available</h3>
				<p>Executive leadership information will be displayed here once available.</p>
			</div>
		<?php endif; ?>
	</div>
	<div class="executive-bottom-decoration">
		<span></span>
		<p>LEADERSHIP • SERVICE • EXCELLENCE</p>
		<span></span>
	</div>
</div>

</section>
	</main>
	<?php require_once __DIR__ . '/includes/footer.php'; ?>
	<button class="back-top" id="backTop" aria-label="Back to top">↑</button>
	<script src="js/script.js"></script>
	<script src="https://unpkg.com/lucide@latest"></script>
	<script>
		if (window.lucide) {
			lucide.createIcons();
		}
	</script>
</body>
</html>