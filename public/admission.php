<?php
require_once __DIR__ . '/../app/Repositories/PublicAdmissionRepository.php';
use Core\Database;
use Repo\PublicAdmissionRepository;
$admissionRepository = new PublicAdmissionRepository(Database::connect());
$admissionPosts = $admissionRepository->getAdmissionPosts();
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
function admissionEscape(?string $value): string
{
	return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
function admissionImagePath(?string $filename): string
{
	if (empty($filename)) {
		return 'images/UCC2.png';
	}
	return 'image.php?f=' . rawurlencode(basename($filename));
}
$posts = [];
foreach ($admissionPosts as $post) {
	$postId = (int)($post['id'] ?? 0);
	$title = $post['title'] ?? 'Untitled Admission Announcement';
	$content = $post['excerpt'] ?? '';
	if (trim($content) === '') {
		$content = mb_strimwidth(strip_tags($post['content'] ?? ''), 0, 180, '...', 'UTF-8');
	}
	$publishedDate = $post['published_at'] ?? $post['created_at'] ?? null;
	$posts[] = [
		'id' => $postId,
		'title' => $title,
		'category' => $post['name'] ?? 'ADMISSION',
		'image' => admissionImagePath($post['featured_image'] ?? null),
		'excerpt' => $content,
		'date' => timeAgo($publishedDate),
		'search' => strtolower($title . ' ' . ($post['name'] ?? 'Admission') . ' ' . $content),
		'published_date' => $publishedDate
	];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>UCC | Admission</title>
	<link rel="icon" type="image/png" href="images/UCC2.png">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="assets/utility.css">
	<link rel="stylesheet" href="assets/utility-bar.css">
	<link rel="stylesheet" href="assets/navbar.css">
	<link rel="stylesheet" href="assets/footer.css">
	<link rel="stylesheet" href="assets/admission.css">
</head>
<body class="student-life-page">
	<?php include __DIR__ . '/includes/utility-bar.php'; ?>
	<?php include __DIR__ . '/includes/navbar.php'; ?>
	<main>
		<section class="student-hero">
			<div class="student-hero-pattern"></div>
			<div class="student-hero-inner">
				<div class="student-hero-copy">
					<span class="student-eyebrow">
						<i data-lucide="graduation-cap"></i>
						UNIVERSITY OF CALOOCAN CITY
					</span>
					<h1>Admission<br>Announcements</h1>
					<p>Stay informed about admission schedules, application requirements, enrollment announcements, and important updates for aspiring University of Caloocan City students.</p>
					<div class="student-hero-note">
						<i data-lucide="calendar-check"></i>
						Your journey toward becoming a UCCian starts here.
					</div>
				</div>
			</div>
		</section>
		<section class="student-search-section">
			<div class="student-search-card">
				<div class="search-card-heading">
					<div class="search-icon-wrap">
						<i data-lucide="search"></i>
					</div>
					<div>
						<span>ADMISSION CONNECTION</span>
						<h2>Find Admission Updates</h2>
					</div>
				</div>
				<div class="student-search-controls">
					<input type="search" class="student-search-input" id="admissionSearch" placeholder="Search admission announcements..." aria-label="Search admission announcements">
					<button type="button" class="student-search-button" id="admissionSearchButton">
						<i data-lucide="search"></i>
						SEARCH
					</button>
				</div>
				<p class="search-hint">Enter a keyword such as enrollment, application, requirements, or admission schedule.</p>
			</div>
		</section>
		<section class="student-section" id="admission-posts">
			<div class="student-section-heading">
				<div>
					<span class="section-eyebrow">STAY UPDATED</span>
					<h2>Latest Admission News</h2>
					<p>Explore the latest announcements and important information for incoming and continuing students.</p>
				</div>
				<a href="#admission-posts" class="student-explore-link">
					EXPLORE UPDATES
					<i data-lucide="arrow-right"></i>
				</a>
			</div>
			<?php if (empty($posts)): ?>
				<div class="no-student-posts">
					<i data-lucide="inbox"></i>
					<p>No admission announcements are available at the moment. Please check back soon.</p>
				</div>
			<?php else: ?>
				<div class="student-grid" id="admissionGrid">
					<?php foreach ($posts as $index => $post): ?>
						<?php $articleUrl = $post['id'] > 0 ? 'postArticle.php?id=' . $post['id'] : '#'; ?>
						<article class="student-card admission-card" data-search="<?= admissionEscape($post['search']) ?>">
							<a href="<?= admissionEscape($articleUrl) ?>" class="student-card-image-link" <?= $post['id'] <= 0 ? 'aria-disabled="true"' : '' ?>>
								<div class="student-card-visual">
									<img src="<?= admissionEscape($post['image']) ?>" alt="<?= admissionEscape($post['title']) ?>" loading="lazy">
									<span class="student-card-number"><?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
									<span class="student-image-label">ADMISSION</span>
								</div>
							</a>
							<div class="student-card-content">
								<h3><?= admissionEscape($post['title']) ?></h3>
								<div class="student-card-date">
									<i data-lucide="calendar-days"></i>
									<span title="<?= !empty($post['published_date']) ? admissionEscape(date('F j, Y', strtotime($post['published_date']))) : '' ?>">
										Published <?= admissionEscape($post['date']) ?>
									</span>
								</div>
								<p><?= admissionEscape($post['excerpt']) ?></p>
								<div class="student-card-footer">
									<a href="<?= admissionEscape($articleUrl) ?>" class="student-read-more" <?= $post['id'] <= 0 ? 'aria-disabled="true"' : '' ?>>
										READ MORE
										<i data-lucide="arrow-right"></i>
									</a>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
				<div class="student-no-results" id="noAdmissionResults" style="display: none;">
					<div class="no-results-icon">
						<i data-lucide="search-x"></i>
					</div>
					<h3>No Admission Updates Found</h3>
					<p>Try another keyword or clear your search to see all admission announcements.</p>
					<button type="button" class="student-search-button" id="resetAdmissionSearch">CLEAR SEARCH</button>
				</div>
			<?php endif; ?>
		</section>
		<section class="student-help">
			<div class="student-help-icon">
				<i data-lucide="headset"></i>
			</div>
			<div class="student-help-content">
				<h3>Need Help With Your Admission?</h3>
				<p>For questions about application requirements, admission procedures, and enrollment, contact the University of Caloocan City.</p>
			</div>
			<a href="contacts.php" class="student-help-link">
				CONTACT UCC
				<i data-lucide="arrow-right"></i>
			</a>
		</section>
	</main>
	<?php include __DIR__ . '/includes/footer.php'; ?>
	<button class="back-top" id="backTop" aria-label="Back to top">
		<i data-lucide="arrow-up"></i>
	</button>
	<script src="https://unpkg.com/lucide@latest"></script>
	<script src="js/script.js"></script>
	<script>
		document.addEventListener("DOMContentLoaded", function () {
			if (typeof lucide !== "undefined") {
				lucide.createIcons();
			}
			const searchInput = document.getElementById("admissionSearch");
			const searchButton = document.getElementById("admissionSearchButton");
			const resetButton = document.getElementById("resetAdmissionSearch");
			const cards = document.querySelectorAll(".admission-card");
			const noResults = document.getElementById("noAdmissionResults");
			const section = document.getElementById("admission-posts");

			function filterAdmissionPosts() {
				if (!searchInput) return;

				const query = searchInput.value.trim().toLowerCase();
				let visibleCount = 0;

				cards.forEach(function (card) {
					const matches = query === "" || card.dataset.search.toLowerCase().includes(query);
					card.style.display = matches ? "" : "none";

					if (matches) {
						visibleCount++;
					}
				});

				if (noResults) {
					noResults.style.display = visibleCount === 0 && query !== "" ? "block" : "none";
				}
			}
			if (searchInput) {
				searchInput.addEventListener("input", filterAdmissionPosts);
				searchInput.addEventListener("keydown", function (event) {
					if (event.key === "Enter") {
						filterAdmissionPosts();
						section.scrollIntoView({ behavior: "smooth" });
					}
					if (event.key === "Escape") {
						searchInput.value = "";
						filterAdmissionPosts();
					}
				});
			}
			if (searchButton) {
				searchButton.addEventListener("click", function () {
					filterAdmissionPosts();
					section.scrollIntoView({ behavior: "smooth" });
				});
			}
			if (resetButton) {
				resetButton.addEventListener("click", function () {
					searchInput.value = "";
					filterAdmissionPosts();
					searchInput.focus();
				});
			}
			const backTop = document.getElementById("backTop");
			if (backTop) {
				window.addEventListener("scroll", function () {
					backTop.classList.toggle("show", window.scrollY > 350);
				});
				backTop.addEventListener("click", function () {
					window.scrollTo({ top: 0, behavior: "smooth" });
				});
			}
			filterAdmissionPosts();
		});
	</script>
</body>
</html>
