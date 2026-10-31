<?php

require_once __DIR__ . '/../app/Repositories/PublicAboutRepository.php';

use Core\Database;
use Repo\PublicAboutRepository;

$aboutRepository = new PublicAboutRepository(Database::connect());

$aboutPosts         = $aboutRepository->getAbout();
$historyPosts       = $aboutRepository->getHistory();
$newUccPosts        = $aboutRepository->getNewUCC();
$opportunitiesPosts = $aboutRepository->getOpportunities();

$about = $aboutPosts[0] ?? null;

if (!function_exists('er')) {
	function er(?string $value): string
	{
		return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
	}
}

// About UCC intro keeps its existing image location and fallback.
function imagePath(?string $file, string $fallback = ''): string
{
	return !empty($file) ? 'admin/assets/images/' . rawurlencode($file) : $fallback;
}

// Uploaded images in other About sections are served from storage/uploads.
function uploadedImagePath(?string $file): string
{
	return !empty($file) ? 'image.php?f=' . rawurlencode($file) : '';
}
// Splits a multi-line description into trimmed, non-empty lines. 
function descriptionLines(?string $text): array
{
	return array_values(array_filter(array_map('trim', preg_split('/\R+/', trim($text ?? '')))));
}

// Card icon taken from the row's featured_image (lazy loaded). 
function iconImage(array $item): void
{
	if (empty($item['featured_image'])) return;
	?>
	<img
		src="<?= er(uploadedImagePath($item['featured_image'])) ?>"
		alt=""
		loading="lazy"
		decoding="async"
		width="48" height="48"
	>
	<?php
}

$introLines = descriptionLines($about['description'] ?? '');
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>UCC | About</title>
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


	<main class="about-page">
		<!-- HERO -->
		<section class="about-hero">
			<div class="about-hero-overlay"></div>
			<div class="container about-hero-content">
				<span class="about-eyebrow">ABOUT UCC <span class="eyebrow-dots">•••</span></span>
				<h1>ABOUT THE<span>UNIVERSITY OF CALOOCAN CITY</span></h1>
			</div>
		</section>



		<!-- ABOUT UCC INTRODUCTION (above the fold: loads eagerly) -->
		<section id="about-ucc" class="ucc-introduction">
			<div class="container ucc-intro-grid">
				<div class="ucc-intro-image">
					<img
						src="<?= er(imagePath($about['featured_image'] ?? null, 'images/UCC-ABOUT.png')) ?>"
						alt="University of Caloocan City building"
						fetchpriority="high"
					>
					<div class="ucc-seal">
						<img src="images/UCC2.png" alt="University of Caloocan City seal">
					</div>
				</div>
				<div class="ucc-intro-divider"></div>
				<div class="ucc-intro-text">
					<?php if (!empty($introLines)): ?>
						<?php foreach ($introLines as $i => $line): ?>
							<?php if ($i === 0): ?>
								<p><strong><?= er($line) ?></strong></p>
							<?php else: ?>
								<p><?= er($line) ?></p>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php else: ?>
						<p>No information available.</p>
					<?php endif; ?>
				</div>
			</div>
		</section>



		<!-- UNIVERSITY HISTORY -->
		<section id="history" class="history-section">
			<div class="container">
				<div class="section-heading centered">
					<span>UNIVERSITY HISTORY</span>
					<i></i>
				</div>
				<h2 class="section-title centered">UCC HISTORICAL DEVELOPMENT</h2>
				<div class="history-timeline">
					<?php if (!empty($historyPosts)): ?>
						<?php foreach ($historyPosts as $index => $item): ?>
							<?php $hasImage = !empty($item['featured_image']); ?>
							<div class="history-item<?= $hasImage ? ' has-image' : '' ?>">
								<div class="history-number"><?= $index + 1 ?></div>
								<div class="history-year"><?= er($item['name'] ?? '') ?></div>
								<?php if ($hasImage): ?>
									<p><?= er($item['description'] ?? '') ?></p>
									<div class="history-image">
										<img
											src="<?= er(uploadedImagePath($item['featured_image'])) ?>"
											alt="<?= er($item['name'] ?? 'UCC history') ?>"
											loading="lazy"
											decoding="async"
											width="600" height="400"
										>
									</div>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					<?php else: ?>
						<p>No history available.</p>
					<?php endif; ?>
				</div>
			</div>
		</section>



		<!-- THE NEW UCC -->
		<section class="new-ucc-section">
			<div class="container">
				<div class="section-heading">
					<span>THE NEW UCC</span>
					<i></i>
				</div>
				<div class="new-ucc-grid">
					<?php if (!empty($newUccPosts)): ?>
						<?php foreach ($newUccPosts as $item): ?>
							<article class="info-card">
								<div class="card-icon"><?php iconImage($item); ?></div>
								<div>
									<h3><?= er(strtoupper($item['name'] ?? '')) ?></h3>
									<p><?= er($item['description'] ?? '') ?></p>
								</div>
							</article>
						<?php endforeach; ?>
					<?php else: ?>
						<p>No information available.</p>
					<?php endif; ?>
				</div>
			</div>
		</section>



		<!-- PROGRESS AND OPPORTUNITIES -->
		<section class="progress-section">
			<div class="container">
				<div class="section-heading centered">
					<span>PROGRESS &amp; OPPORTUNITIES</span>
					<i></i>
				</div>
				<div class="progress-grid">
					<?php if (!empty($opportunitiesPosts)): ?>
						<?php foreach ($opportunitiesPosts as $item): ?>
							<?php $lines = descriptionLines($item['description'] ?? ''); ?>
							<article class="progress-card">
								<div class="progress-icon"><?php iconImage($item); ?></div>
								<div>
									<h3><?= er(strtoupper($item['name'] ?? '')) ?></h3>
									<?php if (!empty($lines)): ?>
										<p><?= er($lines[0]) ?></p>
										<?php if (count($lines) > 1): ?>
											<ul>
												<?php foreach (array_slice($lines, 1) as $line): ?>
													<li><?= er($line) ?></li>
												<?php endforeach; ?>
											</ul>
										<?php endif; ?>
									<?php endif; ?>
								</div>
							</article>
						<?php endforeach; ?>
					<?php else: ?>
						<p>No information available.</p>
					<?php endif; ?>
				</div>
			</div>
		</section>



		<!-- QUOTE -->
		<section class="about-quote">
			<div class="container">
				<div class="quote-mark">“</div>
				<p>Basta sa Caloocan, walang batang maiiwan</p>
				<div class="quote-mark quote-right">”</div>
			</div>
		</section>
	</main>


	<?php require_once __DIR__ . '/includes/footer.php'; ?>


	<button class="back-top" id="backTop" aria-label="Back to top">↑</button>
	<script src="js/script.js" defer></script>
</body>
</html>