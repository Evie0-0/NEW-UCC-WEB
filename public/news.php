<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/Repositories/PublicUniversityNewsRepository.php';
use Core\Database;
use Repo\PublicUniversityNewsRepository;
if (!function_exists('timeAgo')) {
    function timeAgo(?string $datetime): string
    {
        if (empty($datetime)) return 'recently';
        try {
            $then = new DateTimeImmutable($datetime);
        } catch (Exception $e) {
            return 'recently';
        }
        $now = new DateTimeImmutable('now');
        if ($then > $now) return 'just now';
        $diff = $then->diff($now);
        if ($diff->y > 0) return $diff->y === 1 ? 'a year ago' : $diff->y . ' years ago';
        if ($diff->m > 0) return $diff->m === 1 ? 'a month ago' : $diff->m . ' months ago';
        if ($diff->d > 0) return $diff->d === 1 ? 'a day ago' : $diff->d . ' days ago';
        if ($diff->h > 0) return $diff->h === 1 ? 'an hour ago' : $diff->h . ' hours ago';
        if ($diff->i > 0) return $diff->i === 1 ? 'a minute ago' : $diff->i . ' minutes ago';
        return 'just now';
    }
}
$newsPayload = [];
try {
    $repo = new PublicUniversityNewsRepository(Database::connect());
    $rows = $repo->getAllNews();
    $newsPayload = array_map(static function ($row) {
        $id = (int)($row['id'] ?? 0);
        $title = (string)($row['title'] ?? 'Untitled News');
        $category = strtoupper(trim((string)($row['category'] ?? 'NEWS')));
        $featuredImage = trim((string)($row['featured_image'] ?? ''));
        return [
            'id' => $id,
            'title' => $title,
            'category' => $category !== '' ? $category : 'NEWS',
            'date' => 'Published ' . timeAgo($row['published_at'] ?? null),
            'image' => $featuredImage !== ''
                ? 'image.php?f=' . rawurlencode($featuredImage)
                : '',
            'excerpt' => (string)($row['excerpt'] ?? ''),
            'content' => strip_tags(
                (string)($row['content'] ?? ''),
                '<p><br><strong><em><ul><ol><li><a><h2><h3><h4><blockquote>'
            ),
        ];
    }, $rows);
} catch (Throwable $e) {
    error_log('News page error: ' . $e->getMessage());
    $newsPayload = [];
}
$newsCategories = array_values(array_unique(array_filter(array_map(
    static fn($news) => trim((string)($news['category'] ?? '')),
    $newsPayload
))));
sort($newsCategories);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Read the latest university news, announcements, achievements, and developments from the University of Caloocan City.">
    <title>UCC | News and Updates</title>
    <link rel="icon" type="image/png" href="images/UCC2.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/news.css">
    <link rel="stylesheet" href="assets/utility.css">
    <link rel="stylesheet" href="assets/utility-bar.css">
    <link rel="stylesheet" href="assets/navbar.css">
    <link rel="stylesheet" href="assets/footer.css">
