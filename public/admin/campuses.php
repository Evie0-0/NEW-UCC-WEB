<?php
$pageTitle = "Campuses";
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1.0">
	<title>UCC Admin | <?= htmlspecialchars($pageTitle) ?> </title>
	<link rel="icon" type="image/png" href="assets/images/ucc-LOGO.png">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Libre+Baskerville:wght@700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="assets/css/style.css">
	<link rel="stylesheet" href="assets/css/campuses.css">
	<script src="https://unpkg.com/lucide@latest"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body data-page-title="<?= htmlspecialchars($pageTitle) ?>">
<div class="dashboard-layout">
	<?php include __DIR__ . '/template/sidebar.php'; ?>
	<main class="main-content">
		<?php include __DIR__ . '/template/header.php'; ?>

        </header>
            <section class="campuses-page">
                <div class="campuses-intro">
                    <div><p>Manage campus details, contact information, and locations.</p></div>
                </div>
                <div class="campuses-toolbar">
                    <div class="campuses-tools-left">
                        <div class="campuses-search">
                            <input type="search" id="search" placeholder="Search campuses..." aria-label="Search campuses">
                            <i data-lucide="search"></i>
                        </div>
                        <select id="filter" class="campuses-filter" aria-label="Filter campuses">
                            <option value="all">All statuses</option>
                            <option value="active">Active</option>
                            <option value="hidden">Hidden</option>
                        </select>
                        <select id="sort" class="campuses-filter" aria-label="Sort campuses">
                            <option value="created-desc">Newest created</option>
                            <option value="created-asc">Oldest created</option>
                            <option value="name-asc">Name: A–Z</option>
                            <option value="name-desc">Name: Z–A</option>
                        </select>
                    </div>
                    <button type="button" id="addBtn" class="campus-btn campus-btn-primary"><i data-lucide="plus"></i> Add Campus</button>
                </div>

                <div class="campuses-card">
                    <div class="campuses-card-head">
                        <h2>Campuses</h2>
                        <span id="itemCount" class="campuses-count">0 campuses</span>
                    </div>
                    <div class="campuses-table-wrap">
                        <table class="campuses-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Campus Name</th>
                                    <th>Address</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody"></tbody>
                        </table>
                    </div>
                </div>

                <nav class="campuses-pagination" id="campusesPagination" aria-label="Campuses pagination" hidden>
                    <button class="page-btn" id="campusesPrevPage" type="button" aria-label="Previous page">
                        <i data-lucide="chevron-left"></i>
                    </button>
                    <div class="page-numbers" id="campusesPageNumbers"></div>
                    <button class="page-btn" id="campusesNextPage" type="button" aria-label="Next page">
                        <i data-lucide="chevron-right"></i>
                    </button>
                    <span class="page-total" id="campusesPageTotal">1 page</span>
                </nav>

                <div id="mainModal" class="campus-modal" aria-hidden="true">
                    <div class="campus-modal-backdrop" data-close-modal></div>
                    <div class="campus-modal-panel" role="dialog" aria-modal="true" aria-labelledby="formTitle">
                        <div class="campus-modal-head">
                            <div><h2 id="formTitle">Add Campus</h2><p>Enter the information for this campus.</p></div>
                            <button type="button" id="exitBtn" class="campus-modal-close" data-close-modal aria-label="Close campus form"><i data-lucide="x"></i></button>
                        </div>
                        <form id="form" class="campus-form">
                            <div class="campus-upload-area">
                                <div id="uploadImagePlaceholder" class="campus-upload-placeholder"><i data-lucide="image-plus"></i><strong>Campus Image</strong></div>
                                <img id="imagePreview" class="campus-image-preview" alt="Campus image preview" hidden>
                                <input type="file" id="image" accept="image/png, image/jpeg, image/webp" hidden>
                                <button type="button" id="uploadImageBtn" class="campus-btn campus-btn-ghost"><i data-lucide="upload"></i> Choose Photo</button>
                                <button type="button" id="removeImageBtn" class="campus-btn campus-btn-danger" hidden><i data-lucide="x"></i> Remove Photo</button>
                            </div>
                            <div class="campus-form-grid">
                                <label class="campus-form-field full-width"><span>Campus Name</span><input type="text" id="name" placeholder="Enter campus name" maxlength="180" required></label>
                                <label class="campus-form-field"><span>Email</span><input type="email" id="email" placeholder="Enter campus email" maxlength="255"></label>
                                <label class="campus-form-field"><span>Address</span><input type="text" id="address" placeholder="Enter campus address" maxlength="500" required></label>
                                <label class="campus-form-field"><span>Status</span><select id="status" required>
                                    <option value="Active">Active</option>
                                    <option value="Hidden">Hidden</option>
                                </select></label>
                                <label class="campus-form-field full-width"><span>Location / Google Maps URL</span><input type="url" id="url" placeholder="Paste Google Maps URL"></label>
                            </div>
                            <label><span>Contact Information</span>
                                <textarea id="contact" rows="4" maxlength="1000" placeholder="Enter phone number or other contact details"></textarea>
                            </label>

                            <div class="campus-form-actions">
                                <button type="button" id="cancelBtn" class="campus-btn campus-btn-ghost" data-close-modal>Cancel</button>
                                <div class="campus-form-actions-right">
                                    <button type="button" id="formPreviewBtn" class="campus-btn campus-btn-ghost"><i data-lucide="eye"></i> Preview</button>
                                    <button type="submit" id="saveBtn" class="campus-btn campus-btn-primary"><i data-lucide="save"></i> Save Campus</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="previewModal" class="campus-modal" aria-hidden="true">
                    <div class="campus-modal-backdrop" data-close-preview></div>
                    <article class="campus-modal-panel campus-preview-panel" role="dialog" aria-modal="true" aria-labelledby="previewHeading">
                        <div class="campus-modal-head"><div><h2 id="previewHeading">Campus Preview</h2></div><button type="button" id="exitPreviewBtn" class="campus-modal-close" data-close-preview aria-label="Close preview"><i data-lucide="x"></i></button></div>
                        <div class="campus-preview">
                            <div id="previewImageWrap" class="campus-preview-image"><div id="previewImageDefault" class="campus-preview-default"><i data-lucide="image"></i><span>Campus Image</span></div><img id="previewImage" alt="Campus image" hidden></div>
                            <div class="campus-preview-body">
                                <h2 id="previewCampusName">Campus Name</h2>
                                <p id="previewCampusEmail" class="campus-preview-email"></p>
                                <p id="previewCampusAddress" class="campus-preview-text"></p>
                                <p id="previewCampusContact" class="campus-preview-text"></p>
                                <a id="previewCampusMap" class="campus-preview-map" href="#" target="_blank" rel="noopener noreferrer" hidden><i data-lucide="map-pin"></i> View on Google Maps</a>
                            </div>
                        </div>
                        <footer class="campus-preview-meta">
                            <div><span>CREATED BY</span><strong id="previewMetadataCreatedBy">—</strong></div>
                            <div><span>DATE CREATED</span><strong id="previewMetadataDateCreated">—</strong></div>
                            <div><span>LAST UPDATED BY</span><strong id="previewMetadataLastUpdatedBy">—</strong></div>
                        </footer>
                    </article>
                </div>
                <div class="campus-toast" id="uiToast" role="status" aria-live="polite"></div>
            </section>
        </main>
    </div>

<script src="assets/js/ucc-common.js"></script>
<script src="assets/js/campuses.js"></script>

</body>
</html>