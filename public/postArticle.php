<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Repositories/PublicUniversityNewsRepository.php';

use Core\Database;
use Repo\PublicUniversityNewsRepository;

function escapeArticle(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function formatArticleDate(?string $date): string
{
    if (empty($date)) {
        return 'Date unavailable';
    }

    try {
        return (new DateTimeImmutable($date))->format('F j, Y');
    } catch (Throwable $e) {
        return 'Date unavailable';
    }
}

function shortenArticleText(string $text, int $length = 160): string
{
    if (function_exists('mb_substr')) {
        return mb_substr($text, 0, $length, 'UTF-8');
    }

    return substr($text, 0, $length);
}

function sanitizeArticleContent(string $html): string
{
    if (trim($html) === '') {
        return '<p>No article content is available.</p>';
    }

    if (!class_exists(DOMDocument::class)) {
        return nl2br(escapeArticle(strip_tags($html)));
    }

    $previousErrorSetting = libxml_use_internal_errors(true);

    $dom = new DOMDocument('1.0', 'UTF-8');

    $dom->loadHTML(
        '<?xml encoding="UTF-8"><div id="article-root">' . $html . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );

    $xpath = new DOMXPath($dom);
    $root = $xpath->query('//*[@id="article-root"]')->item(0);

    if (!$root) {
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorSetting);

        return nl2br(escapeArticle(strip_tags($html)));
    }

    $allowedTags = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u',
        'ul', 'ol', 'li', 'h2', 'h3', 'h4',
        'blockquote', 'a', 'img'
    ];

    $removeCompletely = [
        'script', 'style', 'iframe', 'object',
        'embed', 'form', 'input', 'button',
        'svg', 'math'
    ];

    $nodes = [];

    foreach ($xpath->query('.//*', $root) as $node) {
        $nodes[] = $node;
    }

    foreach (array_reverse($nodes) as $node) {
        if (!$node instanceof DOMElement) {
            continue;
        }

        $tag = strtolower($node->tagName);

        if (in_array($tag, $removeCompletely, true)) {
            $node->parentNode?->removeChild($node);
            continue;
        }

        if (!in_array($tag, $allowedTags, true)) {
            $parent = $node->parentNode;

            if ($parent) {
                while ($node->firstChild) {
                    $parent->insertBefore($node->firstChild, $node);
                }

                $parent->removeChild($node);
            }

            continue;
        }

        $attributes = [];

        foreach ($node->attributes as $attribute) {
            $attributes[] = $attribute->name;
        }

        foreach ($attributes as $attributeName) {
            if (!in_array($attributeName, ['href', 'src', 'alt', 'title'], true)) {
                $node->removeAttribute($attributeName);
            }
        }

        if ($tag === 'a') {
            $href = trim($node->getAttribute('href'));
            $scheme = strtolower((string) parse_url($href, PHP_URL_SCHEME));

            if (
                $href === '' ||
                !in_array($scheme, ['', 'http', 'https', 'mailto'], true) ||
                str_starts_with($href, '//')
            ) {
                $node->removeAttribute('href');
            } else {
                $node->setAttribute('rel', 'noopener noreferrer');
                $node->setAttribute('target', '_blank');
            }
        }

        if ($tag === 'img') {
            $src = trim($node->getAttribute('src'));
            $scheme = strtolower((string) parse_url($src, PHP_URL_SCHEME));

            if (
                $src === '' ||
                !in_array($scheme, ['', 'http', 'https'], true) ||
                str_starts_with($src, '//')
            ) {
                $node->parentNode?->removeChild($node);
            } else {
                $node->setAttribute('loading', 'lazy');
            }
        }
    }

    $result = '';

    foreach ($root->childNodes as $child) {
        $result .= $dom->saveHTML($child);
    }

    libxml_clear_errors();
    libxml_use_internal_errors($previousErrorSetting);

    return $result;
}

/* GET ARTICLE, CALENDAR EVENT, PROGRAM EVENT, OR COLLEGE EVENT */

$postId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$contentType = strtolower(trim((string) ($_GET['type'] ?? 'news')));

$isCalendarEvent = $contentType === 'calendar';
$isProgramEvent = $contentType === 'program';
$isCollegeEvent = $contentType === 'college';

$isValidType = in_array(
    $contentType,
    ['news', 'calendar', 'program', 'college'],
    true
);

$collegeQuery = trim((string) ($_GET['college'] ?? ''));

$post = [];
$pageError = false;

/* BACK BUTTON DESTINATION */

