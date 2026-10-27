<?php
$pageTitle = "About UCC";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>UCC Admin | <?= htmlspecialchars($pageTitle) ?></title>
  <link rel="icon" type="image/png" href="assets/images/ucc-LOGO.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Libre+Baskerville:wght@700&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/about.css">
</head>

<body data-page-title="<?= htmlspecialchars($pageTitle) ?>">
<div class="dashboard-layout">
	<?php include __DIR__ . '/template/sidebar.php'; ?>
	<main class="main-content">
		<?php include __DIR__ . '/template/header.php'; ?>

        </header>
            <section class="about-page">
            <div class="about-intro">
                <div><p>Manage the About UCC content displayed on the website.</p></div>
            </div>

            <div class="about-toolbar">
                <div class="about-tools-left">
                    <div class="about-search">
                        <input type="search" id="aboutSearch" placeholder="Search posts..." aria-label="Search posts" autocomplete="off">
                        <i data-lucide="search"></i>
                    </div>
                    <select id="statusFilter" class="about-filter" aria-label="Filter by status">
                        <option value="all">All statuses</option>
                        <option value="Active">Active</option>
                        <option value="Hidden">Hidden</option>
                    </select>
                </div>
                <button type="button" id="addPostBtn" class="about-btn about-btn-primary"><i data-lucide="plus"></i> Add Post</button>
            </div>

            <div class="about-card">
                <div class="about-card-head">
                    <h2 id="aboutUccHeading">About UCC Posts</h2>
                    <span id="postCount" class="about-count">0 posts</span>
                </div>
                <div class="about-table-wrap">
                    <table class="about-table">
                        <thead>
                            <tr>
                                <th>Featured Image</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="postsTableBody"></tbody>
                    </table>
                </div>
            </div>

            <nav class="about-pagination" id="aboutPagination" aria-label="About UCC pagination" hidden>
                <button class="page-btn" id="aboutPrevPage" type="button" aria-label="Previous page">
                    <i data-lucide="chevron-left"></i>
                </button>
                <div class="page-numbers" id="aboutPageNumbers"></div>
                <button class="page-btn" id="aboutNextPage" type="button" aria-label="Next page">
                    <i data-lucide="chevron-right"></i>
                </button>
                <span class="page-total" id="aboutPageTotal">1 page</span>
            </nav>

            <div id="postFormOverlay" class="about-modal" aria-hidden="true">
                <div class="about-modal-backdrop" data-close-form></div>
                <div class="about-modal-panel" role="dialog" aria-modal="true" aria-labelledby="formModalTitle">
                    <div class="about-modal-head">
                        <div>
                            <h2 id="formModalTitle">Add Post</h2>
                            <p>Add a new About UCC post and manage how it appears on the website.</p>
                        </div>
                        <button type="button" class="about-modal-close" data-close-form aria-label="Close form"><i data-lucide="x"></i></button>
                    </div>
                    <form id="postForm" class="about-form">
                        <div class="about-upload-area">
                            <div id="uploadPlaceholder" class="about-upload-placeholder"><i data-lucide="image"></i><strong>Featured Image</strong></div>
                            <img id="selectedImagePreview" class="about-image-preview" alt="Selected featured image" hidden>
                            <input id="featuredImage" type="file" accept="image/*" hidden>
                            <button type="button" id="chooseImageBtn" class="about-btn about-btn-ghost"><i data-lucide="upload"></i> Choose Photo</button>
                            <button type="button" id="removeImageBtn" class="about-btn about-btn-danger" hidden><i data-lucide="x"></i> Remove Photo</button>
                        </div>

                        <div class="about-form-grid">
                            <label class="about-form-field full-width">
                                <span>Name</span>
                                <input id="postName" type="text" required maxlength="160" placeholder="Enter post name">
                            </label>
                            <label class="about-form-field full-width">
                                <span>Description</span>
                                <textarea id="postDescription" required rows="5" placeholder="Write the About UCC description..."></textarea>
                            </label>
                            <label class="about-form-field">
                                <span>Status</span>
                                <select id="postStatus">
                                    <option value="Active">Active</option>
                                    <option value="Hidden">Hidden</option>
                                </select>
                            </label>
                        </div>

                        <div class="about-form-actions">
                            <button type="button" class="about-btn about-btn-ghost" data-close-form>Cancel</button>
                            <div class="about-form-actions-right">
                                <button type="button" id="formPreviewBtn" class="about-btn about-btn-ghost"><i data-lucide="eye"></i> Preview</button>
                                <button type="submit" class="about-btn about-btn-primary"><i data-lucide="save"></i> <span id="savePostLabel">Save Post</span></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div id="postPreviewOverlay" class="about-modal" aria-hidden="true">
                <div class="about-modal-backdrop" data-close-preview></div>
                <article class="about-modal-panel about-preview-panel" role="dialog" aria-modal="true" aria-labelledby="previewName">
                    <div class="about-modal-head">
                        <div><h2>About UCC Post Preview</h2></div>
                        <button type="button" class="about-modal-close" data-close-preview aria-label="Close preview"><i data-lucide="x"></i></button>
                    </div>
                    <div class="about-preview">
                        <div id="previewImageWrap" class="about-preview-image" hidden>
                            <img id="previewImage" alt="Featured image preview" hidden>
                        </div>
                        <div class="about-preview-body">
                            <h3 id="previewName">Post name</h3>
                            <span id="previewStatus" class="about-status about-status-active">Active</span>
                            <p id="previewDescription" class="about-preview-text"></p>
                        </div>
                    </div>
                    <footer class="about-preview-meta">
                        <div><span>CREATED BY</span><strong id="previewMetadataCreatedBy">—</strong></div>
                        <div><span>DATE CREATED</span><strong id="previewMetadataDateCreated">—</strong></div>
                        <div><span>LAST UPDATED BY</span><strong id="previewMetadataLastUpdatedBy">—</strong></div>
                    </footer>
                </article>
            </div>
            <div class="about-toast" id="uiToast" role="status" aria-live="polite"></div>
        </section>
    </main>
</div>

<script src="assets/js/ucc-common.js"></script>
<script src="assets/js/about.js"></script>

</body>
</html>