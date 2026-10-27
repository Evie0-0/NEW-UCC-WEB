<?php
$pageTitle = "E-Services";
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
    <link rel="stylesheet" href="assets/css/eservices.css">
</head>
<body data-page-title="<?= htmlspecialchars($pageTitle) ?>">
<div class="dashboard-layout">
	<?php include __DIR__ . '/template/sidebar.php'; ?>
	<main class="main-content">
		<?php include __DIR__ . '/template/header.php'; ?>
        
        </header>
            <section class="eservices-page">
                <div class="eservices-intro">
                    <div>
                        <p>Manage the online services and external resources available to the UCC community.</p>
                    </div>
                </div>
                <div class="eservices-toolbar">
                    <div class="eservices-tools-left">
                        <div class="eservices-search">
                            <input type="search" id="search" placeholder="Search e-services..." aria-label="Search e-services">
                            <i data-lucide="search"></i>
                        </div>
                        <select id="filter" class="eservices-filter" aria-label="Filter e-services">
                            <option value="all">All statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <select id="sort" class="eservices-filter" aria-label="Order e-services">
                            <option value="created-desc">Newest created</option>
                            <option value="created-asc">Oldest created</option>
                            <option value="name-desc">Name: Z–A</option>
                            <option value="name-asc">Name: A–Z</option>
                        </select>
                    </div>
                    <button type="button" id="addBtn" class="eservice-btn eservice-btn-primary"><i data-lucide="plus"></i>Add E-Service</button>
                </div>

                <div class="eservices-card">
                    <div class="eservices-card-head">
                        <h2>E-Services</h2>
                        <span id="itemCount" class="eservices-count">0 services</span>
                    </div>
                    <div class="eservices-table-wrap">
                        <table class="eservices-table">
                            <thead>
                                <tr>
                                    <th>Logo</th>
                                    <th>Service Name</th>
                                    <th>Link</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody"></tbody>
                        </table>
                    </div>
                </div>

                <nav class="eservices-pagination" id="eservicesPagination" aria-label="E-Services pagination" hidden>
                    <button class="page-btn" id="eservicesPrevPage" type="button" aria-label="Previous page">
                        <i data-lucide="chevron-left"></i>
                    </button>
                    <div class="page-numbers" id="eservicesPageNumbers"></div>
                    <button class="page-btn" id="eservicesNextPage" type="button" aria-label="Next page">
                        <i data-lucide="chevron-right"></i>
                    </button>
                    <span class="page-total" id="eservicesPageTotal">1 page</span>
                </nav>

                <div id="mainModal" class="eservice-modal" aria-hidden="true">
                    <div class="eservice-modal-backdrop" data-close-modal></div>
                    <div class="eservice-modal-panel" role="dialog" aria-modal="true" aria-labelledby="formTitle">
                        <div class="eservice-modal-head">
                            <div><h2 id="formTitle">Add E-Service</h2><p>Enter the information for this e-service.</p></div>
                            <button type="button" id="exitBtn" class="eservice-modal-close" data-close-modal aria-label="Close service form"><i data-lucide="x"></i></button>
                        </div>
                        <form id="form" class="eservice-form">
                            <div class="eservice-upload-area">
                                <div id="uploadImagePlaceholder" class="eservice-upload-placeholder"><i data-lucide="globe"></i><strong>Service Logo</strong></div>
                                <img id="imagePreview" class="eservice-image-preview" alt="Service logo preview" hidden>
                                <input type="file" id="image" accept="image/png, image/jpeg, image/webp, image/svg+xml" hidden>
                                <button type="button" id="uploadImageBtn" class="eservice-btn eservice-btn-ghost"><i data-lucide="upload"></i>Choose Photo</button>
                                <button type="button" id="removeImageBtn" class="eservice-btn eservice-btn-danger" hidden><i data-lucide="x"></i>Remove Photo</button>
                            </div>
                            <div class="eservice-form-grid">
                                <label class="eservice-form-field full-width"><span>Service Name</span><input type="text" id="name" placeholder="Enter service name" maxlength="180" required></label>
                                <label class="eservice-form-field full-width"><span>Status</span><select id="status" required>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select></label>
                                <label class="eservice-form-field full-width"><span>Description</span>
                                    <textarea id="description" rows="4" maxlength="500" placeholder="Enter service description" required></textarea>
                                </label>
                                <label class="eservice-form-field full-width"><span>Website URL</span><input type="url" id="url" placeholder="https://example.com"></label>
                            </div>
                            <div class="eservice-form-actions">
                                <button type="button" id="cancelBtn" class="eservice-btn eservice-btn-ghost" data-close-modal>Cancel</button>
                                <div class="eservice-form-actions-right">
                                    <button type="button" id="formPreviewBtn" class="eservice-btn eservice-btn-ghost"><i data-lucide="eye"></i>Preview</button>
                                    <button type="submit" id="saveBtn" class="eservice-btn eservice-btn-primary"><i data-lucide="save"></i>Save Service</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="previewModal" class="eservice-modal" aria-hidden="true">
                    <div class="eservice-modal-backdrop" data-close-preview></div>
                    <article class="eservice-modal-panel eservice-preview-panel" role="dialog" aria-modal="true" aria-labelledby="previewHeading">
                        <div class="eservice-modal-head">
                            <div><h2 id="previewHeading">E-Service Preview</h2></div>
                            <button type="button" id="exitPreviewBtn" class="eservice-modal-close" data-close-preview aria-label="Close service preview"><i data-lucide="x"></i></button>
                        </div>
                        <div class="eservice-preview">
                            <div id="previewImageWrap" class="eservice-preview-image"><div id="previewImageDefault" class="eservice-preview-default"><i data-lucide="globe"></i><span>Service Logo</span></div><img id="previewImage" alt="Service logo" hidden></div>
                            <div class="eservice-preview-body">
                                <h2 id="previewName">Service Name</h2>
                                <p id="previewDescription" class="eservice-preview-text">Service description will appear here.</p>
                                <p id="previewURL" class="eservice-preview-url"></p>
                            </div>
                        </div>
                        <footer class="eservice-preview-meta">
                            <div><span>CREATED BY</span><strong id="previewMetadataCreatedBy">—</strong></div>
                            <div><span>DATE CREATED</span><strong id="previewMetadataDateCreated">—</strong></div>
                            <div><span>LAST UPDATED BY</span><strong id="previewMetadataLastUpdatedBy">—</strong></div>
                        </footer>
                    </article>
                </div>
                <div class="eservice-toast" id="uiToast" role="status" aria-live="polite"></div>
            </section>
        </main>
    </div>

<script src="assets/js/ucc-common.js"></script>
<script src="assets/js/eservices.js"></script>

</body>
</html>