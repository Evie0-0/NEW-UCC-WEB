<?php
$pageTitle = "Units";
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
	<script src="https://unpkg.com/lucide@latest"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<link rel="stylesheet" href="assets/css/style.css">
	<link rel="stylesheet" href="assets/css/unit.css">
</head>
<body data-page-title="<?= htmlspecialchars($pageTitle) ?>">
<div class="dashboard-layout">
	<?php include __DIR__ . '/template/sidebar.php'; ?>
	<main class="main-content">
		<?php include __DIR__ . '/template/header.php'; ?>
        
            <section class="units-page">
            <div class="units-intro">
                <div>
                    <p>Manage university units, dean information, logos, and publication status on this page.</p>
                </div>
            </div>

            <div class="units-toolbar">
                <div class="units-tools-left">
                    <div class="units-search">
                        <input type="search" id="unitSearch" placeholder="Search units..." aria-label="Search units">
                        <i data-lucide="search"></i>
                    </div>

                    <select id="unitStatusFilter" class="units-filter" aria-label="Filter units by status">
                        <option value="All">All statuses</option>
                        <option value="Active">Active</option>
                        <option value="Hidden">Hidden</option>
                    </select>

                    <select id="unitSort" class="units-filter" aria-label="Sort units">
                        <option value="name-asc">Name: A to Z</option>
                        <option value="name-desc">Name: Z to A</option>
                        <option value="newest">Newest added</option>
                        <option value="oldest">Oldest added</option>
                    </select>
                </div>
                <button type="button" id="addUnitBtn" class="unit-btn unit-btn-primary"><i data-lucide="plus"></i> Add Unit</button>
            </div>

            <div class="units-card">
                <div class="units-card-head">
                    <h2>Units</h2>
                    <span id="unitCount" class="units-count">0 units</span>
                </div>
                <div class="units-table-wrap">
                    <table class="units-table">
                        <thead>
                            <tr>
                                <th>Logo</th>
                                <th>Unit Name</th>
                                <th>Dean</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="unitsTableBody"></tbody>
                    </table>
                </div>
                <div id="unitsEmpty" class="unit-empty" hidden>
                    <i data-lucide="building-2"></i>
                    <h3>No units found</h3>
                    <p>Try a different search term or status filter.</p>
                </div>
            </div>

            <nav class="units-pagination" id="unitsPagination" aria-label="Units pagination" hidden>
                <button class="unit-page-btn" id="unitsPrevPage" type="button" aria-label="Previous page">
                    <i data-lucide="chevron-left"></i>
                </button>
                <div class="unit-page-numbers" id="unitsPageNumbers"></div>
                <button class="unit-page-btn" id="unitsNextPage" type="button" aria-label="Next page">
                    <i data-lucide="chevron-right"></i>
                </button>
                <span class="unit-page-total" id="unitsPageTotal">1 page</span>
            </nav>

            <div id="unitFormModal" class="unit-modal" aria-hidden="true">
                <div class="unit-modal-backdrop" data-close-unit-form></div>
                <div class="unit-modal-panel" role="dialog" aria-modal="true" aria-labelledby="unitFormTitle">
                    <div class="unit-modal-head">
                        <div>
                            <h2 id="unitFormTitle">Add Unit</h2>
                            <p>Add or update the unit name, logo, dean information, and status.</p>
                        </div>
                        <button type="button" class="unit-modal-close" data-close-unit-form aria-label="Close unit form"><i data-lucide="x"></i></button>
                    </div>
                    <form id="unitForm" class="unit-form" enctype="multipart/form-data">
                        <div class="unit-upload-area">
                            <div id="unitLogoInitials" class="unit-upload-placeholder"><i data-lucide="image"></i><strong>Unit Logo</strong></div>
                            <img id="unitLogoPreview" class="unit-image-preview" alt="Unit logo preview">
                            <input id="unitLogoFile" type="file" accept="image/*" hidden>
                            <button type="button" id="chooseUnitLogoBtn" class="unit-btn unit-btn-ghost"><i data-lucide="upload"></i> Choose Photo</button>
                            <button type="button" id="removeUnitLogo" class="unit-btn unit-btn-danger" hidden><i data-lucide="x"></i> Remove Photo</button>
                        </div>

                        <div class="unit-form-grid">
                            <label class="unit-form-field full-width"><span>Unit Name</span><input id="unitName" name="unitName" type="text" maxlength="150" placeholder="Enter unit name" required></label>
                        </div>

                        <div class="unit-upload-area">
                            <div id="deanImageInitials" class="unit-upload-placeholder"><i data-lucide="user"></i><strong>Dean's Photo</strong></div>
                            <img id="deanImagePreview" class="unit-image-preview" alt="Dean image preview">
                            <input id="deanImageFile" type="file" accept="image/*" hidden>
                            <button type="button" id="chooseDeanImageBtn" class="unit-btn unit-btn-ghost"><i data-lucide="upload"></i> Choose Photo</button>
                            <button type="button" id="removeDeanImage" class="unit-btn unit-btn-danger" hidden><i data-lucide="x"></i> Remove Photo</button>
                        </div>

                        <div class="unit-form-grid">
                            <label class="unit-form-field full-width"><span>Dean's Name</span><input id="unitDeanName" name="deanName" type="text" maxlength="150" placeholder="Enter dean's full name" required></label>
                            <label class="unit-form-field full-width"><span>Status</span><select id="unitStatus" name="status"><option value="Active">Active</option><option value="Hidden">Hidden</option></select></label>
                        </div>

                        <div class="unit-form-actions">
                            <button type="button" id="cancelUnitBtn" class="unit-btn unit-btn-ghost">Cancel</button>
                            <div class="unit-form-actions-right">
                                <button type="button" id="previewUnitBtn" class="unit-btn unit-btn-ghost"><i data-lucide="eye"></i> Preview</button>
                                <button type="submit" class="unit-btn unit-btn-primary"><i data-lucide="save"></i> Save Unit</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div id="unitPreviewModal" class="unit-modal" aria-hidden="true">
                <div class="unit-modal-backdrop" data-close-unit-preview></div>
                <article class="unit-modal-panel unit-preview-panel" role="dialog" aria-modal="true" aria-labelledby="unitPreviewTitle">
                    <div class="unit-modal-head">
                        <div><h2 id="unitPreviewTitle">Unit Preview</h2></div>
                        <button type="button" class="unit-modal-close" data-close-unit-preview aria-label="Close preview"><i data-lucide="x"></i></button>
                    </div>
                    <div class="unit-preview-body">
                        <div class="unit-preview-top">
                            <div class="unit-preview-logo">
                                <span id="previewUnitLogoInitials">UN</span>
                                <img id="previewUnitLogo" alt="Unit logo" hidden>
                            </div>
                            <div>
                                <h3 id="previewUnitName" class="unit-preview-name">Unit Name</h3>
                                <span id="previewUnitStatus" class="unit-status unit-status-active">Active</span>
                            </div>
                        </div>
                        <div class="unit-preview-dean">
                            <div class="unit-preview-avatar">
                                <span id="previewDeanInitials">DN</span>
                                <img id="previewDeanImage" alt="Dean" style="display:none;">
                            </div>
                            <div>
                                <p class="unit-preview-role">Dean</p>
                                <h4 id="previewDeanName" class="unit-preview-dean-name">Dean's Name</h4>
                            </div>
                        </div>
                    </div>
                    <footer class="unit-preview-meta">
                        <div><span>Created by</span><strong id="unitCreatedBy">Admin</strong></div>
                        <div><span>Date created</span><strong id="unitDateCreated">—</strong></div>
                        <div><span>Last updated by</span><strong id="unitLastUpdatedBy">—</strong></div>
                    </footer>
                </article>
            </div>
            <div class="unit-toast" id="unitsToast" role="status" aria-live="polite"></div>
        </section>
    </main>
</div>

<script src="assets/js/shared-layout.js"></script>
<script src="assets/js/units.js"></script>

</body>
</html>