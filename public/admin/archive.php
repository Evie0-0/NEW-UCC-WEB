<?php
$pageTitle = "Archive";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UCC Admin | <?= htmlspecialchars($pageTitle) ?></title>
  <link rel="icon" type="image/png" href="assets/images/ucc-LOGO.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Libre+Baskerville:wght@700&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/archive.css">
</head>
<body data-page-title="<?= htmlspecialchars($pageTitle) ?>">
<div class="dashboard-layout">
	<?php include __DIR__ . '/template/sidebar.php'; ?>
	<main class="main-content">
		<?php include __DIR__ . '/template/header.php'; ?>

        </header>
            <section class="archive-page">
                <div class="archive-intro">
                    <div><p>Search archived content, preview its details, restore posts to the active list, or permanently delete items.</p></div>
                </div>
                <div class="archive-toolbar">
                    <div class="archive-tools-left">
                        <div class="archive-search">
                            <input type="search" id="archiveSearch" placeholder="Search archived content..." aria-label="Search archived content">
                            <i data-lucide="search"></i>
                        </div>
                        <select id="categoryFilter" class="archive-filter" aria-label="Filter by category">
                            <option value="all">All categories</option>
                            <option value="News">News</option>
                            <option value="Announcement">Announcement</option>
                            <option value="Event">Event</option>
                            <option value="Student Life">Student Life</option>
                            <option value="Community Extension Services">Community Extension Services</option>
                            <option value="Admission">Admission</option>
                            <option value="Alumni">Alumni</option>
                            <option value="Careers">Careers</option>
                            <option value="College News">College News</option>
                            <option value="Program News">Program News</option>
                        </select>
                        <select id="orderFilter" class="archive-filter" aria-label="Sort order">
                            <option value="newest">Newest publish date</option>
                            <option value="oldest">Oldest publish date</option>
                            <option value="az">Title A–Z</option>
                            <option value="za">Title Z–A</option>
                        </select>
                    </div>
                </div>

                <div class="archive-card">
                    <div class="archive-card-head">
                        <h2>Archive</h2>
                        <span id="archiveCount" class="archive-count">0 archived items</span>
                    </div>
                    <div class="archive-table-wrap">
                        <table class="archive-table">
                            <thead>
                                <tr>
                                    <th class="col-image">Featured image</th>
                                    <th class="col-title">Title</th>
                                    <th class="col-category">Category</th>
                                    <th class="col-date">Publish date</th>
                                    <th class="col-date">Archived date</th>
                                    <th class="col-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="archiveTableBody"></tbody>
                        </table>
                    </div>
                </div>

                <nav class="archive-pagination" id="archivePagination" aria-label="Archive pagination" hidden>
                    <button class="page-btn" id="archivePrevPage" type="button" aria-label="Previous page">
                        <i data-lucide="chevron-left"></i>
                    </button>
                    <div class="page-numbers" id="archivePageNumbers"></div>
                    <button class="page-btn" id="archiveNextPage" type="button" aria-label="Next page">
                        <i data-lucide="chevron-right"></i>
                    </button>
                    <span class="page-total" id="archivePageTotal">1 page</span>
                </nav>

                <div id="archivePreviewModal" class="archive-modal" aria-hidden="true">
                    <div class="archive-modal-backdrop" data-close-preview></div>
                    <article class="archive-modal-panel archive-preview-panel" role="dialog" aria-modal="true" aria-labelledby="previewTitle">
                        <div class="archive-modal-head">
                            <div><h2>Archive Preview</h2></div>
                            <button type="button" class="archive-modal-close" data-close-preview aria-label="Close preview"><i data-lucide="x"></i></button>
                        </div>
                        <div class="archive-preview">
                            <div id="previewImageWrap" class="archive-preview-image">
                                <div id="previewImagePlaceholder" class="archive-preview-default"><i data-lucide="image"></i><span>Featured image</span></div>
                                <img id="previewImage" alt="Post featured image" hidden>
                            </div>
                            <div class="archive-preview-body">
                                <h2 id="previewTitle">Post title</h2>
                                <p id="previewExcerpt" class="archive-preview-excerpt"></p>
                                <div id="previewBody" class="archive-preview-text"></div>
                            </div>
                        </div>
                                        <footer class="archive-preview-meta">
                            <div class="archive-preview-meta-grid">
                            <div><span>CREATED BY</span><strong id="previewMetadataCreatedBy">—</strong></div>
                            <div><span>DATE CREATED</span><strong id="previewMetadataDateCreated">—</strong></div>
                            <div><span>LAST UPDATED BY</span><strong id="previewMetadataLastUpdatedBy">—</strong></div>
                        </div>
                    </footer>
                </article>
                </div>
                <div class="archive-toast" id="uiToast" role="status" aria-live="polite"></div>
            </section>
        </main>
    </div>

<script src="assets/js/ucc-common.js"></script>
<script src="assets/js/archive.js"></script>

</body>
</html>