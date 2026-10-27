<?php
$pageTitle = "Executive Officials";
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
	<link rel="stylesheet" href="assets/css/executive.css">
</head>
<body data-page-title="<?= htmlspecialchars($pageTitle) ?>">
<div class="dashboard-layout">
	<?php include __DIR__ . '/template/sidebar.php'; ?>
	<main class="main-content">
		<?php include __DIR__ . '/template/header.php'; ?>
		
		<section class="executive-page">
			<div class="executive-intro">
				<div>
					<p>Manage executive official profiles displayed on the public website.</p>
				</div>
			</div>

			<div class="executive-toolbar">
				<div class="executive-tools-left">
					<div class="executive-search">
						<input type="search" id="officialSearch" placeholder="Search officials..." aria-label="Search officials">
						<i data-lucide="search"></i>
					</div>

					<select id="officialStatusFilter" class="executive-filter" aria-label="Filter by status">
						<option value="all">All Statuses</option>
						<option value="active">Active</option>
						<option value="inactive">Hidden</option>
					</select>

					<select id="officialOrderFilter" class="executive-filter" aria-label="Sort officials">
						<option value="ascending">Order: Low to High</option>
						<option value="descending">Order: High to Low</option>
						<option value="name">Name: A to Z</option>
					</select>
				</div>
				<button type="button" id="addOfficialBtn" class="executive-btn executive-btn-primary"><i data-lucide="plus"></i> Add Official</button>
			</div>

			<div class="executive-card">
				<div class="executive-card-head">
					<h2>Executive Officials</h2>
					<span id="tableOfficialCount" class="executive-count">0 officials</span>
				</div>
				<div class="executive-table-wrap">
					<table class="executive-table">
						<thead>
							<tr>
								<th>Photo</th>
								<th>Name</th>
								<th>Position</th>
								<th>Order</th>
								<th>Status</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody id="officialsTableBody"></tbody>
					</table>
				</div>
				<div id="emptyState" class="executive-empty" hidden>
					<i data-lucide="user-round-search"></i>
					<h3>No officials found</h3>
					<p>Try a different search term or status filter.</p>
				</div>
			</div>

			<nav class="executive-pagination" id="officialsPagination" aria-label="Executive officials pagination" hidden>
				<button class="executive-page-btn" id="officialsPrevPage" type="button" aria-label="Previous page">
					<i data-lucide="chevron-left"></i>
				</button>
				<div class="executive-page-numbers" id="officialsPageNumbers"></div>
				<button class="executive-page-btn" id="officialsNextPage" type="button" aria-label="Next page">
					<i data-lucide="chevron-right"></i>
				</button>
				<span class="executive-page-total" id="officialsPageTotal">1 page</span>
			</nav>

			<div id="officialModal" class="executive-modal" aria-hidden="true">
				<div class="executive-modal-backdrop" data-close-modal></div>
				<div class="executive-modal-panel" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
					<div class="executive-modal-head">
						<div>
							<h2 id="modalTitle">Add Official</h2>
							<p>Add or update an official’s photo, name, position, biography, display order, and publication status.</p>
						</div>
						<button type="button" class="executive-modal-close" data-close-modal aria-label="Close form"><i data-lucide="x"></i></button>
					</div>
					<form id="officialForm" class="executive-form" enctype="multipart/form-data">
						<div class="executive-upload-area">
							<div id="officialUploadInitials" class="executive-upload-placeholder"><i data-lucide="user"></i><strong>Official Photo</strong></div>
							<img id="officialUploadImage" class="executive-image-preview" alt="Official photo preview">
							<input id="officialPhoto" type="file" accept="image/*" hidden>
							<button type="button" id="chooseOfficialPhotoBtn" class="executive-btn executive-btn-ghost"><i data-lucide="upload"></i> Choose Photo</button>
							<button type="button" id="removeOfficialPhoto" class="executive-btn executive-btn-danger" hidden><i data-lucide="x"></i> Remove Photo</button>
						</div>

						<div class="executive-form-grid">
							<label class="executive-form-field">
								<span>Full Name</span>
								<input id="name" name="name" type="text" placeholder="Enter full name" required>
							</label>
							<label class="executive-form-field">
								<span>Position</span>
								<input id="position" name="position" type="text" placeholder="Enter official position" required>
							</label>
							<label class="executive-form-field full-width">
								<span>Biography / Short Description</span>
								<textarea id="bio" name="bio" rows="4" placeholder="Enter biography or additional role information"></textarea>
							</label>
							<label class="executive-form-field">
								<span>Display Order</span>
								<input id="displayOrder" name="displayOrder" type="number" min="1" value="1" required>
							</label>
							<label class="executive-form-field">
								<span>Status</span>
								<select id="status" name="status">
									<option value="Active">Active</option>
									<option value="Inactive">Hidden</option>
								</select>
							</label>
						</div>

						<div class="executive-form-actions">
							<button type="button" id="cancelBtn" class="executive-btn executive-btn-ghost">Cancel</button>
							<div class="executive-form-actions-right">
								<button type="button" id="previewOfficialFormBtn" class="executive-btn executive-btn-ghost"><i data-lucide="eye"></i> Preview</button>
								<button type="submit" id="saveOfficialBtn" class="executive-btn executive-btn-primary"><i data-lucide="save"></i> Save Official</button>
							</div>
						</div>
					</form>
				</div>
			</div>

			<div id="officialPreviewModal" class="executive-modal" aria-hidden="true">
				<div class="executive-modal-backdrop" data-close-preview></div>
				<article class="executive-modal-panel executive-preview-panel" role="dialog" aria-modal="true" aria-labelledby="officialPreviewHeading">
					<div class="executive-modal-head">
						<div><h2 id="officialPreviewHeading">Executive Official Details</h2></div>
						<button type="button" class="executive-modal-close" data-close-preview aria-label="Close preview"><i data-lucide="x"></i></button>
					</div>
					<div class="executive-preview-scroll">
						<div class="executive-preview-body">
							<div class="executive-preview-top">
								<div class="executive-preview-photo">
									<span id="officialPreviewInitials">OF</span>
									<img id="officialPreviewImage" alt="Official photo" hidden>
								</div>
								<div>
									<h3 class="executive-preview-name" id="officialPreviewName">Official Name</h3>
									<p class="executive-preview-position" id="officialPreviewPosition"></p>
									<span id="officialPreviewStatus" class="executive-status executive-status-active">Active</span>
								</div>
							</div>
							<div class="executive-preview-section">
								<p class="executive-preview-label">Biography / Short Description</p>
								<p id="officialPreviewBio" class="executive-preview-text"></p>
							</div>
							<p id="officialPreviewOrder" class="executive-preview-order"></p>
						</div>
					</div>
					<footer class="executive-preview-meta">
						<div><span>Created by</span><strong id="previewMetadataCreatedBy">—</strong></div>
						<div><span>Date created</span><strong id="previewMetadataDateCreated">—</strong></div>
						<div><span>Last updated by</span><strong id="previewMetadataLastUpdatedBy">—</strong></div>
					</footer>
				</article>
			</div>

			<div class="executive-toast" id="officialToast" role="status" aria-live="polite"></div>
		</section>
	</main>
</div>

<script src="assets/js/shared-layout.js"></script>
<script src="assets/js/executive.js"></script>

</body>
</html>