</head>
<body>
    <?php include __DIR__ . '/includes/utility-bar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
    <main class="news-page">
        <section class="news-hero">
            <div class="news-hero-inner">
                <div class="news-hero-copy">
                    <span class="news-hero-eyebrow">
                        <span></span> UNIVERSITY OF CALOOCAN CITY
                    </span>
                    <h1>UCC <span>NEWS</span></h1>
                    <p>Stay informed with the latest news, announcements, achievements, and developments from our university community.</p>
                    <div class="news-hero-note">
                        <i data-lucide="newspaper"></i>
                        <span>Stories, updates, and milestones from UCC</span>
                    </div>
                </div>
            </div>
            <div class="news-hero-decoration news-hero-decoration-one"></div>
            <div class="news-hero-decoration news-hero-decoration-two"></div>
        </section>
        <section class="news-search-section">
            <div class="news-search-card">
                <div class="news-search-heading">
                    <div class="news-search-icon"><i data-lucide="search"></i></div>
                    <div>
                        <span class="news-search-kicker">STAY INFORMED</span>
                        <h2>Find the latest updates</h2>
                        <p>Search through university news and announcements.</p>
                    </div>
                </div>
                <div class="news-search-form">
                    <div class="news-filter-field">
                        <label for="newsCategoryFilter">CATEGORY</label>
                        <div class="news-select-wrap">
                            <i data-lucide="layers"></i>
                            <select id="newsCategoryFilter">
                                <option value="">All Categories</option>
                                <?php foreach ($newsCategories as $category): ?>
                                    <option value="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <i data-lucide="chevron-down" class="news-select-chevron"></i>
                        </div>
                    </div>
                    <div class="news-search-controls">
                        <label for="newsSearchInput">SEARCH NEWS</label>
                        <div class="news-search-input-wrap">
                            <i data-lucide="search"></i>
                            <input type="search" id="newsSearchInput" class="news-search-input" placeholder="Search news, announcements, or keywords..." autocomplete="off">
                        </div>
                    </div>
                    <button type="button" class="news-search-button" id="newsSearchButton">
                        <span>Search</span>
                        <i data-lucide="arrow-right"></i>
                    </button>
                </div>
                <div class="news-search-footer">
                    <span><i data-lucide="info"></i> Browse the latest updates from UCC</span>
                    <button type="button" class="news-reset-link" id="newsResetButton">
                        <i data-lucide="rotate-ccw"></i> Reset filters
                    </button>
                </div>
            </div>
        </section>
        <section class="news-section">
            <div class="news-section-heading">
                <div>
                    <span class="news-section-kicker">FROM THE UNIVERSITY</span>
                    <h2>Latest <span>News &amp; Updates</span></h2>
                    <p>Discover what's happening across the University of Caloocan City.</p>
                </div>
                <div class="news-results-count" id="newsResultsCount">0 ARTICLES</div>
            </div>
            <section class="news-featured" id="featuredNews"></section>
            <div class="news-section-subheading" id="newsSectionTitle">
                <div>
                    <span class="news-section-kicker">MORE TO EXPLORE</span>
                    <h3>More News</h3>
                </div>
                <span class="news-subheading-line"></span>
            </div>
            <div class="news-grid" id="newsGrid"></div>
            <div class="news-no-posts" id="newsNoPosts" hidden>
                <div class="news-empty-icon"><i data-lucide="newspaper"></i></div>
                <h3>No news articles found</h3>
                <p>Try another search term or select a different category.</p>
                <button type="button" id="newsEmptyReset">CLEAR FILTERS</button>
            </div>
            <div class="news-pagination" id="newsPagination"></div>
            <button type="button" class="news-view-all" id="viewAllNews" hidden>
                <i data-lucide="layout-grid"></i>
                VIEW ALL NEWS
            </button>
        </section>
        <section class="news-help">
            <div class="news-help-icon"><i data-lucide="landmark"></i></div>
            <div class="news-help-content">
                <span>UNIVERSITY OF CALOOCAN CITY</span>
                <h2>Stay connected with UCC.</h2>
                <p>Keep up with university announcements, accomplishments, and stories from our community.</p>
            </div>
            <div class="news-help-decoration"><i data-lucide="book-open"></i></div>
        </section>
    </main>
    <?php include __DIR__ . '/includes/footer.php'; ?>
    <button class="back-top" id="backTop" aria-label="Back to top">↑</button>
    <script src="js/script.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        "use strict";
        const newsData = <?= json_encode(
            $newsPayload,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        ) ?>;
        const itemsPerPage = 10;
        let currentPage = 1;
        let showAllNews = false;
        let filteredNews = [...newsData];
        const featuredNews = document.getElementById("featuredNews");
        const newsGrid = document.getElementById("newsGrid");
        const newsPagination = document.getElementById("newsPagination");
        const newsSectionTitle = document.getElementById("newsSectionTitle");
        const newsResultsCount = document.getElementById("newsResultsCount");
        const newsNoPosts = document.getElementById("newsNoPosts");
        const newsSearchInput = document.getElementById("newsSearchInput");
        const newsCategoryFilter = document.getElementById("newsCategoryFilter");
        const newsSearchButton = document.getElementById("newsSearchButton");
        const newsResetButton = document.getElementById("newsResetButton");
        const newsEmptyReset = document.getElementById("newsEmptyReset");
        const viewAllNews = document.getElementById("viewAllNews");
        function escapeHtml(value) {
            return String(value ?? "").replace(/[&<>"']/g, character => ({
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#39;"
            })[character]);
        }
        function getArticleUrl(news) {
            return `postArticle.php?id=${encodeURIComponent(news.id)}`;
        }
        function getExcerpt(news) {
            const excerpt = String(news.excerpt || news.title || "");
            return excerpt.length > 180
                ? excerpt.substring(0, 180).trim() + "..."
                : excerpt;
        }
        function createNewsPlaceholder(newsIndex) {
            const number = String(newsIndex + 1).padStart(2, "0");
            return `
                <div class="news-image-placeholder">
                    <span class="news-placeholder-number">${number}</span>
                    <div class="news-placeholder-logo">
                        <img src="images/UCC2.png" alt="University of Caloocan City Logo">
                    </div>
                    <span class="news-placeholder-label">UNIVERSITY NEWS</span>
                </div>
            `;
        }
        function getPageItems() {
            if (showAllNews) return filteredNews;
            const start = (currentPage - 1) * itemsPerPage;
            return filteredNews.slice(start, start + itemsPerPage);
        }
        function getTotalPages() {
            return Math.max(1, Math.ceil(filteredNews.length / itemsPerPage));
        }
        function renderFeatured(news) {
            if (!news) {
                featuredNews.innerHTML = "";
                return;
            }
            const newsIndex = newsData.indexOf(news);
            const imageHTML = news.image
                ? `<img src="${escapeHtml(news.image)}" alt="${escapeHtml(news.title)}">`
                : createNewsPlaceholder(newsIndex);
            featuredNews.innerHTML = `
                <div class="news-featured-image">
                    ${imageHTML}
                    <span class="news-featured-image-label">
                        <i data-lucide="star"></i> FEATURED STORY
                    </span>
                </div>
                <div class="news-featured-content">
                    <span class="news-badge">${escapeHtml(news.category)}</span>
                    <span class="news-featured-kicker">LATEST UNIVERSITY UPDATE</span>
                    <h2>${escapeHtml(news.title)}</h2>
                    <div class="news-meta">
                        <i data-lucide="calendar-days"></i>
                        <strong>${escapeHtml(news.date)}</strong>
                    </div>
                    <div class="news-gold-divider"></div>
                    <p>${escapeHtml(getExcerpt(news))}</p>
                    <a href="${getArticleUrl(news)}" class="news-read-more">
                        READ FULL STORY <i data-lucide="arrow-up-right"></i>
                    </a>
                </div>
            `;
        }
        function createNewsCard(news) {
            const newsIndex = newsData.indexOf(news);
            const imageHTML = news.image
                ? `<img src="${escapeHtml(news.image)}" alt="${escapeHtml(news.title)}" loading="lazy">`
                : createNewsPlaceholder(newsIndex);
            return `
                <article class="news-card">
                    <a class="news-card-image-link" href="${getArticleUrl(news)}" aria-label="Read ${escapeHtml(news.title)}">
                        <div class="news-card-image">
                            ${imageHTML}
                            <span class="news-card-category">${escapeHtml(news.category)}</span>
                        </div>
                    </a>
                    <div class="news-card-content">
                        <div class="news-card-date">
                            <i data-lucide="calendar-days"></i>
                            ${escapeHtml(news.date)}
                        </div>
                        <h3>${escapeHtml(news.title)}</h3>
                        <p>${escapeHtml(getExcerpt(news))}</p>
                        <div class="news-card-bottom">
                            <a href="${getArticleUrl(news)}" class="news-card-button">
                                READ MORE <i data-lucide="arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </article>
            `;
        }
        function renderPagination() {
            newsPagination.innerHTML = "";
            const totalPages = getTotalPages();
            if (showAllNews || totalPages <= 1 || filteredNews.length === 0) return;
            const previous = document.createElement("button");
            previous.type = "button";
            previous.className = "news-page-arrow";
            previous.innerHTML = '<i data-lucide="chevron-left"></i> PREVIOUS';
            previous.disabled = currentPage === 1;
            previous.addEventListener("click", () => goToPage(currentPage - 1));
            newsPagination.appendChild(previous);
            for (let page = 1; page <= totalPages; page++) {
                const button = document.createElement("button");
                button.type = "button";
                button.className = "news-page-number" + (page === currentPage ? " active" : "");
                button.textContent = page;
                button.setAttribute("aria-label", `Go to page ${page}`);
                button.addEventListener("click", () => goToPage(page));
                newsPagination.appendChild(button);
            }
            const next = document.createElement("button");
            next.type = "button";
            next.className = "news-page-arrow";
            next.innerHTML = 'NEXT <i data-lucide="chevron-right"></i>';
            next.disabled = currentPage === totalPages;
            next.addEventListener("click", () => goToPage(currentPage + 1));
            newsPagination.appendChild(next);
        }
        function renderNews() {
            const items = getPageItems();
            const featured = items[0] || null;
            const cards = items.slice(1);
            renderFeatured(featured);
            newsGrid.innerHTML = cards.map(createNewsCard).join("");
            newsResultsCount.textContent =
                `${filteredNews.length} ${filteredNews.length === 1 ? "ARTICLE" : "ARTICLES"}`;
            newsNoPosts.hidden = filteredNews.length !== 0;
            featuredNews.hidden = !featured;
            newsSectionTitle.hidden = cards.length === 0;
            newsGrid.hidden = cards.length === 0;
            renderPagination();
            viewAllNews.hidden = filteredNews.length <= itemsPerPage;
            viewAllNews.innerHTML = showAllNews
                ? '<i data-lucide="layout-grid"></i> SHOW LESS NEWS'
                : '<i data-lucide="layout-grid"></i> VIEW ALL NEWS';
            if (window.lucide) window.lucide.createIcons();
        }
        function applyNewsFilters() {
            const keyword = newsSearchInput.value.trim().toLowerCase();
            const category = newsCategoryFilter.value.toUpperCase();
            filteredNews = newsData.filter(news => {
                const matchesCategory =
                    !category || String(news.category).toUpperCase() === category;
                const searchableText =
                    `${news.title} ${news.category} ${news.excerpt} ${news.content}`.toLowerCase();
                return matchesCategory && (!keyword || searchableText.includes(keyword));
            });
            currentPage = 1;
            showAllNews = false;
            renderNews();
        }
        function goToPage(page) {
            if (page < 1 || page > getTotalPages()) return;
            currentPage = page;
            showAllNews = false;
            renderNews();
            scrollToNewsSection();
        }
        function scrollToNewsSection() {
            const section = document.querySelector(".news-section");
            if (!section) return;
            const top = section.getBoundingClientRect().top + window.pageYOffset - 30;
            window.scrollTo({ top, behavior: "smooth" });
        }
        function resetNewsFilters() {
            newsSearchInput.value = "";
            newsCategoryFilter.value = "";
            applyNewsFilters();
        }
        newsSearchButton.addEventListener("click", applyNewsFilters);
        newsSearchInput.addEventListener("input", applyNewsFilters);
        newsSearchInput.addEventListener("keydown", event => {
            if (event.key === "Enter") applyNewsFilters();
        });
        newsCategoryFilter.addEventListener("change", applyNewsFilters);
        newsResetButton.addEventListener("click", resetNewsFilters);
        newsEmptyReset.addEventListener("click", resetNewsFilters);
        viewAllNews.addEventListener("click", () => {
            showAllNews = !showAllNews;
            currentPage = 1;
            renderNews();
            scrollToNewsSection();
        });
        renderNews();
    </script>
    <script>
        if (window.lucide) window.lucide.createIcons();
    </script>
</body>
</html>
