<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/Repositories/PublicStudentLifeRepository.php';
use Core\Database;
use Repo\PublicStudentLifeRepository;
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
if (!function_exists('escStudent')) {
	function escStudent(?string $value): string {
		return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
	}
}
$repo = new PublicStudentLifeRepository(Database::connect());
$rawPosts = $repo->getAllPost();
$posts = [];
foreach ($rawPosts as $post) {
	$id = (int) ($post['id'] ?? 0);
	$title = trim((string) ($post['title'] ?? 'Student Life Update'));
	$publishedAt = $post['published_at'] ?? null;
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
		$content = '<p>' . escStudent($excerpt) . '</p>';
	}
	$posts[] = [
		'id' => $id,
		'title' => $title,
		'date' => timeAgo($publishedAt ? (string) $publishedAt : null),
		'image' => $image,
		'excerpt' => $excerpt,
		'content' => $content
	];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>UCC | Student Life</title>
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
		<!-- STUDENT LIFE HERO -->
		<section class="student-hero">
			<div class="student-hero-pattern"></div>
			<div class="student-hero-inner">
				<div class="student-hero-copy">
					<span class="student-eyebrow">
						<span></span>
						UNIVERSITY OF CALOOCAN CITY
					</span>
					<h1>Student Life</h1>
					<p>Discover the stories, experiences, achievements, and activities that bring the UCC community together. Stay connected with the latest happenings around the University.</p>
					<div class="student-hero-note">
						<i data-lucide="graduation-cap"></i>
						<span>Celebrating the experiences and achievements of UCC students</span>
					</div>
				</div>
			</div>
		</section>
		<!-- SEARCH STUDENT LIFE POSTS -->
		<section class="student-search-section" aria-label="Search student life posts">
			<div class="student-search-card">
				<div class="search-card-heading">
					<div class="search-icon-wrap">
						<i data-lucide="search"></i>
					</div>
					<div>
						<span>STAY CONNECTED</span>
						<h2>Search Student Life Stories</h2>
					</div>
				</div>
				<div class="student-search-controls">
					<div class="student-search-input">
						<i data-lucide="search"></i>
						<input type="search" id="studentSearch" placeholder="Search stories, activities, or achievements..." autocomplete="off" aria-label="Search student life posts">
					</div>
					<button type="button" class="student-search-button" id="studentSearchButton">
						<span>Search</span>
						<i data-lucide="arrow-right"></i>
					</button>
				</div>
				<p class="search-hint">Enter a keyword or title to find a Student Life article.</p>
			</div>
		</section>
		<!-- STUDENT LIFE ARTICLES -->
		<section class="student-section" id="student-stories">
			<div class="student-section-heading">
				<div>
					<span class="section-eyebrow">CAMPUS COMMUNITY</span>
					<h2>Stories &amp; <span>Experiences</span></h2>
					<p>Explore the latest activities, accomplishments, and memorable moments of the UCC community.</p>
				</div>
				<a href="#student-posts" class="student-explore-link">
					Explore stories
					<i data-lucide="arrow-down"></i>
				</a>
			</div>
			<?php if (!empty($posts)): ?>
				<div class="student-grid" id="student-posts">
					<?php foreach ($posts as $index => $post): ?>
						<?php
							$searchText = strtolower($post['title'] . ' ' . $post['excerpt'] . ' ' . strip_tags($post['content']));
							$articleUrl = 'postArticle.php?id=' . $post['id'];
						?>
						<article class="student-card" data-search="<?= escStudent($searchText) ?>">
							<a href="<?= escStudent($articleUrl) ?>" class="student-card-image-link" aria-label="Read <?= escStudent($post['title']) ?>">
								<div class="student-card-visual">
									<img
										src="<?= escStudent($post['image']) ?>"
										alt="<?= escStudent($post['title']) ?>"
										onerror="this.onerror=null;this.src='images/UCC2.png';"
										loading="lazy"
										decoding="async"
									>
									<span class="student-card-number"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
									<span class="student-image-label">UCC STUDENT LIFE</span>
								</div>
							</a>
							<div class="student-card-content">
								<span class="student-card-category">STUDENT LIFE</span>
								<h3>
									<a href="<?= escStudent($articleUrl) ?>"><?= escStudent($post['title']) ?></a>
								</h3>
								<div class="student-card-date">
									<i data-lucide="clock-3"></i>
									<span><?= escStudent($post['date']) ?></span>
								</div>
								<p><?= escStudent($post['excerpt']) ?></p>
								<div class="student-card-footer">
									<a href="<?= escStudent($articleUrl) ?>" class="student-read-more">
										READ MORE
										<i data-lucide="arrow-up-right"></i>
									</a>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
				<div class="student-no-results" id="noStudentResults" hidden>
					<div class="no-results-icon">
						<i data-lucide="search-x"></i>
					</div>
					<h3>No stories found</h3>
					<p>We couldn't find a Student Life article matching your search. Try another keyword.</p>
					<button type="button" id="resetStudentSearch">Clear Search</button>
				</div>
			<?php else: ?>
				<div class="no-student-posts">
					<div class="no-results-icon">
						<i data-lucide="book-open"></i>
					</div>
					<h3>No student life stories available</h3>
					<p>There are currently no Student Life posts to display. Please check again later.</p>
				</div>
			<?php endif; ?>
		</section>
		<!-- HELP SECTION -->
		<section class="student-help">
			<div class="student-help-icon">
				<i data-lucide="info"></i>
			</div>
			<div class="student-help-content">
				<span>BE PART OF THE COMMUNITY</span>
				<h2>Want to know more about UCC?</h2>
				<p>Discover more about the University, its campus community, and the opportunities available to students.</p>
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
			const searchInput = document.getElementById('studentSearch');
			const searchButton = document.getElementById('studentSearchButton');
			const resetButton = document.getElementById('resetStudentSearch');
			const resultsContainer = document.getElementById('student-posts');
			const noResults = document.getElementById('noStudentResults');
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
					document.getElementById('student-stories').scrollIntoView({ behavior: 'smooth' });
				}
				if (event.key === 'Escape') {
					clearSearch();
				}
			});
			if (searchButton) {
				searchButton.addEventListener('click', function () {
					filterPosts();
					document.getElementById('student-stories').scrollIntoView({ behavior: 'smooth' });
				});
			}
			if (resetButton) {
				resetButton.addEventListener('click', clearSearch);
			}
		});
	</script>
</body>
</html>