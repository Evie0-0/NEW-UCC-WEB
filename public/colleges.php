<?php
require_once __DIR__ . '/../app/Repositories/PublicCollegesRepository.php';
require_once __DIR__ . '/../app/Repositories/PublicProgramsRepository.php';

use Core\Database;
use Repo\PublicCollegesRepository;
use Repo\PublicProgramsRepository;

$collegeName = trim((string) ($_GET['college'] ?? ''));

$conn              = Database::connect();
$collegeRepository = new PublicCollegesRepository($conn);
$programRepository = new PublicProgramsRepository($conn);

$rows        = [];
$programRows = [];
$baseCollege = [];

/**
 * Fetch the college record itself from `units`, independent of unit_info rows.
 */
function fetchCollegeBase($conn, string $name): array {
    $sql = 'SELECT id, name, logo, dean, dean_image FROM units WHERE name = ? AND active = 1 LIMIT 1';

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

try {
    if ($collegeName !== '') {
        $rows = $collegeRepository->collegesInfo($collegeName);
        $baseCollege = fetchCollegeBase($conn, $collegeName);

        if (!empty($rows) || !empty($baseCollege)) {
            $programRows = $programRepository->getProgramsByCollegeName($collegeName);
        }
    }
} catch (Throwable $e) {
    error_log('College page error: ' . $e->getMessage());
    $rows        = [];
    $programRows = [];
}

// Not found only if the college itself doesn't exist
$notFound = empty($rows) && empty($baseCollege);
if ($notFound) {
    http_response_code(404);
}

function programSlug(string $name): string {
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
    return $slug !== '' ? $slug : 'program';
}



function collegeImage($file): string {
    $file = trim((string) $file);

    if ($file === '') {
        return 'images/UCC2.png';
    }

    // Keep externally hosted and public-root images as-is.
    if (preg_match('#^(https?:)?//#i', $file) || $file[0] === '/') {
        return $file;
    }

    // image.php accepts a filename only, even if the DB contains an old admin path.
    $filename = basename(str_replace('\\', '/', $file));
    return $filename !== ''
        ? 'image.php?f=' . rawurlencode($filename)
        : 'images/UCC2.png';
}

/*
 * Per-college CSS classes. Keys must match units.name.
 */
$pageClassByCollege = [
    'College of Business and Accountancy'   => 'cba-page',
    'College of Criminal Justice Education' => 'ccje-page',
    'College of Education'                  => 'ccje-page coe-page',
    'College of Engineering'                => 'ccje-page coe-page',
    'Graduate School'                       => 'ccje-page graduate-page',
    'College of Law'                        => 'ccje-page law-page',
    'College of Liberal Arts and Sciences'  => 'ccje-page clas-page',
];

// Programs from the database
$programs = array_map(static fn($p) => [
    'id'      => (int) $p['id'],
    'name'    => (string) $p['name'],
    'slug'    => programSlug((string) $p['name']),
    'vision'  => (string) ($p['vision'] ?? ''),
    'mission' => (string) ($p['mission'] ?? ''),
], $programRows);

// College-level fields are identical on every info row; fall back to the
// base `units` record when there are no info rows.
$first = $rows[0] ?? [];
$name  = (string) ($first['college_name'] ?? $baseCollege['name'] ?? '');

$collegePage = [
    'title'       => $name !== '' ? $name : 'College not found',
    'page_title'  => $name !== '' ? $name : 'College not found',
    'page_class'  => $pageClassByCollege[$name] ?? 'ccje-page',
    'query'       => $collegeName, // the ?college= value; the navbar reuses $collegeName, so don't read it after the includes
    'logo'        => collegeImage($first['logo'] ?? $baseCollege['logo'] ?? ''),
    'dean'        => $first['dean'] ?? $baseCollege['dean'] ?? '',
    'dean_title'  => $name !== '' ? 'Dean, ' . $name : '',
    'dean_image'  => collegeImage($first['dean_image'] ?? $baseCollege['dean_image'] ?? ''),
    'description' => $name !== '' ? 'Explore the academic programs offered by ' . $name . '.' : '',
    'infos'       => [], // every unit_info row: name = heading, description = text
    'programs'    => $programs,
];

foreach ($rows as $row) {
    $heading = trim((string) ($row['info_name'] ?? ''));
    $text    = trim((string) ($row['description'] ?? ''));

    // Skip empty rows (e.g. from a LEFT JOIN with no info)
    if ($heading === '' && $text === '') {
        continue;
    }

    $collegePage['infos'][] = [
        'heading' => $heading,
        'text'    => $text,
    ];
}

function collegeEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UCC | <?= collegeEscape($collegePage['page_title']) ?></title>
    <link rel="icon" type="image/png" href="images/UCC2.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/academics.css">
    <link rel="stylesheet" href="assets/utility-bar.css">
    <link rel="stylesheet" href="assets/navbar.css">
    <link rel="stylesheet" href="assets/footer.css">
    <link rel="stylesheet" href="colleges-events.css">
    <style>
        html, body { min-height: 100%; }
        body { min-height: 100vh; display: flex; flex-direction: column; }
        body > main { flex: 1 0 auto; min-height: 0; }
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


    <main class="<?= collegeEscape($collegePage['page_class']) ?>">
        <section class="cba-hero">
            <div class="cba-building">
                <img src="images/FOOTER.jpg" alt="University of Caloocan City campus building">
            </div>


            <div class="cba-hero-inner">
                <div class="cba-dean">
                    <div class="cba-dean-photo">
                        <?php if ($collegePage['dean_image'] !== ''): ?>
                            <img src="<?= collegeEscape($collegePage['dean_image']) ?>" alt="<?= collegeEscape($collegePage['dean']) ?>">
                        <?php endif; ?>
                    </div>
                    <h2><?= collegeEscape($collegePage['dean']) ?></h2>
                    <p><?= collegeEscape($collegePage['dean_title']) ?></p>
                </div>


                <div class="cba-hero-content">
                    <div class="cba-breadcrumb">
                        <span>ACADEMICS</span>
                        <b>/</b>
                        <span>COLLEGE PROFILE</span>
                        <div class="cba-breadcrumb-dots">
                            <i></i><i></i><i></i><span></span>
                        </div>
                    </div>


                    <h1><?= collegeEscape($collegePage['title']) ?></h1>
                    <a class="cba-hero-events-btn" href="colleges-events.php?college=<?= rawurlencode($collegePage['query']) ?>">
                        <i data-lucide="calendar-days" aria-hidden="true"></i>
                        <span>COLLEGE EVENTS</span>
                        <b aria-hidden="true">→</b>
                    </a>


                </div>
            </div>
            <div class="cba-hero-wave"></div>
        </section>


        <section class="cba-about ccje-about" id="about-college">
            <div class="container">


                <div class="cba-section-title">
                    <h2>ABOUT THE COLLEGE</h2>
                    <span></span>
                </div>


                <div class="cba-about-grid">
                    <div class="cba-about-logo">
                        <?php if ($collegePage['logo'] !== ''): ?>
                            <img src="<?= collegeEscape($collegePage['logo']) ?>" alt="<?= collegeEscape($collegePage['title']) ?> Logo">
                        <?php endif; ?>
                    </div>


                    <?php foreach ($collegePage['infos'] as $info): ?>
                        <div class="cba-about-block">
                            <div class="cba-quote">“</div>
                            <h3><?= collegeEscape(strtoupper($info['heading'])) ?></h3>
                            <p><?= nl2br(collegeEscape($info['text'])) ?></p>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($collegePage['infos'])): ?>
                        <p>No information has been provided for this college yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>


        <section class="cba-programs" id="programs">
            <div class="container">

                <div class="cba-program-heading">
                    <div class="cba-heading-line"></div>
                    <h2>PROGRAMS</h2>
                    <div class="cba-heading-dots">
                        <i></i><i></i><i></i>
                    </div>
                    <p><?= collegeEscape($collegePage['description']) ?></p>
                </div>


                <div class="cba-program-grid">
                    <?php if (empty($collegePage['programs'])): ?>
                        <p>No programs are listed for this college yet.</p>
                    <?php endif; ?>

                    <?php foreach ($collegePage['programs'] as $program): ?>
                        <article class="cba-program-card"

                            data-program="<?= collegeEscape($program['slug']) ?>"
                            data-vision="<?= collegeEscape($program['vision']) ?>"
                            data-mission="<?= collegeEscape($program['mission']) ?>">
                            <h3><?= collegeEscape($program['name']) ?></h3>

                            <div class="cba-program-actions">
                                <a href="#" class="program-read-more">Read more <span>→</span></a>
                                <a href="program-events.php?program=<?= (int) $program['id'] ?>" class="program-events-link">Events <span>↗</span></a>
                            </div>

                        </article>
                    <?php endforeach; ?>
                </div>

            </div>
        </section>


        <section class="cba-quick-links">
            <div class="container cba-quick-grid">


                <a href="#programs">
                    <div class="cba-quick-icon"><i data-lucide="graduation-cap"></i></div>

                    <div>
                        <strong>EXPLORE A PROGRAM</strong>
                        <span>Browse <?= collegeEscape($collegePage['title']) ?> programs</span>
                    </div>

                    <b>→</b>
                </a>


                <a href="colleges-events.php?college=<?= rawurlencode($collegePage['query']) ?>">
                    <div class="cba-quick-icon"><i data-lucide="calendar-days"></i></div>
                    <div>
                        <strong>COLLEGE EVENTS</strong>
                        <span>Explore events for <?= collegeEscape($collegePage['title']) ?></span>
                    </div>
                    <b>→</b>
                </a>


                <a href="contacts.php#office-directory">
                    <div class="cba-quick-icon"><i data-lucide="building-2"></i></div>
                    <div>
                        <strong>ACADEMIC OFFICE</strong>
                        <span>Contact the college</span>
                    </div>
                    <b>→</b>
                </a>


                <a href="admission.php">
                    <div class="cba-quick-icon"><i data-lucide="users-round"></i></div>
                    <div>
                        <strong>ADMISSIONS</strong>
                        <span>Start your UCC journey</span>
                    </div>
                    <b>→</b>
                </a>


            </div>
        </section>
    </main>


    <div class="program-modal" id="programModal" aria-hidden="true">
        <div class="program-modal-backdrop" data-modal-close></div>
        <div class="program-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="programModalTitle">

            <div class="program-modal-header">

                <div class="program-modal-heading">
                    <span class="program-modal-label">ACADEMIC PROGRAM</span>
                </div>
                <button class="program-modal-close" type="button" aria-label="Close program details" data-modal-close>×</button>

            </div>


            <div class="program-modal-body">

                <div class="program-modal-accent"></div>
                <h2 id="programModalTitle"></h2>

                <div class="program-modal-section">
                    <div class="program-modal-section-heading">
                        <span class="program-modal-section-line"></span>
                        <h3>VISION</h3>
                    </div>
                    <p id="programModalVision"></p>
                </div>


                <div class="program-modal-section">
                    <div class="program-modal-section-heading">
                        <span class="program-modal-section-line"></span>
                        <h3>MISSION</h3>
                    </div>
                    <p id="programModalMission"></p>
                </div>

            </div>


            <div class="program-modal-footer">
                <span><?= collegeEscape(strtoupper($collegePage['title'])) ?></span>
            </div>
        </div>
    </div>


    <?php include __DIR__ . '/includes/footer.php'; ?>


    <button class="back-top" id="backTop" aria-label="Back to top">↑</button>
    <script src="js/script.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        if (window.lucide) {
            window.lucide.createIcons();
        }
    </script>
    <script src="js/academics.js"></script>
</body>
</html>
