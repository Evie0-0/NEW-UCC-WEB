<?php
require_once __DIR__ . '/../app/Repositories/PublicCollegesRepository.php';

use Core\Database;
use Repo\PublicCollegesRepository;

function collegeEventsEscape($value) {
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/*
 * The database stores only the image file name (featured_image).
 * Uploaded images live in admin/assets/images/.
 */
function eventsImage($file): string {
	$file = trim((string) $file);

	if ($file === '') {
		return 'images/UCC2.png';
	}

	if (preg_match('#^https?://#i', $file) || $file[0] === '/') {
		return $file;
	}

	$filename = basename(str_replace('\\', '/', $file));

	return $filename !== ''
		? 'image.php?f=' . rawurlencode($filename)
		: 'images/UCC2.png';
}

// "Published 3 mos ago" style label from published_at
function eventsTimeAgo(?string $datetime): string {
	$time = $datetime ? strtotime($datetime) : false;

	if (!$time) {
		return 'Published';
	}

	$seconds = max(0, time() - $time);

	$units = [
		['yr', 31536000],
		['mo', 2592000],
		['day', 86400],
		['hr', 3600],
		['min', 60],
	];

	foreach ($units as [$label, $size]) {
		$count = (int) floor($seconds / $size);

		if ($count >= 1) {
			$plural = $count === 1 ? '' : 's';
			return 'Published ' . $count . ' ' . $label . $plural . ' ago';
		}
	}

	return 'Published just now';
}

/**
 * Fetch the college record itself from `units`, independent of unit_info rows.
 * Works with PDO or mysqli.
 */
function eventsFetchCollegeBase($conn, string $name): array {
	$sql = 'SELECT id, name FROM units WHERE name = ? AND active = 1 LIMIT 1';

	if ($conn instanceof PDO) {
		$stmt = $conn->prepare($sql);
		$stmt->execute([$name]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ?: [];
	}

	if ($conn instanceof mysqli) {
		$stmt = $conn->prepare($sql);
		$stmt->bind_param('s', $name);
		$stmt->execute();

		$result = $stmt->get_result();
		$row = $result ? $result->fetch_assoc() : null;

		$stmt->close();

		return $row ?: [];
	}

	return [];
}

// This is the ?college= value, which is units.name
$requestedCollege = trim((string) ($_GET['college'] ?? ''));

$infoRows = [];
$newsRows = [];
$baseCollege = [];
$newsRepository = null;

try {
	$conn = Database::connect();
	$newsRepository = new PublicCollegesRepository($conn);

	if ($requestedCollege !== '') {
		$baseCollege = eventsFetchCollegeBase($conn, $requestedCollege);
		$infoRows = $newsRepository->collegesInfo($requestedCollege);
	}
} catch (Throwable $e) {
	error_log('College events page error: ' . $e->getMessage());
}

$collegeExists = !empty($baseCollege) || !empty($infoRows);

// If the news query fails, the college still shows with no events.
if ($collegeExists && $newsRepository !== null) {
	try {
		$newsRows = $newsRepository->collegesNews($requestedCollege);
	} catch (Throwable $e) {
		error_log('College events news error: ' . $e->getMessage());
		$newsRows = [];
	}
}

$notFound = !$collegeExists;

if ($notFound) {
	http_response_code(404);
}

// Prepare event data for colleges-events.js
$events = [];

foreach ($newsRows as $row) {
	$events[] = [
		'id' => (int) $row['id'],
		'title' => (string) $row['title'],
		'excerpt' => (string) ($row['excerpt'] ?? ''),
		'content' => (string) ($row['content'] ?? ''),
		'image' => eventsImage($row['featured_image'] ?? ''),
		'date' => eventsTimeAgo($row['published_at'] ?? null),
		'college' => $requestedCollege,
	];
}

// Name comes from info rows if present, otherwise from the units record.
$collegeTitle = (string) ($infoRows[0]['college_name'] ?? $baseCollege['name'] ?? '');

$eventsPage = [
	'title' => $collegeTitle !== '' ? $collegeTitle : 'College not found',
	'query' => $requestedCollege,
];

$eventsJson = json_encode(
	$events,
	JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);

if ($eventsJson === false) {
	$eventsJson = '[]';
}

$collegeBackUrl = 'colleges.php?college=' . rawurlencode($eventsPage['query']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<meta name="description" content="<?= collegeEventsEscape('Explore academic activities, announcements, achievements, and student events from ' . $eventsPage['title'] . ' at the University of Caloocan City.') ?>">

	<title>UCC | <?= collegeEventsEscape($eventsPage['title']) ?> Events</title>

	<link rel="icon" type="image/png" href="images/UCC2.png">

	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

	<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet">

	<link rel="stylesheet" href="assets/utility-bar.css">
	<link rel="stylesheet" href="assets/navbar.css">
	<link rel="stylesheet" href="assets/footer.css">
	<link rel="stylesheet" href="assets/colleges-events.css">

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

		.college-events-page.news-page {
			position: relative;
			overflow: hidden;
			background: #f8f8f4;
			padding: 35px 0 40px;
			color: #263c31;
		}
	</style>
</head>

<body class="post-article-page college-events-body">

	<?php include __DIR__ . '/includes/utility-bar.php'; ?>
	<?php include __DIR__ . '/includes/navbar.php'; ?>

	<main class="article-page college-events-article-page">

		<!-- COLLEGE EVENTS HERO -->
		<section class="article-hero college-events-hero">

			<video class="article-hero-video" autoplay muted loop playsinline preload="metadata" aria-hidden="true">
				<source src="images/UCC-NEWS-HERO-BG.mp4" type="video/mp4">
			</video>

			<div class="article-hero-overlay"></div>
			<div class="article-hero-decoration article-decoration-one"></div>
			<div class="article-hero-decoration article-decoration-two"></div>

			<div class="article-hero-inner">

				<div class="article-breadcrumb">
					<a href="<?= collegeEventsEscape($collegeBackUrl) ?>">ACADEMICS</a>
					<i data-lucide="chevron-right"></i>
					<span><?= collegeEventsEscape(strtoupper($eventsPage['title'])) ?></span>
				</div>

				<div class="article-hero-content">

					<span class="article-category">
						<span class="article-category-dot"></span>
						COLLEGE EVENTS
					</span>

					<h1>COLLEGE EVENTS</h1>

					<p class="article-hero-excerpt">
						Explore academic activities, announcements, achievements, and student events from <?= collegeEventsEscape($eventsPage['title']) ?>.
					</p>

					<div class="article-hero-meta">

						<span>
							<i data-lucide="calendar-days"></i>
							ACADEMIC ACTIVITIES &amp; ANNOUNCEMENTS
						</span>

						<span class="article-meta-divider"></span>

						<span>
							<i data-lucide="landmark"></i>
							<?= collegeEventsEscape($eventsPage['title']) ?>
						</span>

					</div>

				</div>

				<div class="article-hero-bottom">
					<span>UNIVERSITY STORIES &amp; COLLEGE EVENTS</span>
					<span class="article-hero-line"></span>
				</div>

			</div>
		</section>

		<!-- COLLEGE EVENTS CONTENT -->
		<section class="college-events-content-section college-events-page">

			<div class="page-dots" aria-hidden="true">
				<span class="dot-pattern dot-1"></span>
				<span class="dot-pattern dot-2"></span>
				<span class="dot-pattern dot-3"></span>
				<span class="dot-pattern dot-4"></span>
			</div>

			<div class="news-decor news-decor-top" aria-hidden="true"></div>
			<div class="news-decor news-decor-right" aria-hidden="true"></div>
			<div class="news-decor news-decor-bottom" aria-hidden="true"></div>

			<section class="news-container">

				<?php if ($notFound): ?>
					<div class="container" style="padding:40px 20px;text-align:center;">
						<h2>College not found</h2>
						<p>The selected college does not exist. Please choose a college from the Academics menu.</p>
					</div>
				<?php endif; ?>

				<div class="college-events-context">

					<span class="college-events-context-icon">
						<i data-lucide="graduation-cap"></i>
					</span>

					<div>
						<span class="college-events-context-label">COLLEGE / DEPARTMENT</span>
						<strong><?= collegeEventsEscape($eventsPage['title']) ?></strong>
						<small>Showing the latest news and events published by this college.</small>
					</div>

				</div>

				<section class="news-featured" id="featuredNews" aria-live="polite"></section>

				<div class="news-section-title">

					<div>
						<span id="moreEventsTitle">MORE COLLEGE EVENTS</span>
						<div class="news-mini-line">
							<i></i>
							<i></i>
							<i></i>
						</div>
					</div>

					<span class="college-events-count" id="eventsCount"></span>

				</div>

				<section class="news-grid-wrapper">

					<button type="button" class="news-slider-arrow news-slider-prev" id="newsPrev" aria-label="Previous events">‹</button>

					<div class="news-grid" id="newsGrid" aria-live="polite"></div>

					<button type="button" class="news-slider-arrow news-slider-next" id="newsNext" aria-label="Next events">›</button>

				</section>

				<div class="news-pagination" id="newsPagination" aria-label="Event pages"></div>

				<button type="button" class="news-view-all" id="viewAllEvents">
					<span>▤</span> VIEW ALL EVENTS
				</button>

			</section>
		</section>

	</main>

	<!-- COLLEGE EVENT MODAL -->
	<div class="news-modal college-events-modal" id="newsModal" aria-hidden="true">

		<div class="news-modal-backdrop" data-news-modal-close></div>

		<div class="news-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="newsModalTitle">

			<div class="news-modal-header">

				<div class="news-modal-heading">
					<span class="news-modal-label" id="newsModalCategory">COLLEGE EVENT</span>
					<h2 id="newsModalTitle">Event Title</h2>
				</div>

				<button type="button" class="news-modal-close" data-news-modal-close aria-label="Close event popup">×</button>

			</div>

			<div class="news-modal-body">

				<figure class="news-modal-image">
					<a href="#" id="newsModalImageLink" aria-label="View full-size event image">
						<img id="newsModalImage" src="" alt="">
					</a>
				</figure>

				<div class="news-modal-meta">
					<span class="news-modal-category" id="newsModalCategoryBadge">EVENT</span>
					<span class="news-modal-date" id="newsModalDate">Published</span>
				</div>

				<div class="college-events-modal-program" id="newsModalProgram" hidden></div>

				<div class="news-modal-accent"></div>

				<p class="news-modal-placeholder" id="newsModalDescription"></p>

			</div>

			<div class="news-modal-footer">
				<?= collegeEventsEscape($eventsPage['title']) ?> • College Events
			</div>

		</div>
	</div>

	<!-- EVENT IMAGE LIGHTBOX -->
	<div class="news-image-lightbox" id="newsImageLightbox" aria-hidden="true">

		<div class="news-image-lightbox-backdrop" data-news-image-close></div>

		<div class="news-image-lightbox-content">

			<button type="button" class="news-image-lightbox-close" data-news-image-close aria-label="Close full-size image">×</button>

			<img id="newsLightboxImage" src="" alt="">

		</div>
	</div>

	<!-- EVENT DATA FOR JAVASCRIPT -->
	<script type="application/json" id="collegeEventsData"><?= $eventsJson ?></script>

	<?php include __DIR__ . '/includes/footer.php'; ?>

	<button class="back-top" id="backTop" aria-label="Back to top">↑</button>

	<script src="js/script.js"></script>
	<script src="https://unpkg.com/lucide@latest"></script>

	<script>
		document.addEventListener("DOMContentLoaded", function () {
			if (window.lucide) {
				window.lucide.createIcons();
			}

			const video = document.querySelector(".article-hero-video");

			if (video) {
				video.play().catch(function () {
					video.style.display = "none";
				});
			}
		});
	</script>

	<script src="js/colleges-events.js" defer></script>

</body>
</html>