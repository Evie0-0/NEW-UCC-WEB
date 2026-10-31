<?php
declare(strict_types=1);

use Core\Database;
use Repo\PublicCommunityRepository;

require_once __DIR__ . '/../app/Repositories/PublicCommunityRepository.php';

// Escapes text for safe HTML output
function er(?string $value): string
{
	return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$communityRepository = new PublicCommunityRepository(Database::connect());

$featured = $communityRepository->getLatestCommunityService();
$others   = $featured !== null
	? $communityRepository->getOtherCommunityServices((int) $featured['id'])
	: [];


function imagePath(?string $file, string $fallback = 'images/UCC2.png'): string
{
	return !empty($file)
		? 'image.php?f=' . rawurlencode($file)
		: $fallback;
}

function postDate(array $post): string
{
	$date = $post['published_at'] ?: ($post['created_at'] ?? '');
	return !empty($date) ? date('F j, Y', strtotime($date)) : '';
}

 
function cardText(array $post, int $limit = 260): string
{
	$text = trim($post['excerpt'] ?? '');
	if ($text === '') {
		$text = trim(preg_replace('/\s+/', ' ', strip_tags($post['content'] ?? '')));
	}
	return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit) . '…' : $text;
}

// Plain-text content turned into safe paragraphs for the modal. 
function modalHtml(array $post): string
{
	$html = '';
	foreach (preg_split('/\R+/', trim($post['content'] ?? '')) as $line) {
		$line = trim($line);
		if ($line !== '') $html .= '<p>' . er($line) . '</p>';
	}
	$date = postDate($post);
	if ($date !== '') {
		$html = '<div class="modal-highlight"><strong>' . er($date) . '</strong></div>' . $html;
	}
	return $html;
}

