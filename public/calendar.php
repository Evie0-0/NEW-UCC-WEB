<?php
use Core\Database;
use Repo\PublicCalendarRepository;
require_once __DIR__ . '/../app/Repositories/PublicCampusRepository.php';
$calendarRepository = new PublicCalendarRepository(Database::connect());
$allEvents = $calendarRepository->getAllCalendarInfo();
if (!function_exists('escStudent')) {
	function escStudent(?string $value): string
	{
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}
}
if (!function_exists('calendarFormatDate')) {
	function calendarFormatDate(?string $datetime): string
	{
		if (empty($datetime)) {
			return '';
		}
		try {
			$date = new DateTimeImmutable($datetime);
		} catch (Exception $e) {
			return '';
		}
		return $date->format('H:i:s') === '00:00:00'
			? $date->format('F j, Y')
			: $date->format('F j, Y \a\t g:i A');
	}
}
if (!function_exists('calendarDateText')) {
	function calendarDateText(?string $start, ?string $end): string
	{
		if (empty($start)) {
			return '';
		}
		try {
			$s = new DateTimeImmutable($start);
			$e = !empty($end) ? new DateTimeImmutable($end) : null;
		} catch (Exception $e) {
			return '';
		}
		if (!$e || $s->format('Y-m-d') === $e->format('Y-m-d')) {
			return $s->format('F j, Y');
		}
		if ($s->format('Y') !== $e->format('Y')) {
			return $s->format('F j, Y') . ' – ' . $e->format('F j, Y');
		}
		return $s->format('F j') . ' – ' . $e->format('F j, Y');
	}
}
$events = [];
if (is_array($allEvents)) {
	foreach ($allEvents as $row) {
		if (
			isset($row['id']) &&
			($row['status'] ?? '') === 'published' &&
			(int)($row['active'] ?? 0) === 1 &&
			!empty($row['published_at']) &&
			strtotime((string)$row['published_at']) <= time()
		) {
			$events[] = $row;
		}
	}
}
usort($events, function ($a, $b) {
	return strtotime((string)($a['starts_at'] ?? '')) <=> strtotime((string)($b['starts_at'] ?? ''));
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>UCC | Academic Calendar</title>
	<link rel="icon" type="image/png" href="images/UCC2.png">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="assets/calendar.css">
	<link rel="stylesheet" href="assets/utility-bar.css">
	<link rel="stylesheet" href="assets/navbar.css">
	<link rel="stylesheet" href="assets/footer.css">
	<style>
		html,body{min-height:100%}
		body{min-height:100vh;display:flex;flex-direction:column}
		.calendar-page{flex:1 0 auto;min-height:0}
		.calendar-events-section{padding:55px 0 80px}
		.calendar-events-heading{text-align:center;margin-bottom:35px}
		.calendar-events-heading span{display:block;color:#087342;font-size:12px;font-weight:800;letter-spacing:2px;margin-bottom:10px}
		.calendar-events-heading h2{font-family:'Oswald',sans-serif;color:#004b25;font-size:clamp(30px,4vw,42px);margin:0 0 10px}
		.calendar-events-heading p{color:#647067;font-size:14px;margin:0}
		.calendar-events-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:24px}
		.calendar-event-card{background:#fff;border:1px solid #e6ece7;border-radius:12px;overflow:hidden;box-shadow:0 8px 25px rgba(0,0,0,.06);display:flex;flex-direction:column}
		.calendar-event-image{height:190px;background:#edf3ee;overflow:hidden}
		.calendar-event-image img{width:100%;height:100%;object-fit:cover;display:block}
		.calendar-event-body{padding:22px;display:flex;flex-direction:column;flex:1}
		.calendar-event-date{font-size:12px;font-weight:800;color:#087342;margin-bottom:12px;display:flex;align-items:center;gap:7px}
		.calendar-event-body h3{font-family:'Oswald',sans-serif;font-size:23px;line-height:1.3;color:#004b25;margin:0 0 12px}
		.calendar-event-description{font-size:13px;line-height:1.8;color:#647067;margin:0 0 20px}
		.calendar-read-more{margin-top:auto;display:inline-flex;align-items:center;gap:9px;color:#006039;font-size:12px;font-weight:800;text-decoration:none;letter-spacing:.5px}
		.calendar-read-more:hover{color:#eab72e}
		.calendar-empty{text-align:center;padding:45px 20px;background:#fff;border:1px solid #e6ece7;border-radius:12px;color:#647067}
		.calendar-empty h3{font-family:'Oswald',sans-serif;color:#004b25;font-size:24px;margin:0 0 10px}
		.calendar-back-link{display:inline-flex;align-items:center;gap:9px;color:#006039;text-decoration:none;font-size:13px;font-weight:800;margin-bottom:25px}
		.calendar-modal{position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.68);display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;visibility:hidden;transition:opacity .25s ease,visibility .25s ease}
		.calendar-modal.show{opacity:1;visibility:visible}
		.calendar-modal-content{background:#fff;width:min(900px,100%);max-height:88vh;overflow-y:auto;border-radius:14px;position:relative;padding:35px}
		.calendar-modal-header{padding-right:45px;margin-bottom:25px}
		.calendar-modal-header span{font-size:11px;font-weight:800;letter-spacing:2px;color:#087342}
		.calendar-modal-header h2{font-family:'Oswald',sans-serif;color:#004b25;font-size:34px;margin:7px 0}
		.calendar-modal-header p{font-size:13px;color:#647067;margin:0;line-height:1.7}
		.calendar-modal-close{position:absolute;right:20px;top:18px;width:38px;height:38px;border:0;border-radius:50%;background:#edf3ee;color:#004b25;font-size:25px;cursor:pointer;display:flex;align-items:center;justify-content:center}
		.calendar-modal-close:hover{background:#004b25;color:#fff}
		.calendar-modal-list{display:grid;gap:15px}
		.calendar-modal-event{display:grid;grid-template-columns:115px 1fr;gap:20px;padding:17px;border:1px solid #e6ece7;border-radius:10px;background:#fff}
		.calendar-modal-event-date{background:#edf5ef;border-radius:8px;padding:15px 10px;text-align:center;display:flex;flex-direction:column;justify-content:center}
		.calendar-modal-event-date strong{font-family:'Oswald',sans-serif;font-size:27px;color:#004b25;line-height:1.1}
		.calendar-modal-event-date span{font-size:11px;color:#087342;font-weight:800;text-transform:uppercase;margin-top:5px}
		.calendar-modal-event-info h3{font-family:'Oswald',sans-serif;font-size:21px;color:#004b25;margin:0 0 7px}
		.calendar-modal-event-info p{font-size:12px;line-height:1.7;color:#647067;margin:0 0 10px}
		.calendar-modal-event-info a{display:inline-flex;align-items:center;gap:7px;color:#006039;font-size:11px;font-weight:800;text-decoration:none}
		.calendar-modal-event-info a:hover{color:#d49b12}
		.calendar-modal-empty{text-align:center;padding:35px 15px;border:1px dashed #cbd8ce;border-radius:10px;color:#647067;font-size:13px}
		@media(max-width:600px){
			.calendar-modal{padding:10px}
			.calendar-modal-content{padding:25px 18px;max-height:92vh}
			.calendar-modal-header h2{font-size:28px}
			.calendar-modal-event{grid-template-columns:75px 1fr;gap:12px;padding:12px}
			.calendar-modal-event-date{padding:10px 5px}
			.calendar-modal-event-date strong{font-size:22px}
			.calendar-modal-event-info h3{font-size:18px}
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
	<?php include __DIR__ . '/includes/utility-bar.php'; ?>
	<?php include __DIR__ . '/includes/navbar.php'; ?>
	<main class="calendar-page">
		<section class="calendar-hero">
			<div class="calendar-hero-overlay"></div>
			<div class="container calendar-hero-content">
				<span>UNIVERSITY OF CALOOCAN CITY</span>
				<h1>Academic Calendar</h1>
				<p>Important academic schedules, examinations, and university dates.</p>
			</div>
		</section>
		<section class="calendar-events-section">
			<div class="container">
				<a href="homepage.php" class="calendar-back-link">
					<i data-lucide="arrow-left"></i>
					<span>Back to Homepage</span>
				</a>
				<div class="calendar-events-heading">
					<span>STAY UPDATED</span>
					<h2>University Events and Schedules</h2>
					<p>Browse the latest academic schedules and upcoming university events.</p>
				</div>
				<?php if (!empty($events)): ?>
					<div class="calendar-events-grid">
						<?php foreach ($events as $event): ?>
							<?php
							$imageFile = $event['featured_image'] ?? '';
							$imageUrl = !empty($imageFile)
								? 'image.php?f=' . rawurlencode((string)$imageFile)
								: 'images/UCC2.png';
							$description = trim(strip_tags((string)($event['description'] ?? '')));
							$description = preg_replace('/\s+/', ' ', $description);
							$description = mb_strimwidth($description, 0, 150, '...');
							$startDate = !empty($event['starts_at']) ? new DateTimeImmutable($event['starts_at']) : null;
							?>
							<article class="calendar-event-card">
								<div class="calendar-event-image">
									<img src="<?= escStudent($imageUrl) ?>" alt="<?= escStudent($event['title'] ?? 'Calendar event') ?>">
								</div>
								<div class="calendar-event-body">
									<div class="calendar-event-date">
										<i data-lucide="calendar-days"></i>
										<?= escStudent(calendarDateText($event['starts_at'] ?? null, $event['ends_at'] ?? null)) ?>
									</div>
									<h3><?= escStudent($event['title'] ?? '') ?></h3>
									<p class="calendar-event-description"><?= escStudent($description) ?></p>
									<a class="calendar-read-more" href="postArticle.php?type=calendar&id=<?= (int)$event['id'] ?>">
										READ MORE
										<i data-lucide="arrow-right"></i>
									</a>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				<?php else: ?>
					<div class="calendar-empty">
						<h3>No Calendar Events Available</h3>
						<p>There are currently no published calendar events to display.</p>
					</div>
				<?php endif; ?>
			</div>
		</section>
	</main>
	<div class="calendar-modal show" id="calendarModal" aria-hidden="false">
		<div class="calendar-modal-content" role="dialog" aria-modal="true" aria-labelledby="calendarModalTitle">
			<button type="button" class="calendar-modal-close" id="calendarModalClose" aria-label="Close calendar modal">&times;</button>
			<div class="calendar-modal-header">
				<span>UNIVERSITY OF CALOOCAN CITY</span>
				<h2 id="calendarModalTitle">Academic Calendar</h2>
				<p>Check the important academic schedules, examinations, and upcoming university events.</p>
			</div>
			<?php if (!empty($events)): ?>
				<div class="calendar-modal-list">
					<?php foreach ($events as $event): ?>
						<?php
						$modalStart = !empty($event['starts_at']) ? new DateTimeImmutable($event['starts_at']) : null;
						$modalDescription = trim(strip_tags((string)($event['description'] ?? '')));
						$modalDescription = preg_replace('/\s+/', ' ', $modalDescription);
						$modalDescription = mb_strimwidth($modalDescription, 0, 190, '...');
						?>
						<article class="calendar-modal-event">
							<div class="calendar-modal-event-date">
								<?php if ($modalStart): ?>
									<strong><?= escStudent($modalStart->format('d')) ?></strong>
									<span><?= escStudent($modalStart->format('M Y')) ?></span>
								<?php else: ?>
									<strong>--</strong>
									<span>DATE TBA</span>
								<?php endif; ?>
							</div>
							<div class="calendar-modal-event-info">
								<h3><?= escStudent($event['title'] ?? '') ?></h3>
								<p><?= escStudent($modalDescription) ?></p>
								<p><strong><?= escStudent(calendarDateText($event['starts_at'] ?? null, $event['ends_at'] ?? null)) ?></strong></p>
								<a href="postArticle.php?type=calendar&id=<?= (int)$event['id'] ?>">
									READ MORE
									<i data-lucide="arrow-right"></i>
								</a>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else: ?>
				<div class="calendar-modal-empty">No published calendar events are available at the moment.</div>
			<?php endif; ?>
		</div>
	</div>
	<?php include __DIR__ . '/includes/footer.php'; ?>
	<button class="back-top" id="backTop" aria-label="Back to top">↑</button>
	<script src="js/script.js"></script>
	<script src="https://unpkg.com/lucide@latest"></script>
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			const modal = document.getElementById('calendarModal');
			const closeButton = document.getElementById('calendarModalClose');
			function closeCalendarModal() {
				modal.classList.remove('show');
				modal.setAttribute('aria-hidden', 'true');
			}
			closeButton.addEventListener('click', closeCalendarModal);
			modal.addEventListener('click', function (event) {
				if (event.target === modal) {
					closeCalendarModal();
				}
			});
			document.addEventListener('keydown', function (event) {
				if (event.key === 'Escape' && modal.classList.contains('show')) {
					closeCalendarModal();
				}
			});
			if (window.lucide) {
				lucide.createIcons();
			}
		});
	</script>
</body>
</html>
