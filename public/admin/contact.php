<?php
$pageTitle = "Contact Information";
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
	<link rel="stylesheet" href="assets/css/contact.css">
</head>
<body data-page-title="<?= htmlspecialchars($pageTitle) ?>">
<div class="dashboard-layout">
	<?php include __DIR__ . '/template/sidebar.php'; ?>
	<main class="main-content">
		<?php include __DIR__ . '/template/header.php'; ?>

        </header>
            <section class="contacts-page">
                <div class="contacts-intro">
                    <div><p>Manage the contact details displayed across the university website.</p></div>
                </div>

                <div class="contacts-toolbar">
                    <div class="contacts-tools-left">
                        <div class="contacts-search">
                            <input type="search" id="contactSearch" placeholder="Search contacts..." aria-label="Search contacts">
                            <i data-lucide="search"></i>
                        </div>
                        <select id="categoryFilter" class="contacts-filter" aria-label="Filter by category">
                            <option value="">All categories</option>
                            <option value="Campus">Campus</option>
                            <option value="Colleges">Colleges</option>
                            <option value="Offices">Offices</option>
                        </select>
                        <select id="statusFilter" class="contacts-filter" aria-label="Filter by status">
                            <option value="">All statuses</option>
                            <option value="Active">Active</option>
                            <option value="Hidden">Hidden</option>
                        </select>
                    </div>
                    <button type="button" id="openContactForm" class="contact-btn contact-btn-primary"><i data-lucide="plus"></i> Add Contact</button>
                </div>

                <div class="contacts-card">
                    <div class="contacts-card-head">
                        <h2 id="contactsHeading">Contacts</h2>
                        <span id="contactCountLabel" class="contacts-count">0 Contacts</span>
                    </div>
                    <div class="contacts-table-wrap">
                        <table class="contacts-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Contact Number</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="contactTableBody"></tbody>
                        </table>
                    </div>
                </div>

                <nav class="contacts-pagination" id="contactsPagination" aria-label="Contacts pagination" hidden>
                    <button class="page-btn" id="contactsPrevPage" type="button" aria-label="Previous page">
                        <i data-lucide="chevron-left"></i>
                    </button>
                    <div class="page-numbers" id="contactsPageNumbers"></div>
                    <button class="page-btn" id="contactsNextPage" type="button" aria-label="Next page">
                        <i data-lucide="chevron-right"></i>
                    </button>
                    <span class="page-total" id="contactsPageTotal">1 page</span>
                </nav>

                <div id="contactFormModal" class="contact-modal" aria-hidden="true">
                    <div class="contact-modal-backdrop" data-close-form></div>
                    <div class="contact-modal-panel" role="dialog" aria-modal="true" aria-labelledby="contactFormTitle">
                        <div class="contact-modal-head">
                            <div><h2 id="contactFormTitle">Add Contact</h2><p>Enter the details for the contact information.</p></div>
                            <button type="button" class="contact-modal-close" data-close-form aria-label="Close form"><i data-lucide="x"></i></button>
                        </div>
                        <form id="contactForm" class="contact-form">
                            <div class="contact-form-grid">
                                <label><span>Category</span><select id="contactCategory" name="contactCategory" required>
                                    <option value="Campus">Campus</option>
                                    <option value="Colleges">Colleges</option>
                                    <option value="Offices">Offices</option>
                                </select></label>
                                <label><span>Status</span><select id="contactStatus" name="contactStatus">
                                    <option value="Active">Active</option>
                                    <option value="Hidden">Hidden</option>
                                </select></label>
                                <label class="contact-form-field full-width"><span>Name</span><input id="contactName" name="contactName" type="text" placeholder="Enter name" required></label>
                                <label class="contact-form-field"><span>Email</span><input id="contactEmail" name="contactEmail" type="email" placeholder="Enter email address" required></label>
                                <label class="contact-form-field"><span>Contact Number</span><input id="contactNumber" name="contactNumber" type="tel" placeholder="Enter contact number" required></label>
                                <label class="contact-form-field full-width" id="campusLocationField"><span>Integrated Map / Campus Location</span>
                                    <input id="contactLocation" name="contactLocation" type="text" placeholder="Enter campus address or paste a Google Maps URL">
                                    <p class="contact-form-caption" id="contactLocationCaption" hidden></p>
                                </label>
                            </div>
                            <div class="contact-form-actions">
                                <button type="button" class="contact-btn contact-btn-ghost" data-close-form>Cancel</button>
                                <div class="contact-form-actions-right">
                                    <button type="button" id="previewContactBtn" class="contact-btn contact-btn-ghost"><i data-lucide="eye"></i> Preview</button>
                                    <button type="submit" class="contact-btn contact-btn-primary"><i data-lucide="save"></i> <span id="saveContactLabel">Save Contact</span></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="contactPreviewModal" class="contact-modal" aria-hidden="true">
                    <div class="contact-modal-backdrop" data-close-preview></div>
                    <article class="contact-modal-panel contact-preview-panel" role="dialog" aria-modal="true" aria-labelledby="contactPreviewTitle">
                        <div class="contact-modal-head">
                            <div><p class="contact-preview-eyebrow">CONTACT DETAILS</p><h2 id="contactPreviewTitle">Contact Preview</h2></div>
                            <button type="button" class="contact-modal-close" data-close-preview aria-label="Close preview"><i data-lucide="x"></i></button>
                        </div>
                        <div class="contact-preview">
                            <div class="contact-preview-body">
                                <div class="contact-preview-top">
                                    <h3 id="previewContactName">Contact name</h3>
                                    <span class="contact-status contact-status-active" id="previewContactStatus">Active</span>
                                </div>
                                <p class="contact-preview-category" id="previewContactCategory">Campus</p>
                                <div class="contact-preview-info">
                                    <p><i data-lucide="mail"></i> <span id="previewContactEmail">Email</span></p>
                                    <p><i data-lucide="phone"></i> <span id="previewContactNumber">Number</span></p>
                                </div>

                                <section class="contact-preview-location" id="previewLocationSection" hidden>
                                    <h3>Campus Location</h3>
                                    <p id="previewContactLocation"></p>
                                    <div class="contact-preview-map" id="previewMapFrame"></div>
                                    <a id="previewContactMapLink" href="#" target="_blank" rel="noopener noreferrer" class="contact-btn contact-btn-ghost">
                                        <i data-lucide="map-pin"></i> Open in Google Maps
                                    </a>
                                </section>
                            </div>
                        </div>
                        <footer class="contact-preview-meta">
                            <div><span>CREATED BY</span><strong id="previewMetadataCreatedBy">—</strong></div>
                            <div><span>DATE CREATED</span><strong id="previewMetadataDateCreated">—</strong></div>
                            <div><span>LAST UPDATED</span><strong id="previewMetadataLastUpdatedBy">—</strong></div>
                        </footer>
                    </article>
                </div>
                <div class="contact-toast" id="uiToast" role="status" aria-live="polite"></div>
            </section>
        </main>
    </div>

<script src="assets/js/ucc-common.js"></script>
<script src="assets/js/contact.js"></script>

</body>
</html>