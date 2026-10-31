<?php

require_once __DIR__ . '/../app/Repositories/PublicCareersRepository.php';

use Core\Database;
use Repo\PublicCareersRepository;

$admissionRepository = new PublicCareersRepository(Database::connect());
$admissionPosts = $admissionRepository->getCareers();

//Turns a datetime into "a day ago", "3 months ago", "2 years ago", etc.
if (!function_exists('timeAgo')) {
	function timeAgo(?string $datetime): string
	{
		if (empty($datetime)) {
			return 'recently';
		}

		try {
			$then = new DateTimeImmutable($datetime);
		} catch (Exception $e) {
			return 'recently';
		}

		$now = new DateTimeImmutable('now');

		if ($then > $now) {
			return 'just now';
		}

		$diff = $then->diff($now);

		if ($diff->y > 0) {
			return $diff->y === 1 ? 'a year ago' : $diff->y . ' years ago';
		}
		if ($diff->m > 0) {
			return $diff->m === 1 ? 'a month ago' : $diff->m . ' months ago';
		}
		if ($diff->d > 0) {
			return $diff->d === 1 ? 'a day ago' : $diff->d . ' days ago';
		}
		if ($diff->h > 0) {
			return $diff->h === 1 ? 'an hour ago' : $diff->h . ' hours ago';
		}
		if ($diff->i > 0) {
			return $diff->i === 1 ? 'a minute ago' : $diff->i . ' minutes ago';
		}

		return 'just now';
	}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>UCC | Careers</title>
	<link rel="icon" type="image/png" href="images/UCC2.png">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="assets/utility.css">
	<link rel="stylesheet" href="assets/utility-bar.css">
	<link rel="stylesheet" href="assets/navbar.css">
	<link rel="stylesheet" href="assets/footer.css">
	<style>
		html, body { min-height: 100%; }
		body { min-height: 100vh; display: flex; flex-direction: column; }
		.careers-page { flex: 1 0 auto; min-height: 0; }
	</style>
</head>
<body>
	<div class="page-dots" aria-hidden="true">
		<span class="dot-pattern dot-1"></span>
		<span class="dot-pattern dot-2"></span>
		<span class="dot-pattern dot-3"></span>
		<span class="dot-pattern dot-4"></span>
	</div>
	<?php include __DIR__ . '/includes/utility-bar.php'; ?>
	<?php include __DIR__ . '/includes/navbar.php'; ?>
	<!-- CAREERS CONTENT -->
	<main class="careers-page">
		<div class="careers-decoration careers-decoration-1"></div>
		<div class="careers-decoration careers-decoration-2"></div>
		<div class="careers-dot careers-dot-1"></div>
		<div class="careers-dot careers-dot-2"></div>
		<div class="careers-container">
			<section class="careers-header">
				<div class="careers-header-content">
					<h1>CAREERS</h1>
					<div class="careers-title-line">
						<span></span>
						<i></i>
						<span></span>
					</div>
					<p>Explore career opportunities and professional possibilities at the University of Caloocan City.</p>
				</div>
			</section>
			
			
			<?php if (!empty($admissionPosts)): ?>
				<?php foreach ($admissionPosts as $post): ?>
					<section class="careers-post">
						<div class="careers-image-card">
							<img
								src="<?= htmlspecialchars(
									!empty($post['featured_image'])
										? 'image.php?f=' . rawurlencode((string)$post['featured_image'])
										: 'images/UCC2.png',
									ENT_QUOTES,
									'UTF-8'
								) ?>"
								alt="<?= htmlspecialchars($post['title'] ?? 'Careers', ENT_QUOTES, 'UTF-8') ?>"
							>
						</div>
						<div class="careers-post-info">
							<div class="careers-category-row">
								<span class="careers-line"></span>
								<span class="careers-dots">•••</span>
								<span class="careers-category">
									<?= htmlspecialchars(strtoupper($post['name'] ?? 'CAREERS'), ENT_QUOTES, 'UTF-8') ?>
								</span>
								<span class="careers-dots">•••</span>
								<span class="careers-line"></span>
							</div>

							<h2><?= htmlspecialchars($post['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>

							<div class="careers-meta">
								<i data-lucide="calendar-days"></i>
								<span
									title="<?= !empty($post['created_at'])
										? htmlspecialchars(date('F j, Y', strtotime($post['created_at'])), ENT_QUOTES, 'UTF-8')
										: '' ?>"
								>
									Published <?= htmlspecialchars(timeAgo($post['created_at'] ?? null), ENT_QUOTES, 'UTF-8') ?>
								</span>
							</div>

							<div class="careers-post-line"></div>

							<?php if (!empty($post['excerpt'])): ?>
								<p><?= htmlspecialchars($post['excerpt'], ENT_QUOTES, 'UTF-8') ?></p>
							<?php endif; ?>
						</div>
					</section>
				<?php endforeach; ?>
			<?php else: ?>
				<section class="careers-post careers-empty">
					<div class="careers-post-info">
						<h2>NO POST AVAILABLE</h2>
						<p>There are no career postings at the moment. Please check back later.</p>
					</div>
				</section>
			<?php endif; ?>

		</div>
	</main>
	
	<?php include __DIR__ . '/includes/footer.php'; ?>

	<button class="back-top" id="backTop" aria-label="Back to top">↑</button>
	<script src="js/script.js"></script>
	<script src="https://unpkg.com/lucide@latest"></script>
	<script>
		lucide.createIcons();
	</script>
</body>
</html>