// Articles handed to the modal script
$articles = [];
foreach (array_filter([$featured, ...$others]) as $post) {
	$articles['post-' . $post['id']] = [
		'category' => strtoupper($post['name'] ?? 'COMMUNITY EXTENSION SERVICES'),
		'title'    => strtoupper($post['title'] ?? ''),
		'content'  => modalHtml($post),
	];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>UCC | Community-Extension Services</title>
	<link rel="icon" type="image/png" href="images/UCC2.png">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="assets/utility.css">
	<link rel="stylesheet" href="assets/community-extension.css">
	<link rel="stylesheet" href="assets/utility-bar.css">
	<link rel="stylesheet" href="assets/navbar.css">
	<link rel="stylesheet" href="assets/footer.css">
	<style>
		html, body { min-height: 100%; }
		body { min-height: 100vh; display: flex; flex-direction: column; }
		.ces-page { flex: 1 0 auto; }
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

	<main class="ces-page">
		<div class="ces-bg ces-bg-1"></div>
		<div class="ces-bg ces-bg-2"></div>
		<section class="ces-container">

			<!-- FEATURED: the latest post -->
			<?php if ($featured !== null): ?>
			<article class="ces-featured-card ces-modal-trigger" data-article="post-<?= (int) $featured['id'] ?>">
				<div class="ces-featured-image">
					<img
						src="<?= er(imagePath($featured['featured_image'])) ?>"
						alt="<?= er($featured['title'] ?? '') ?>"
						fetchpriority="high"
					>
				</div>
				<div class="ces-featured-content">
					<div class="ces-post-meta ces-meta-light">
						<div class="ces-meta-item">
							<i data-lucide="map-pin"></i>
							<div>
								<strong><?= er(postDate($featured)) ?></strong>
								<span><?= er($featured['name'] ?? '') ?></span>
							</div>
						</div>
					</div>
					<h1><?= er(strtoupper($featured['title'] ?? '')) ?></h1>
					<div class="ces-title-line"></div>
					<p><?= er(cardText($featured)) ?></p>
					<a href="#" class="ces-read-more ces-read-light" data-modal-trigger="post-<?= (int) $featured['id'] ?>">
						<span>Read More</span>
						<i data-lucide="arrow-right"></i>
					</a>
				</div>
				<div class="ces-featured-glow"></div>
			</article>
			<?php endif; ?>

			
			<!-- ALL OTHER POSTS -->
			<?php foreach ($others as $post): ?>
			<article class="ces-moa-card ces-modal-trigger" data-article="post-<?= (int) $post['id'] ?>">
				<div class="ces-moa-image">
					<img
						src="<?= er(imagePath($post['featured_image'])) ?>"
						alt="<?= er($post['title'] ?? '') ?>"
						loading="lazy"
						decoding="async"
					>
				</div>
				<div class="ces-moa-content">
					<div class="ces-post-meta ces-meta-dark">
						<div class="ces-meta-item">
							<i data-lucide="map-pin"></i>
							<div>
								<strong><?= er(postDate($post)) ?></strong>
								<span><?= er($post['name'] ?? '') ?></span>
							</div>
						</div>
					</div>
					<h2><?= er(strtoupper($post['title'] ?? '')) ?></h2>
					<div class="ces-title-line ces-line-dark"></div>
					<p><?= er(cardText($post)) ?></p>
					<a href="#" class="ces-read-more ces-read-dark" data-modal-trigger="post-<?= (int) $post['id'] ?>">
						<span>Read More</span>
						<i data-lucide="arrow-right"></i>
					</a>
					<div class="ces-pen-decoration">
						<i data-lucide="pen-line"></i>
					</div>
					<div class="ces-signature-decoration">UCC</div>
				</div>
			</article>
			<?php endforeach; ?>

			<?php if ($featured === null): ?>
				<p>No community extension posts are currently available.</p>
			<?php endif; ?>
		</section>
	</main>

	<!-- COMMUNITY EXTENSION ARTICLE MODAL -->
	<div class="student-modal" id="cesModal" aria-hidden="true">
		<div class="student-modal-backdrop"></div>
		<div class="student-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="cesModalTitle">
			<div class="student-modal-header">
				<div class="student-modal-heading">
					<span class="student-modal-label" id="cesModalCategory">COMMUNITY EXTENSION SERVICES</span>
				</div>
				<button class="student-modal-close" type="button" aria-label="Close" data-modal-close>×</button>
			</div>
			<div class="student-modal-body">
				<div class="student-modal-accent"></div>
				<h2 id="cesModalTitle"></h2>
				<div id="cesModalContent"></div>
			</div>
			<div class="student-modal-footer">
				<span>UNIVERSITY OF CALOOCAN CITY</span>
			</div>
		</div>
	</div>

	<?php include __DIR__ . '/includes/footer.php'; ?>


	<button class="back-top" id="backTop" aria-label="Back to top">↑</button>

	<script src="https://unpkg.com/lucide@0.469.0/dist/umd/lucide.min.js" defer></script>
	<script src="js/script.js" defer></script>
	<script>
		const cesArticles = <?= json_encode($articles, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

		document.addEventListener("DOMContentLoaded", function () {
			if (typeof lucide !== "undefined") lucide.createIcons();

			const cesModal         = document.getElementById("cesModal");
			const cesModalCategory = document.getElementById("cesModalCategory");
			const cesModalTitle    = document.getElementById("cesModalTitle");
			const cesModalContent  = document.getElementById("cesModalContent");
			if (!cesModal) return;

			function openCesModal(articleId) {
				const article = cesArticles[articleId];
				if (!article) return;
				cesModalCategory.textContent = article.category;
				cesModalTitle.textContent    = article.title;
				cesModalContent.innerHTML    = article.content;
				cesModal.classList.add("active");
				cesModal.setAttribute("aria-hidden", "false");
				document.body.classList.add("student-modal-open");
				const modalBody = cesModal.querySelector(".student-modal-body");
				if (modalBody) modalBody.scrollTop = 0;
			}

			function closeCesModal() {
				cesModal.classList.remove("active");
				cesModal.setAttribute("aria-hidden", "true");
				document.body.classList.remove("student-modal-open");
			}

			document.querySelectorAll(".ces-modal-trigger").forEach(card => {
				card.addEventListener("click", function (event) {
					if (event.target.closest("a")) return;
					openCesModal(this.dataset.article);
				});
			});

			document.querySelectorAll("[data-modal-trigger]").forEach(button => {
				button.addEventListener("click", function (event) {
					event.preventDefault();
					event.stopPropagation();
					openCesModal(this.dataset.modalTrigger);
				});
			});

			cesModal.querySelectorAll("[data-modal-close]").forEach(button => {
				button.addEventListener("click", function (event) {
					event.preventDefault();
					event.stopPropagation();
					closeCesModal();
				});
			});

			document.addEventListener("keydown", function (event) {
				if (event.key === "Escape" && cesModal.classList.contains("active")) closeCesModal();
			});
		});
	</script>
</body>
</html>