$backUrl = $isCalendarEvent
    ? 'calendar.php'
    : ($isProgramEvent
        ? 'javascript:history.back()'
        : ($isCollegeEvent
            ? 'colleges-events.php' . ($collegeQuery !== ''
                ? '?college=' . rawurlencode($collegeQuery)
                : '')
            : 'news.php'));

$backLabel = $isCalendarEvent
    ? 'BACK TO ACADEMIC CALENDAR'
    : ($isProgramEvent
        ? 'BACK TO PROGRAM EVENTS'
        : ($isCollegeEvent
            ? 'BACK TO COLLEGE EVENTS'
            : 'BACK TO NEWS & UPDATES'));

/* VALIDATE ARTICLE REQUEST */

if (
    !$isValidType ||
    $postId === false ||
    $postId === null ||
    $postId < 1 ||
    ($isCollegeEvent && $collegeQuery === '')
) {
    $pageError = true;
} else {
    try {
        $pdo = Database::connect();

        /* ACADEMIC CALENDAR EVENT */

        if ($isCalendarEvent) {
            $sql = "
                SELECT
                    id,
                    title,
                    description,
                    featured_image,
                    views,
                    published_at,
                    starts_at,
                    ends_at
                FROM calendar
                WHERE id = :id
                    AND status = 'published'
                    AND active = 1
                    AND published_at <= NOW()
                LIMIT 1
            ";

            $statement = $pdo->prepare($sql);
            $statement->execute([
                ':id' => (int) $postId
            ]);

            $event = $statement->fetch(PDO::FETCH_ASSOC);

            if ($event) {
                $post = [
                    'id' => $event['id'],
                    'title' => $event['title'],
                    'category' => 'Academic Calendar',
                    'excerpt' => '',
                    'content' => $event['description'] ?? '',
                    'featured_image' => $event['featured_image'] ?? '',
                    'published_at' => $event['starts_at'] ?: $event['published_at'],
                    'views' => $event['views'] ?? 0
                ];
            } else {
                $pageError = true;
            }

        /* PROGRAM EVENT */

        } elseif ($isProgramEvent) {
            $sql = "
                SELECT
                    pp.id,
                    pp.title,
                    pp.excerpt,
                    pp.content,
                    pp.featured_image,
                    pp.published_at,
                    c.name AS category_name,
                    pr.name AS program_name
                FROM program_posts pp
                INNER JOIN programs pr
                    ON pp.program_id = pr.id
                INNER JOIN categories c
                    ON pp.category_id = c.id
                WHERE pp.id = :id
                    AND pp.status = 'published'
                    AND pp.category_id = 8
                    AND pp.active = 1
                    AND (pp.expires_at IS NULL OR pp.expires_at > NOW())
                    AND pp.published_at <= NOW()
                    AND pr.status = 'active'
                    AND pr.active = 1
                LIMIT 1
            ";

            $statement = $pdo->prepare($sql);
            $statement->execute([
                ':id' => (int) $postId
            ]);

            $event = $statement->fetch(PDO::FETCH_ASSOC);

            if ($event) {
                $post = [
                    'id' => $event['id'],
                    'title' => $event['title'],
                    'category' => $event['category_name'] ?: 'Program Event',
                    'excerpt' => $event['excerpt'] ?? '',
                    'content' => $event['content'] ?? '',
                    'featured_image' => $event['featured_image'] ?? '',
                    'published_at' => $event['published_at'] ?? null,
                    'program_name' => $event['program_name'] ?? '',
                    'views' => 0
                ];
            } else {
                $pageError = true;
            }

        /* COLLEGE EVENT */

        } elseif ($isCollegeEvent) {
            $sql = "
                SELECT
                    up.id,
                    up.title,
                    up.excerpt,
                    up.content,
                    up.featured_image,
                    up.published_at,
                    c.name AS category_name,
                    u.name AS college_name
                FROM unit_posts up
                INNER JOIN units u
                    ON up.unit_id = u.id
                INNER JOIN categories c
                    ON up.category_id = c.id
                WHERE up.id = :id
                    AND u.name = :college
                    AND up.category_id = 7
                    AND up.status = 'published'
                    AND up.active = 1
                    AND (up.expires_at IS NULL OR up.expires_at > NOW())
                    AND up.published_at <= NOW()
                    AND u.status = 'active'
                    AND u.active = 1
                LIMIT 1
            ";

            $statement = $pdo->prepare($sql);

            $statement->execute([
                ':id' => (int) $postId,
                ':college' => $collegeQuery
            ]);

            $event = $statement->fetch(PDO::FETCH_ASSOC);

            if ($event) {
                $post = [
                    'id' => $event['id'],
                    'title' => $event['title'],
                    'category' => $event['category_name'] ?: 'College Event',
                    'excerpt' => $event['excerpt'] ?? '',
                    'content' => $event['content'] ?? '',
                    'featured_image' => $event['featured_image'] ?? '',
                    'published_at' => $event['published_at'] ?? null,
                    'college_name' => $event['college_name'] ?? '',
                    'views' => 0
                ];
            } else {
                $pageError = true;
            }

        /* UNIVERSITY NEWS ARTICLE */

        } else {
            $repository = new PublicUniversityNewsRepository($pdo);

            $post = $repository->getPostById((int) $postId);

            if (empty($post)) {
                $pageError = true;
            }
        }

    } catch (Throwable $e) {
        error_log('Post article page error: ' . $e->getMessage());
        $pageError = true;
    }
}

