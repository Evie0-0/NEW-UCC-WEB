<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/Repositories/PublicProgramsRepository.php';
use Core\Database;
use Repo\PublicProgramsRepository;
function escapeProgramEvent(string $value): string
{
	return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
function programEventImage(string $image): string
{
	$image = trim($image);
	if ($image === '') {
		return 'images/UCC2.png';
	}
	if (preg_match('#^(https?:)?//#i', $image) || $image[0] === '/' || strpos($image, 'image.php?f=') === 0) {
		return $image;
	}
	$filename = basename(str_replace('\\', '/', $image));
	return $filename !== '' ? 'image.php?f=' . rawurlencode($filename) : 'images/UCC2.png';
}
function programEventTime(?string $datetime): string
{
	if (!$datetime) {
		return 'Date unavailable';
	}
	$timestamp = strtotime($datetime);
	if ($timestamp === false) {
		return 'Date unavailable';
	}
	return 'Published ' . date('F j, Y', $timestamp);
}
$programId = filter_var($_GET['program'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$programRecord = null;
$events = [];
$programDb = null;
if ($programId !== false && $programId !== null) {
	try {
		$programDb = Database::connect();
		$programRepo = new PublicProgramsRepository($programDb);
		$programRecord = $programRepo->findProgram((int) $programId);
		if ($programRecord) {
			$unitId = (int) ($programRecord['unit_id'] ?? $programRecord['college_id'] ?? 0);
			if ($unitId > 0 && $programDb instanceof PDO) {
				$collegeStmt = $programDb->prepare('SELECT name FROM units WHERE id = ? AND active = 1 LIMIT 1');
				$collegeStmt->execute([$unitId]);
				$programRecord['college_name'] = (string) ($collegeStmt->fetchColumn() ?: '');
			}
			foreach ($programRepo->programsNews((int) $programId) as $row) {
				$desc = trim((string) ($row['excerpt'] ?? ''));
				if ($desc === '') {
					$desc = trim(strip_tags((string) ($row['content'] ?? '')));
				}
				$fullContent = trim(html_entity_decode(
					strip_tags((string) ($row['content'] ?? '')),
					ENT_QUOTES | ENT_HTML5,
					'UTF-8'
				));
				$eventId = (int) ($row['id'] ?? 0);
				$events[] = [
					'id' => $eventId,
					'program_id' => (int) $programId,
					'title' => (string) ($row['title'] ?? 'Untitled Event'),
					'date' => programEventTime(isset($row['published_at']) ? (string) $row['published_at'] : null),
					'image' => programEventImage((string) ($row['featured_image'] ?? '')),
					'excerpt' => $desc,
					'description' => $desc,
					'content' => $fullContent,
					'readMoreUrl' => 'postArticle.php?type=program&id=' . $eventId
				];
			}
		}
	} catch (Throwable $e) {
		error_log('Program events error: ' . $e->getMessage());
		$programRecord = null;
		$events = [];
	}
}
if (!$programRecord) {
	http_response_code(404);
}
$programName = $programRecord ? trim((string) ($programRecord['name'] ?? '')) : '';
$pageHeading = $programName !== '' ? strtoupper($programName) . ' EVENTS' : 'PROGRAM EVENTS';
$programCollegeName = '';
if ($programRecord) {
	foreach (['college_name', 'unit_name', 'college'] as $collegeKey) {
		if (!empty($programRecord[$collegeKey])) {
			$programCollegeName = trim((string) $programRecord[$collegeKey]);
			break;
		}
	}
}
$programEventsJson = json_encode(
	$events,
	JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
) ?: '[]';
$programEventsId = (int) ($programId ?: 0);
$collegeBackUrl = $programCollegeName !== ''
	? 'colleges.php?college=' . rawurlencode($programCollegeName)
	: 'javascript:history.back()';
$programDescription = $programName !== ''
	? 'Explore the latest events, academic activities, celebrations, and program initiatives of ' . $programName . ' at the University of Caloocan City.'
	: 'Explore academic events, celebrations, and activities from the University of Caloocan City programs.';
$collegeBreadcrumb = $programCollegeName !== '' ? strtoupper($programCollegeName) : 'ACADEMICS';
unset($events, $row, $desc, $fullContent, $eventId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="<?= escapeProgramEvent($programDescription) ?>">
	<title>UCC | <?= escapeProgramEvent($pageHeading) ?></title>
	<link rel="icon" type="image/png" href="images/UCC2.png">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="assets/utility.css">
	<link rel="stylesheet" href="assets/colleges-events.css">
	<link rel="stylesheet" href="assets/program-events.css">
	<link rel="stylesheet" href="assets/utility-bar.css">
	<link rel="stylesheet" href="assets/navbar.css">
	<link rel="stylesheet" href="assets/footer.css">
</head>
<body class="post-article-page college-events-body program-events-body">
	<?php include __DIR__ . '/includes/utility-bar.php'; ?>
	<?php include __DIR__ . '/includes/navbar.php'; ?>
	<main class="article-page college-events-article-page program-events-article-page" id="programEventsPage" data-program-id="<?= $programEventsId ?>">
		<script id="programEventsData" type="application/json"><?= $programEventsJson ?></script>
		<section class="article-hero college-events-hero program-events-hero">
			<video class="article-hero-video" autoplay muted loop playsinline preload="metadata" aria-hidden="true">
				<source src="images/UCC-NEWS-HERO-BG.mp4" type="video/mp4">
			</video>
			<div class="article-hero-overlay"></div>
			<div class="article-hero-decoration article-hero-decoration-one" aria-hidden="true"></div>
			<div class="article-hero-decoration article-hero-decoration-two" aria-hidden="true"></div>
			<div class="article-hero-inner">
				<nav class="article-breadcrumb" aria-label="Breadcrumb">
					<a href="<?= escapeProgramEvent($collegeBackUrl) ?>"><?= escapeProgramEvent($collegeBreadcrumb) ?></a>
					<span aria-hidden="true">›</span>
					<span><?= escapeProgramEvent($pageHeading) ?></span>
				</nav>
				<div class="article-hero-content">
					<div class="article-category">
						<span class="article-category-dot"></span>
						PROGRAM EVENTS
					</div>
					<h1><?= escapeProgramEvent($pageHeading) ?></h1>
					<p class="article-hero-excerpt"><?= escapeProgramEvent($programDescription) ?></p>
					<div class="article-hero-meta">
						<span>
							<i data-lucide="calendar-days" aria-hidden="true"></i>
							PROGRAM ACTIVITIES
						</span>
						<span>
							<i data-lucide="graduation-cap" aria-hidden="true"></i>
							<?= escapeProgramEvent($programCollegeName !== '' ? $programCollegeName : 'UNIVERSITY OF CALOOCAN CITY') ?>
						</span>
					</div>
					<div class="article-hero-bottom">
						<a href="<?= escapeProgramEvent($collegeBackUrl) ?>" class="article-back-button program-events-back">
							<i data-lucide="arrow-left" aria-hidden="true"></i>
							BACK TO COLLEGE
						</a>
					</div>
				</div>
			</div>
		</section>
		<section class="college-events-content-section college-events-page program-events-content">
			<div class="page-dots" aria-hidden="true">
				<span class="dot-pattern dot-1"></span>
				<span class="dot-pattern dot-2"></span>
				<span class="dot-pattern dot-3"></span>
				<span class="dot-pattern dot-4"></span>
			</div>
			<div class="news-decor news-decor-top" aria-hidden="true"></div>
			<div class="news-decor news-decor-right" aria-hidden="true"></div>
			<div class="news-decor news-decor-bottom" aria-hidden="true"></div>
			<div class="news-container">
				<div class="college-events-context program-events-context">
					<div class="college-events-context-icon">
						<i data-lucide="graduation-cap" aria-hidden="true"></i>
					</div>
					<div class="college-events-context-text">
						<h2><?= escapeProgramEvent($programName !== '' ? $programName : 'Program Events') ?></h2>
						<p>Discover the activities, achievements, and events organized for this academic program.</p>
					</div>
					<?php if ($programCollegeName !== ''): ?>
						<div class="program-events-college">
							<span>COLLEGE / DEPARTMENT</span>
							<strong><?= escapeProgramEvent($programCollegeName) ?></strong>
						</div>
					<?php endif; ?>
				</div>
				<section class="news-featured" id="featuredEvent"></section>
				<div class="news-section-title" id="moreEventsTitle">
					<div>
						<span>MORE PROGRAM EVENTS</span>
						<div class="news-mini-line">
							<i></i>
							<i></i>
							<i></i>
						</div>
					</div>
					<span class="college-events-count" id="eventsCount"></span>
				</div>
				<section class="news-grid-wrapper" id="eventGridWrapper">
					<button type="button" class="news-slider-arrow news-slider-prev" id="eventPrev" aria-label="Previous events">‹</button>
					<div class="news-grid" id="eventGrid"></div>
					<button type="button" class="news-slider-arrow news-slider-next" id="eventNext" aria-label="Next events">›</button>
				</section>
				<div class="news-pagination" id="eventPagination"></div>
				<button type="button" class="news-view-all" id="viewAllEvents">
					<span>▤</span>
					VIEW ALL EVENTS
				</button>
				<div class="news-empty program-events-empty" id="noProgramEvents" hidden>
					<h3>No program events found</h3>
					<p>No events are currently available for this academic program.</p>
				</div>
			</div>
		</section>
	</main>
	<div class="news-modal college-events-modal program-events-modal" id="eventModal" aria-hidden="true">
		<div class="news-modal-backdrop" data-event-modal-close></div>
		<div class="news-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="eventModalTitle">
			<div class="news-modal-header">
				<div class="news-modal-heading">
					<span class="news-modal-label" id="eventModalCategory">PROGRAM EVENTS</span>
					<h2 id="eventModalTitle">Event Title</h2>
				</div>
				<button type="button" class="news-modal-close" data-event-modal-close aria-label="Close event details">×</button>
			</div>
			<div class="news-modal-body">
				<figure class="news-modal-image">
					<a href="#" id="eventModalImageLink" aria-label="View full-size event image">
						<img id="eventModalImage" src="" alt="">
					</a>
				</figure>
				<div class="news-modal-meta">
					<span class="news-modal-category-badge">PROGRAM EVENTS</span>
					<span class="news-modal-date" id="eventModalDate">Event date</span>
				</div>
				<div class="news-modal-accent"></div>
				<p class="event-modal-description" id="eventModalDescription">Event details will be added here.</p>
				<a href="#" class="article-back-button program-event-read-more" id="eventReadMore" style="display:none;">
					READ FULL ARTICLE
					<i data-lucide="arrow-right" aria-hidden="true"></i>
				</a>
			</div>
			<div class="news-modal-footer">
				University of Caloocan City • Program Events
			</div>
		</div>
	</div>
	<div class="news-image-lightbox" id="eventImageLightbox" aria-hidden="true">
		<div class="news-image-lightbox-backdrop" data-event-image-close></div>
		<div class="news-image-lightbox-content">
			<button type="button" class="news-image-lightbox-close" data-event-image-close aria-label="Close full-size image">×</button>
			<img id="eventLightboxImage" src="" alt="">
		</div>
	</div>
	<?php include __DIR__ . '/includes/footer.php'; ?>
	<button class="back-top" id="backTop" aria-label="Back to top">↑</button>
	<script src="js/script.js"></script>
	<script src="https://unpkg.com/lucide@latest"></script>
	<script>
		document.addEventListener("DOMContentLoaded", () => {
			if (window.lucide) {
				window.lucide.createIcons();
			}
			const heroVideo = document.querySelector(".article-hero-video");
			if (heroVideo) {
				heroVideo.play().catch(() => {
					heroVideo.style.display = "none";
				});
				heroVideo.addEventListener("error", () => {
					heroVideo.style.display = "none";
				});
			}
		});
	</script>
	<script src="js/program-events.js"></script>
</body>
</html>
