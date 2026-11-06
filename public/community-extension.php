<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/Repositories/PublicCommunityRepository.php';
use Core\Database;
use Repo\PublicCommunityRepository;
if (!function_exists('timeAgo')) {
	function timeAgo(?string $datetime): string {
		if (!$datetime) return 'Recently published';
		try {
			$date = new DateTime($datetime);
			$now = new DateTime();
			$diff = $now->diff($date);
			if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
			if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
			if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
			if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
			if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
			return 'Just now';
		} catch (Throwable $e) {
			return 'Recently published';
		}
	}
}
if (!function_exists('escCommunity')) {
	function escCommunity(?string $value): string {
		return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
	}
}
$communityRepository = new PublicCommunityRepository(Database::connect());
$featured = $communityRepository->getLatestCommunityService();
$others = $featured !== null
	? $communityRepository->getOtherCommunityServices((int) $featured['id'])
	: [];
$rawPosts = [];
if ($featured !== null) {
	$rawPosts[] = $featured;
}
$rawPosts = array_merge($rawPosts, $others);
$posts = [];
foreach ($rawPosts as $post) {
	$id = (int) ($post['id'] ?? 0);
	$title = trim((string) ($post['title'] ?? 'Community Extension Update'));
	$publishedAt = $post['published_at'] ?? $post['created_at'] ?? null;
	$featuredImage = trim((string) ($post['featured_image'] ?? ''));
	$image = $featuredImage !== ''
		? 'image.php?f=' . rawurlencode(basename($featuredImage))
		: 'images/UCC2.png';
	$content = trim((string) ($post['content'] ?? ''));
	$excerpt = trim((string) ($post['excerpt'] ?? ''));
	if ($excerpt === '') {
		$excerpt = mb_strimwidth(strip_tags($content), 0, 160, '...', 'UTF-8');
	}
	if ($content === '' && $excerpt !== '') {
		$content = '<p>' . escCommunity($excerpt) . '</p>';
	}
	$posts[] = [
		'id' => $id,
		'title' => $title,
		'date' => timeAgo($publishedAt ? (string) $publishedAt : null),
		'image' => $image,
		'excerpt' => $excerpt,
		'content' => $content,
		'name' => trim((string) ($post['name'] ?? ''))
	];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>UCC | Community Extension Services</title>
	<link rel="icon" type="image/png" href="images/UCC2.png">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="assets/utility.css">
	<link rel="stylesheet" href="assets/utility-bar.css">
	<link rel="stylesheet" href="assets/navbar.css">
	<link rel="stylesheet" href="assets/footer.css">
</head>
<body class="student-life-page">
	<?php include __DIR__ . '/includes/utility-bar.php'; ?>
	<?php include __DIR__ . '/includes/navbar.php'; ?>
	<div class="page-dots" aria-hidden="true">
		<span class="dot-pattern dot-1"></span>
		<span class="dot-pattern dot-2"></span>
		<span class="dot-pattern dot-3"></span>
		<span class="dot-pattern dot-4"></span>
	</div>
	<main>
		<!-- COMMUNITY EXTENSION HERO -->
		<section class="student-hero">
			<div class="student-hero-pattern"></div>
			<div class="student-hero-inner">
				<div class="student-hero-copy">
					<span class="student-eyebrow">
						<span></span>
						UNIVERSITY OF CALOOCAN CITY
					</span>
					<h1>Community Extension Services</h1>
					<p>Discover how the University of Caloocan City reaches beyond the campus through meaningful outreach programs, community partnerships, and initiatives that create positive change.</p>
					<div class="student-hero-note">
						<i data-lucide="hand-heart"></i>
						<span>Building stronger communities through service and collaboration</span>
					</div>
				</div>
			</div>
		</section>
		<!-- SEARCH COMMUNITY EXTENSION POSTS -->
		<section class="student-search-section" aria-label="Search community extension posts">
			<div class="student-search-card">
				<div class="search-card-heading">
					<div class="search-icon-wrap">
						<i data-lucide="search"></i>
					</div>
					<div>
						<span>COMMUNITY CONNECTION</span>
						<h2>Search Community Extension Updates</h2>
					</div>
				</div>
				<div class="student-search-controls">
					<div class="student-search-input">
						<i data-lucide="search"></i>
						<input type="search" id="communitySearch" placeholder="Search programs, outreach activities, or community projects..." autocomplete="off" aria-label="Search community extension posts">
					</div>
					<button type="button" class="student-search-button" id="communitySearchButton">
						<span>Search</span>
						<i data-lucide="arrow-right"></i>
					</button>
				</div>
				<p class="search-hint">Enter a keyword or title to find a Community Extension Services article.</p>
			</div>
		</section>
		<!-- COMMUNITY EXTENSION ARTICLES -->
		<section class="student-section" id="community-stories">
			<div class="student-section-heading">
				<div>
					<span class="section-eyebrow">SERVICE BEYOND THE CAMPUS</span>
					<h2>Community <span>Engagement</span></h2>
					<p>Explore UCC's outreach programs, partnerships, and initiatives dedicated to serving the community.</p>
				</div>
				<a href="#community-posts" class="student-explore-link">
					Explore updates
					<i data-lucide="arrow-down"></i>
				</a>
			</div>
			<?php if (!empty($posts)): ?>
				<div class="student-grid" id="community-posts">
					<?php foreach ($posts as $index => $post): ?>
						<?php
							$searchText = strtolower($post['title'] . ' ' . $post['excerpt'] . ' ' . strip_tags($post['content']) . ' ' . $post['name']);
							$articleUrl = 'postArticle.php?id=' . $post['id'];
						?>
						<article class="student-card" data-search="<?= escCommunity($searchText) ?>">
							<a href="<?= escCommunity($articleUrl) ?>" class="student-card-image-link" aria-label="Read <?= escCommunity($post['title']) ?>">
								<div class="student-card-visual">
									<img
										src="<?= escCommunity($post['image']) ?>"
										alt="<?= escCommunity($post['title']) ?>"
										onerror="this.onerror=null;this.src='images/UCC2.png';"
										loading="lazy"
										decoding="async"
									>
									<span class="student-card-number"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
									<span class="student-image-label">COMMUNITY SERVICE</span>
								</div>
							</a>
							<div class="student-card-content">
								<span class="student-card-category">COMMUNITY EXTENSION</span>
								<h3>
									<a href="<?= escCommunity($articleUrl) ?>"><?= escCommunity($post['title']) ?></a>
								</h3>
								<div class="student-card-date">
									<i data-lucide="clock-3"></i>
									<span><?= escCommunity($post['date']) ?></span>
								</div>
								<p><?= escCommunity($post['excerpt']) ?></p>
								<div class="student-card-footer">
									<a href="<?= escCommunity($articleUrl) ?>" class="student-read-more">
										READ MORE
										<i data-lucide="arrow-up-right"></i>
									</a>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
				<div class="student-no-results" id="noCommunityResults" hidden>
					<div class="no-results-icon">
						<i data-lucide="search-x"></i>
					</div>
					<h3>No community updates found</h3>
					<p>We couldn't find a Community Extension Services article matching your search. Try another keyword.</p>
					<button type="button" id="resetCommunitySearch">Clear Search</button>
				</div>
			<?php else: ?>
				<div class="no-student-posts">
					<div class="no-results-icon">
						<i data-lucide="hand-heart"></i>
					</div>
					<h3>No community extension updates available</h3>
					<p>There are currently no Community Extension Services posts to display. Please check again later.</p>
				</div>
			<?php endif; ?>
		</section>
		<!-- HELP SECTION -->
		<section class="student-help">
			<div class="student-help-icon">
				<i data-lucide="info"></i>
			</div>
			<div class="student-help-content">
				<span>WORKING TOGETHER FOR THE COMMUNITY</span>
				<h2>Want to learn more about UCC's community programs?</h2>
				<p>Get in touch with the University to learn more about its community extension initiatives and outreach activities.</p>
			</div>
			<a href="contacts.php" class="student-help-link">
				Contact UCC
				<i data-lucide="arrow-up-right"></i>
			</a>
		</section>
	</main>
	<?php include __DIR__ . '/includes/footer.php'; ?>
	<button class="back-top" id="backTop" aria-label="Back to top">
		<i data-lucide="arrow-up"></i>
	</button>
	<script src="js/script.js"></script>
	<script src="https://unpkg.com/lucide@latest"></script>
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			if (window.lucide) {
				lucide.createIcons();
			}
			const searchInput = document.getElementById('communitySearch');
			const searchButton = document.getElementById('communitySearchButton');
			const resetButton = document.getElementById('resetCommunitySearch');
			const resultsContainer = document.getElementById('community-posts');
			const noResults = document.getElementById('noCommunityResults');
			if (!searchInput || !resultsContainer) return;
			const cards = Array.from(resultsContainer.querySelectorAll('.student-card'));
			function filterPosts() {
				const query = searchInput.value.trim().toLowerCase();
				let visibleCount = 0;
				cards.forEach(function (card) {
					const searchableText = (card.dataset.search || '').toLowerCase();
					const isMatch = searchableText.includes(query);
					card.hidden = !isMatch;
					if (isMatch) visibleCount++;
				});
				if (noResults) {
					noResults.hidden = visibleCount !== 0;
				}
				resultsContainer.classList.toggle('has-no-results', visibleCount === 0);
			}
			function clearSearch() {
				searchInput.value = '';
				filterPosts();
				searchInput.focus();
			}
			searchInput.addEventListener('input', filterPosts);
			searchInput.addEventListener('keydown', function (event) {
				if (event.key === 'Enter') {
					filterPosts();
					document.getElementById('community-stories').scrollIntoView({ behavior: 'smooth' });
				}
				if (event.key === 'Escape') {
					clearSearch();
				}
			});
			if (searchButton) {
				searchButton.addEventListener('click', function () {
					filterPosts();
					document.getElementById('community-stories').scrollIntoView({ behavior: 'smooth' });
				});
			}
			if (resetButton) {
				resetButton.addEventListener('click', clearSearch);
			}
		});
	</script>
</body>
</html>