if ($pageError) {
    http_response_code(404);
}

/* ARTICLE INFORMATION */

$title = $pageError
    ? 'Article Not Found'
    : (string) ($post['title'] ?? 'Untitled Article');

$category = $pageError
    ? ''
    : trim((string) ($post['category'] ?? 'University Update'));

$excerpt = $pageError
    ? ''
    : trim((string) ($post['excerpt'] ?? ''));

$publishedDate = $pageError
    ? ''
    : formatArticleDate($post['published_at'] ?? null);

$featuredImage = '';

if (!$pageError) {
    $imageFile = trim((string) ($post['featured_image'] ?? ''));

    if ($imageFile !== '') {
        $featuredImage = 'image.php?f=' . rawurlencode(basename(str_replace('\\', '/', $imageFile)));
    }
}

$articleContent = $pageError
    ? ''
    : sanitizeArticleContent((string) ($post['content'] ?? ''));

$metaDescription = $excerpt !== ''
    ? $excerpt
    : 'Read the latest updates and stories from the University of Caloocan City.';

$programName = trim((string) ($post['program_name'] ?? ''));

$collegeName = trim((string) ($post['college_name'] ?? ''));

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="<?= escapeArticle(shortenArticleText($metaDescription)) ?>">

    <title><?= escapeArticle($title) ?> | University of Caloocan City</title>

    <link rel="icon" type="image/png" href="images/UCC2.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/utility.css">
    <link rel="stylesheet" href="assets/utility-bar.css">
    <link rel="stylesheet" href="assets/navbar.css">
    <link rel="stylesheet" href="assets/postArticle.css">
    <link rel="stylesheet" href="assets/footer.css">
</head>

