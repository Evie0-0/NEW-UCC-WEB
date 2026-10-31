<?php
require_once __DIR__ . '/../app/Repositories/PublicCollegesRepository.php';

use Core\Database;
use Repo\PublicCollegesRepository;

function collegeEventsEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/*
 * The database stores only the image file name (featured_image).
 * Uploaded images live in admin/assets/images/. Values that already
 * look like a URL or a path are left alone.
 */
function eventsImage($file): string {
    $file = trim((string) $file);

    if ($file === '') {
        return 'images/UCC2.png';
    }

    if (preg_match('#^https?://#i', $file) || $file[0] === '/') {
        return $file;
    }

    // image.php serves files from storage/uploads using the stored filename.
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
        ['yr',  31536000],
        ['mo',  2592000],
        ['day', 86400],
        ['hr',  3600],
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
        $row    = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        return $row ?: [];
    }

    return [];
}

// This is the ?college= value, which is units.name (the same value colleges.php uses)
$requestedCollege = trim((string) ($_GET['college'] ?? ''));

$infoRows    = [];
$newsRows    = [];
$baseCollege = [];
$newsRepository = null;

try {
    $conn           = Database::connect();
    $newsRepository = new PublicCollegesRepository($conn);

    if ($requestedCollege !== '') {
        // Look up the college itself so it is found even with no unit_info rows
        $baseCollege = eventsFetchCollegeBase($conn, $requestedCollege);

        // Info rows (may be empty)
        $infoRows = $newsRepository->collegesInfo($requestedCollege);
    }
} catch (Throwable $e) {
    error_log('College events page error: ' . $e->getMessage());
}

$collegeExists = !empty($baseCollege) || !empty($infoRows);

// Separate try: if the news query fails, the college still shows, just with no events
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

$events = [];

foreach ($newsRows as $row) {
    $events[] = [
        'id'           => (int) $row['id'],
        'title'        => (string) $row['title'],
        'excerpt'      => (string) ($row['excerpt'] ?? ''),
        'content'      => (string) ($row['content'] ?? ''),
        'image'        => eventsImage($row['featured_image'] ?? ''),
        'date'         => eventsTimeAgo($row['published_at'] ?? null),
    ];
}

// Name comes from info rows if present, otherwise from the units record
$collegeTitle = (string) ($infoRows[0]['college_name'] ?? $baseCollege['name'] ?? '');

/*
 * The navbar include reuses $college and $collegeName, so everything the
 * page needs after the includes is kept in $eventsPage.
 */
$eventsPage = [
    'title'      => $collegeTitle !== '' ? $collegeTitle : 'College not found',
    'query'      => $requestedCollege,
];

$eventsJson = json_encode($events, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        html, body { min-height: 100%; }
        body { min-height: 100vh; display: flex; flex-direction: column; }
        .college-events-page { flex: 1 0 auto; min-height: 0; }
        .college-events-heading { position: relative; padding-right: 190px; }
        .college-events-back { position: absolute; top: 0; right: 0; z-index: 4; }
        @media (max-width: 700px) {
            .college-events-heading { padding-right: 0; padding-top: 48px; }
            .college-events-back { top: 0; right: 0; }
        }
    </style>

</head>

<body>
    <?php include __DIR__ . '/includes/utility-bar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
    <div class="page-dots" aria-hidden="true">
        <span class="dot-pattern dot-1"></span>
        <span class="dot-pattern dot-2"></span>
        <span class="dot-pattern dot-3"></span>
        <span class="dot-pattern dot-4"></span>
    </div>



    <main class="news-page college-events-page">
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

            <div class="news-heading college-events-heading">
                <div>
                    <div class="college-events-eyebrow">
                        <a href="colleges.php?college=<?= rawurlencode($eventsPage['query']) ?>">ACADEMICS</a>
                        <span>/</span>
                        <span><?= collegeEventsEscape(strtoupper($eventsPage['title'])) ?></span>
                    </div>
                    <h1>COLLEGE EVENTS</h1>
                    <div class="news-heading-line"><span></span><i></i><span></span></div>
                    <p>Explore academic activities, announcements, achievements, and student events from <?= collegeEventsEscape($eventsPage['title']) ?>.</p>
                </div>
                <a class="college-events-back" href="colleges.php?college=<?= rawurlencode($eventsPage['query']) ?>"><span>←</span> BACK TO COLLEGE</a>
            </div>


            <div class="college-events-context">
                <span class="college-events-context-icon"><i data-lucide="graduation-cap"></i></span>
                <div>
                    <span class="college-events-context-label">COLLEGE / DEPARTMENT</span>
                    <strong><?= collegeEventsEscape($eventsPage['title']) ?></strong>
                    <small>Showing the latest news and events published by this college.</small>
                </div>
            </div>


            <section class="news-featured" id="featuredNews" aria-live="polite"></section>


            <div class="news-section-title">
                <div><span id="moreEventsTitle">MORE COLLEGE EVENTS</span><div class="news-mini-line"><i></i><i></i><i></i></div></div>
                <span class="college-events-count" id="eventsCount"></span>
            </div>


            <section class="news-grid-wrapper">
                <button type="button" class="news-slider-arrow news-slider-prev" id="newsPrev" aria-label="Previous events">‹</button>
                <div class="news-grid" id="newsGrid" aria-live="polite"></div>
                <button type="button" class="news-slider-arrow news-slider-next" id="newsNext" aria-label="Next events">›</button>
            </section>


            <div class="news-pagination" id="newsPagination" aria-label="Event pages"></div>


            <button type="button" class="news-view-all" id="viewAllEvents"><span>▤</span> VIEW ALL EVENTS</button>
        </section>
    </main>



    <div class="news-modal college-events-modal" id="newsModal" aria-hidden="true">


        <div class="news-modal-backdrop" data-news-modal-close></div>


        <div class="news-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="newsModalTitle">
            <div class="news-modal-header">
                <div class="news-modal-heading"><span class="news-modal-label" id="newsModalCategory">COLLEGE EVENT</span><h2 id="newsModalTitle">Event Title</h2></div>
                <button type="button" class="news-modal-close" data-news-modal-close aria-label="Close event popup">×</button>
            </div>


            <div class="news-modal-body">
                <figure class="news-modal-image">
                    <a href="#" id="newsModalImageLink" aria-label="View full-size event image"><img id="newsModalImage" src="" alt=""></a>
                </figure>
                <div class="news-modal-meta"><span class="news-modal-category" id="newsModalCategoryBadge">EVENT</span><span class="news-modal-date" id="newsModalDate">Published</span></div>
                <div class="college-events-modal-program" id="newsModalProgram" hidden></div>
                <div class="news-modal-accent"></div>
                <p class="news-modal-placeholder" id="newsModalDescription"></p>
            </div>


            <div class="news-modal-footer"><?= collegeEventsEscape($eventsPage['title']) ?> • College Events</div>
        </div>
    </div>


    <div class="news-image-lightbox" id="newsImageLightbox" aria-hidden="true">


        <div class="news-image-lightbox-backdrop" data-news-image-close></div>


        <div class="news-image-lightbox-content">
            <button type="button" class="news-image-lightbox-close" data-news-image-close aria-label="Close full-size image">×</button>
            <img id="newsLightboxImage" src="" alt="">
        </div>


    </div>

    <script type="application/json" id="collegeEventsData"><?= $eventsJson ?: '[]' ?></script>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="js/script.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="js/colleges-events.js" defer></script>
</body>
</html>
