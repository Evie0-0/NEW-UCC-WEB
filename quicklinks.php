<?php
$pageTitle = "Quick Links";
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
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Libre+Baskerville:wght@700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/quicklinks.css">
</head>

<body data-page-title="<?= htmlspecialchars($pageTitle) ?>">
<div class="dashboard-layout">
	<?php include __DIR__ . '/template/sidebar.php'; ?>
	<main class="main-content">
		<?php include __DIR__ . '/template/header.php'; ?>

        </header>
        <section class="quicklinks-page">
            <div class="quicklinks-intro">
                <div>
                    <p>Manage the quick links shown to users.</p>
                </div>
            </div>

            <div class="quicklinks-toolbar">
                <div class="quicklinks-tools-left">
                    <div class="quicklinks-search">
                        <input type="search" id="quickSearch" placeholder="Search quick links..." aria-label="Search quick links">
                        <i data-lucide="search"></i>
                    </div>
                </div>
                <button class="quicklink-btn quicklink-btn-primary" type="button" id="addLinkBtn">
                    <i data-lucide="plus"></i>
                    Add Link
                </button>
            </div>

            <div class="quicklinks-card">
                <div class="quicklinks-card-head">
                    <h2>Quick Links</h2>
                    <span id="totalLinks" class="quicklinks-count">0 links</span>
                </div>

                <div class="quicklinks-table-wrap">
                    <table class="quicklinks-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th class="col-title">Link Name</th>
                                <th>URL</th>
                                <th class="col-status">Status</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="quickLinksBody"></tbody>
                    </table>
                </div>
            </div>

            <nav class="quicklinks-pagination" id="quicklinksPagination" aria-label="Quick links pagination" hidden>
                <button class="page-btn" id="quicklinksPrevPage" type="button" aria-label="Previous page">
                    <i data-lucide="chevron-left"></i>
                </button>
                <div class="page-numbers" id="quicklinksPageNumbers"></div>
                <button class="page-btn" id="quicklinksNextPage" type="button" aria-label="Next page">
                    <i data-lucide="chevron-right"></i>
                </button>
                <span class="page-total" id="quicklinksPageTotal">1 page</span>
            </nav>

            <div id="quickModal" class="quicklink-modal" aria-hidden="true">
                <div class="quicklink-modal-backdrop" data-close-modal></div>
                <div class="quicklink-modal-panel" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
                    <div class="quicklink-modal-head">
                        <div>
                            <h2 id="modalTitle">Add Quick Link</h2>
                            <p id="postFormDescription">Enter the details for this quick link.</p>
                        </div>
                        <button class="quicklink-modal-close" type="button" id="closeModalBtn" aria-label="Close modal"><i data-lucide="x"></i></button>
                    </div>
                    <form id="quickLinkForm" class="quicklink-form" novalidate>
                        <div class="quicklink-upload-area">
                            <div id="uploadPlaceholder" class="quicklink-upload-placeholder"><i data-lucide="image"></i><strong>Featured Image</strong></div>
                            <img id="selectedImagePreview" class="quicklink-image-preview" alt="Selected featured image" hidden>
                            <input type="file" id="featuredImage" accept="image/*" hidden>
                            <button type="button" id="chooseImageBtn" class="quicklink-btn quicklink-btn-ghost"><i data-lucide="upload"></i> Choose Photo</button>
                            <button type="button" id="removeImageBtn" class="quicklink-btn quicklink-btn-danger" hidden><i data-lucide="x"></i> Remove Photo</button>
                        </div>

                        <div class="quicklink-form-grid">
                            <label class="quicklink-form-field full-width">
                                <span>Link Name</span>
                                <input type="text" id="linkName" maxlength="100" placeholder="Enter link name" required>
                                <div class="quicklink-field-error" id="linkNameError"></div>
                            </label>
                            <label class="quicklink-form-field full-width">
                                <span>URL</span>
                                <input type="url" id="linkUrl" placeholder="https://example.com" required>
                                <div class="quicklink-field-error" id="linkUrlError"></div>
                            </label>
                            <label class="quicklink-form-field">
                                <span>Display Order</span>
                                <input type="number" id="displayOrder" min="1" step="1" placeholder="1" required>
                                <div class="quicklink-field-error" id="displayOrderError"></div>
                            </label>
                            <label class="quicklink-form-field">
                                <span>Status</span>
                                <select id="linkStatus" name="status">
                                    <option value="Active">Active</option>
                                    <option value="Hidden">Hidden</option>
                                </select>
                            </label>
                        </div>

                        <div class="quicklink-form-actions">
                            <button class="quicklink-btn quicklink-btn-ghost" type="button" id="cancelModalBtn">Cancel</button>
                            <div class="quicklink-form-actions-right">
                                <button class="quicklink-btn quicklink-btn-ghost" type="button" id="previewLinkBtn"><i data-lucide="eye"></i> Preview</button>
                                <button class="quicklink-btn quicklink-btn-primary" type="submit"><i data-lucide="save"></i> Save Link</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div id="quickPreviewModal" class="quicklink-modal" aria-hidden="true">
                <div class="quicklink-modal-backdrop" data-close-preview></div>
                <article class="quicklink-modal-panel quicklink-preview-panel" role="dialog" aria-modal="true" aria-labelledby="quickPreviewTitle">
                    <div class="quicklink-modal-head">
                        <div><h2 id="quickPreviewTitle">Quick Link Preview</h2></div>
                        <button class="quicklink-modal-close" type="button" id="closePreviewBtn" aria-label="Close preview"><i data-lucide="x"></i></button>
                    </div>
                    <div class="quicklink-preview">
                        <div class="quicklink-preview-image">
                            <img id="previewImage" alt="Featured image" hidden>
                        </div>
                        <div class="quicklink-preview-body">
                            <h3 id="previewName">Link name</h3>
                            <span class="quicklink-status quicklink-status-active" id="previewStatus">Active</span>
                            <p id="previewUrl" class="quicklink-preview-text"></p>
                            <a id="previewVisitLink" href="#" target="_blank" rel="noopener noreferrer" class="quicklink-btn quicklink-btn-primary">
                                <i data-lucide="external-link"></i> View Link
                            </a>
                        </div>
                    </div>
                                    <footer class="quicklink-preview-meta">
                        <div class="quicklink-preview-meta-grid">
                            <div><span>CREATED BY</span><strong id="previewMetadataCreatedBy">—</strong></div>
                            <div><span>DATE CREATED</span><strong id="previewMetadataDateCreated">—</strong></div>
                            <div><span>LAST UPDATED BY</span><strong id="previewMetadataLastUpdatedBy">—</strong></div>
                        </div>
                    </footer>
                </article>
            </div>
            <div class="quicklink-toast" id="uiToast" role="status" aria-live="polite"></div>
        </section>
    </main>
</div>

<script src="assets/js/ucc-common.js"></script>
<script src="assets/js/quicklinks.js"></script>

</body>
</html>