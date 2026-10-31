<?php

declare(strict_types=1);

use Core\Database;
use Repo\PublicExecutiveOfficialsRepository;

require_once __DIR__ . '/../app/Repositories/PublicExecutiveOfficialsRepository.php';

$officialsRepository = new PublicExecutiveOfficialsRepository(Database::connect());

$president = $officialsRepository->getUniversityPresident();
$executiveOfficials = $officialsRepository->getAllExecOfficials();

function executiveImagePath(?string $filename): string
{
	return !empty($filename)
		? 'image.php?f=' . rawurlencode($filename)
		: 'images/UCC2.png';
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
	<style>
		html,
		body {
			min-height: 100%;
		}

		body {
			min-height: 100vh;
			display: flex;
			flex-direction: column;
		}

		.executive-page {
			flex: 1 0 auto;
		}
	</style>
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

	<!-- EXECUTIVE OFFICIALS PAGE -->
	<main class="executive-page">
		<!-- PAGE HERO -->
		<section class="executive-hero">
			<div class="container executive-hero-content">
				<div class="executive-eyebrow">
					<span>UCC LEADERSHIP</span>
				</div>
				<h1>EXECUTIVE OFFICIALS</h1>
			</div>
		</section>

		<!-- UNIVERSITY PRESIDENT -->
		<?php if ($president !== null): ?>
			<section class="executive-president-section">
				<div class="container">
					<div class="executive-president-card">
						<div class="executive-president-photo">
							<img
								src="<?= htmlspecialchars(executiveImagePath($president['featured_image'] ?? null), ENT_QUOTES, 'UTF-8') ?>"
								alt="<?= htmlspecialchars($president['name'] ?? 'University President', ENT_QUOTES, 'UTF-8') ?>"
							>
						</div>

						<div class="executive-president-content">
							<div class="executive-president-copy">
								<div class="executive-card-label">
									THE UNIVERSITY PRESIDENT
								</div>

								<h2><?= htmlspecialchars($president['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>

								<div class="executive-gold-line"></div>

								<p class="executive-position">
									<?= htmlspecialchars($president['position'] ?? '', ENT_QUOTES, 'UTF-8') ?>
								</p>
							</div>

							<div class="executive-decoration"></div>
						</div>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<!-- UNIVERSITY EXECUTIVE OFFICIALS -->
		<section class="executive-officials-section">
			<div class="container">
				<div class="executive-heading">
					<div class="executive-heading-line"></div>
					<h2>UNIVERSITY EXECUTIVE OFFICIALS</h2>
					<div class="executive-heading-line"></div>
				</div>

				<div class="executive-grid">
				<?php if (!empty($executiveOfficials)): ?>
					<?php foreach ($executiveOfficials as $official): ?>
						<article class="executive-card">
							<div class="executive-photo">
								<img
									src="<?= htmlspecialchars(executiveImagePath($official['featured_image'] ?? null), ENT_QUOTES, 'UTF-8') ?>"
									alt="<?= htmlspecialchars($official['name'] ?? 'Executive official', ENT_QUOTES, 'UTF-8') ?>"
								>

								<span class="executive-number">
									<?= (int) ($official['order_number'] ?? 0) ?>
								</span>
							</div>

							<div class="executive-role">
								<?= htmlspecialchars($official['position'] ?? '', ENT_QUOTES, 'UTF-8') ?>
							</div>

							<div class="executive-info">
								<h3><?= htmlspecialchars($official['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
								<span><?= htmlspecialchars($official['biography'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
							</div>
						</article>
					<?php endforeach; ?>
				<?php else: ?>
					<p class="no-executive-officials">No executive officials available.</p>
				<?php endif; ?>
			</div>
		</div>
		</section>
	</main>

	<?php require_once __DIR__ . '/includes/footer.php'; ?>

	<button class="back-top" id="backTop" aria-label="Back to top">↑</button>
	<script src="js/script.js"></script>
	<script src="https://unpkg.com/lucide@latest"></script>
	<script>lucide.createIcons();</script>
</body>
</html>