<?php
use Core\Database;
use Repo\PublicCalendarRepository;

require_once __DIR__ . '/../app/Repositories/PublicCampusRepository.php';

$calendarRepository = new PublicCalendarRepository(Database::connect());
$allEvents          = $calendarRepository->getAllCalendarInfo();

if (!function_exists('escStudent')) {
	function escStudent(?string $value): string
	{
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}
}

// Formats a single datetime. Shows the time only when it isn't midnight.
if (!function_exists('calendarFormatDate')) {
	function calendarFormatDate(?string $datetime): string
	{
		if (empty($datetime)) {
			return '';
		}
		try {
			$d = new DateTimeImmutable($datetime);
		} catch (Exception $e) {
			return '';
		}
		return $d->format('H:i:s') === '00:00:00'
			? $d->format('F j, Y')
			: $d->format('F j, Y \a\t g:i A');
	}
}

// Formats a start/end range, e.g. "October 25 – October 31, 2026"
if (!function_exists('calendarDateText')) {
	function calendarDateText(?string $start, ?string $end): string
	{
		if (empty($start)) {
			return '';
		}
		try {
			$s = new DateTimeImmutable($start);
			$e = !empty($end) ? new DateTimeImmutable($end) : null;
		} catch (Exception $ex) {
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

// Find the requested event 
$requestedId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$event       = null;

foreach ($allEvents as $row) {
	if ($requestedId === 0 || (int)$row['id'] === $requestedId) {
		$event = $row;
		break;
	}
}

if ($event === null) {
	http_response_code(404);
} else {
	// Image (stored as a filename, so the admin folder path is added here)
	$eventImage = !empty($event['featured_image'])
		? 'image.php?f=' . rawurlencode((string)$event['featured_image'])
		: 'images/UCC2.png';

	// keep basic formatting tags
	$rawDescription = (string)($event['description'] ?? '');
	if ($rawDescription !== strip_tags($rawDescription)) {
		$eventDescription = strip_tags($rawDescription, '<p><br><strong><em><ul><ol><li><a><h3><h4>');
	} else {
		$eventDescription = nl2br(htmlspecialchars($rawDescription, ENT_QUOTES, 'UTF-8'));
	}

	// Event information grid
	$startsAt = $event['starts_at'] ?? null;
	$endsAt   = $event['ends_at'] ?? null;
	$details  = [];

	$sameDay = !empty($startsAt) && !empty($endsAt)
		&& substr((string)$startsAt, 0, 10) === substr((string)$endsAt, 0, 10);

	if (!empty($startsAt) && (empty($endsAt) || $sameDay)) {
		$details[] = ['DATE', calendarFormatDate($startsAt)];
	} else {
		if (!empty($startsAt)) {
			$details[] = ['STARTS', calendarFormatDate($startsAt)];
		}
		if (!empty($endsAt)) {
			$details[] = ['ENDS', calendarFormatDate($endsAt)];
		}
	}
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>UCC | Calendar</title>
	<link rel="icon" type="image/png" href="images/UCC2.png">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="assets/calendar.css">
	<link rel="stylesheet" href="assets/utility-bar.css">
	<link rel="stylesheet" href="assets/navbar.css">
	<link rel="stylesheet" href="assets/footer.css">
	<style>
		html, body { min-height: 100%; }
		body { min-height: 100vh; display: flex; flex-direction: column; }
		.calendar-page { flex: 1 0 auto; min-height: 0; }
	</style>
</head>
<body>
	<!-- PAGE DOTS -->
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


		<section class="calendar-content-section">
			<div class="container">
				<a href="homepage.php" class="calendar-back-link">
					<i data-lucide="arrow-left"></i>
					<span>Back to Homepage</span>
				</a>

				<div id="calendarEventContainer">
					<?php if ($event !== null): ?>
						<article class="calendar-detail-card">
							<div class="calendar-detail-image">
								<img src="<?= escStudent($eventImage) ?>" alt="<?= escStudent($event['title']) ?>">
							</div>


							<div class="calendar-detail-body">
								<h2><?= escStudent($event['title']) ?></h2>


								<div class="calendar-detail-date">
									<span>◷</span>
									<?= escStudent(calendarDateText($startsAt, $endsAt)) ?>
								</div>


								<div class="calendar-detail-divider"></div>

								<?php if (trim(strip_tags($eventDescription)) !== ''): ?>
									<div class="calendar-detail-description"><?= $eventDescription ?></div>
								<?php endif; ?>

								<?php if (!empty($details)): ?>
									<div class="calendar-detail-information">
										<h3>EVENT INFORMATION</h3>
										<div class="calendar-detail-grid">
											<?php foreach ($details as $detail): ?>
												<div class="calendar-detail-item">
													<span><?= escStudent($detail[0]) ?></span>
													<strong><?= escStudent($detail[1]) ?></strong>
												</div>
											<?php endforeach; ?>
										</div>
									</div>
								<?php endif; ?>

								
							</div>
						</article>
					<?php else: ?>
						<div class="calendar-not-found">
							<h2>Calendar Event Not Found</h2>
							<p>The calendar event you are looking for is unavailable.</p>
							<a href="homepage.php">Return to Homepage</a>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</section>
	</main>

	
	<?php include __DIR__ . '/includes/footer.php'; ?>
	<button class="back-top" id="backTop" aria-label="Back to top">↑</button>

	<!-- SCRIPTS -->
	<script src="js/script.js"></script>
	<script src="https://unpkg.com/lucide@latest"></script>
	<script>
		if (window.lucide) {
			lucide.createIcons();
		}
	</script>
</body>
</html>