<body class="post-article-page">

    <?php include __DIR__ . '/includes/utility-bar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="article-page">

        <?php if ($pageError): ?>

            <section class="article-not-found">

                <div class="article-not-found-icon">
                    <i data-lucide="file-x"></i>
                </div>

                <span class="article-eyebrow">
                    UNIVERSITY OF CALOOCAN CITY
                </span>

                <h1>Article Not Found</h1>

                <p>
                    The article or event you're looking for may have been removed,
                    expired, or is currently unavailable.
                </p>

                <a href="<?= escapeArticle($backUrl) ?>" class="article-back-button">
                    <i data-lucide="arrow-left"></i>
                    <?= escapeArticle($backLabel) ?>
                </a>

            </section>

        <?php else: ?>

            <!-- ARTICLE HERO -->

            <section class="article-hero">

                <video
                    class="article-hero-video"
                    autoplay
                    muted
                    loop
                    playsinline
                    preload="metadata"
                    aria-hidden="true"
                >
                    <source src="images/UCC-NEWS-HERO-BG.mp4" type="video/mp4">
                </video>

                <div class="article-hero-overlay"></div>

                <div class="article-hero-decoration article-decoration-one"></div>
                <div class="article-hero-decoration article-decoration-two"></div>

                <div class="article-hero-inner">

                    <div class="article-breadcrumb">

                        <a href="<?= escapeArticle($backUrl) ?>">
                            <?= $isCalendarEvent
                                ? 'ACADEMIC CALENDAR'
                                : ($isProgramEvent
                                    ? 'PROGRAM EVENTS'
                                    : ($isCollegeEvent
                                        ? 'COLLEGE EVENTS'
                                        : 'UPDATES')) ?>
                        </a>

                        <i data-lucide="chevron-right"></i>

                        <span><?= escapeArticle(strtoupper($category)) ?></span>

                    </div>

                    <div class="article-hero-content">

                        <span class="article-category">
                            <span class="article-category-dot"></span>
                            <?= escapeArticle(strtoupper($category)) ?>
                        </span>

                        <h1><?= escapeArticle($title) ?></h1>

                        <?php if ($excerpt !== ''): ?>
                            <p class="article-hero-excerpt">
                                <?= escapeArticle($excerpt) ?>
                            </p>
                        <?php endif; ?>

                        <div class="article-hero-meta">

                            <span>
                                <i data-lucide="calendar-days"></i>
                                <?= escapeArticle($publishedDate) ?>
                            </span>

                            <span class="article-meta-divider"></span>

                            <span>
                                <i data-lucide="landmark"></i>

                                <?= $isProgramEvent && $programName !== ''
                                    ? escapeArticle($programName)
                                    : ($isCollegeEvent && $collegeName !== ''
                                        ? escapeArticle($collegeName)
                                        : 'UCC Official Updates') ?>
                            </span>

                        </div>

                    </div>

                    <div class="article-hero-bottom">
                        <span>UNIVERSITY STORIES &amp; UPDATES</span>
                        <span class="article-hero-line"></span>
                    </div>

                </div>

            </section>

            <!-- ARTICLE CONTENT -->

            <section class="article-main-section">

                <div class="article-main-container">

                    <div class="article-layout">

                        <!-- MAIN ARTICLE -->

                        <article class="article-content-column">

                            <?php if ($featuredImage !== ''): ?>

                                <figure class="article-featured-image">

                                    <img
                                        src="<?= escapeArticle($featuredImage) ?>"
                                        alt="<?= escapeArticle($title) ?>"
                                        fetchpriority="high"
                                    >

                                    <figcaption>
                                        UNIVERSITY OF CALOOCAN CITY
                                    </figcaption>

                                </figure>

                            <?php endif; ?>

                            <div class="article-body">

                                <div class="article-body-heading">

                                    <span class="article-body-kicker">
                                        <?= escapeArticle(strtoupper($category)) ?>
                                    </span>

                                    <h2><?= escapeArticle($title) ?></h2>

                                    <div class="article-body-date">
                                        <i data-lucide="calendar-days"></i>
                                        <?= escapeArticle($publishedDate) ?>
                                    </div>

                                </div>

                                <?php if ($excerpt !== ''): ?>

                                    <p class="article-body-excerpt">
                                        <?= escapeArticle($excerpt) ?>
                                    </p>

                                <?php endif; ?>

                                <div class="article-rich-content">
                                    <?= $articleContent ?>
                                </div>

                            </div>

                            <div class="article-end-mark">
                                <span></span>
                                <i data-lucide="landmark"></i>
                                <span></span>
                            </div>

                            <div class="article-bottom-navigation">

                                <a href="<?= escapeArticle($backUrl) ?>" class="article-back-button">
                                    <i data-lucide="arrow-left"></i>
                                    <?= escapeArticle($backLabel) ?>
                                </a>

                            </div>

                        </article>

                        <!-- SIDEBAR -->

                        <aside class="article-sidebar">

                            <div class="article-sidebar-card">

                                <div class="article-sidebar-icon">
                                    <i data-lucide="landmark"></i>
                                </div>

                                <span class="article-sidebar-kicker">
                                    UNIVERSITY OF CALOOCAN CITY
                                </span>

                                <h3>Committed to Excellence.</h3>

                                <div class="article-sidebar-divider"></div>

                                <p>
                                    Stay connected with the latest stories,
                                    announcements, and developments from
                                    the UCC community.
                                </p>

                                <a href="<?= escapeArticle($backUrl) ?>" class="article-sidebar-link">

                                    <?= $isCalendarEvent
                                        ? 'EXPLORE ACADEMIC CALENDAR'
                                        : ($isProgramEvent
                                            ? 'EXPLORE PROGRAM EVENTS'
                                            : ($isCollegeEvent
                                                ? 'EXPLORE COLLEGE EVENTS'
                                                : 'EXPLORE MORE UPDATES')) ?>

                                    <i data-lucide="arrow-up-right"></i>

                                </a>

                            </div>

                            <div class="article-sidebar-note">

                                <i data-lucide="info"></i>

                                <div>

                                    <strong>Official UCC Information</strong>

                                    <p>
                                        This <?= ($isCalendarEvent || $isCollegeEvent)
                                            ? 'event'
                                            : 'article' ?> is published through
                                        the University of Caloocan City's
                                        official web portal.
                                    </p>

                                </div>

                            </div>

                        </aside>

                    </div>

                </div>

            </section>

        <?php endif; ?>

    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <button class="back-top" id="backTop" aria-label="Back to top">
        ↑
    </button>

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

</body>
</html>
