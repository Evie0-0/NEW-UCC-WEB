<?php
$pageTitle = "Board of Regents";
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
	<link rel="stylesheet" href="assets/css/regents.css">
</head>
<body data-page-title="<?= htmlspecialchars($pageTitle) ?>">
<div class="dashboard-layout">
	<?php include __DIR__ . '/template/sidebar.php'; ?>
	<main class="main-content">
		<?php include __DIR__ . '/template/header.php'; ?>
		
		<section class="regents-page">
			<div class="regents-intro">
				<div>
					<p>Manage the members and official information of the University of Caloocan City Board of Regents.</p>
				</div>
			</div>

			<div class="regents-toolbar">
				<div class="regents-tools-left">
					<div class="regents-search">
						<input type="search" id="borSearch" placeholder="Search regents..." aria-label="Search regents">
						<i data-lucide="search"></i>
					</div>

					<select id="borStatusFilter" class="regents-filter" aria-label="Filter by status">
						<option value="all">All Statuses</option>
						<option value="active">Active</option>
						<option value="hidden">Hidden</option>
						<option value="expired">Expired</option>
					</select>

					<select id="borOrderFilter" class="regents-filter" aria-label="Sort regents">
						<option value="ascending">Order: Low to High</option>
						<option value="descending">Order: High to Low</option>
						<option value="name">Name: A to Z</option>
					</select>
				</div>
				<button type="button" id="addBorBtn" class="regents-btn regents-btn-primary"><i data-lucide="plus"></i> Add Regent</button>
			</div>

			<div class="regents-card">
				<div class="regents-card-head">
					<h2>Board of Regents</h2>
					<span id="tableBorCount" class="regents-count">0 regents</span>
				</div>
				<div class="regents-table-wrap">
					<table class="regents-table">
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
						<tbody id="borTableBody"></tbody>
					</table>
				</div>
				<div id="borEmpty" class="regents-empty" hidden>
					<i data-lucide="user-round-search"></i>
					<h3>No regents found</h3>
					<p>Try a different search term or status filter.</p>
				</div>
			</div>

			<nav class="regents-pagination" id="borPagination" aria-label="Board of regents pagination" hidden>
				<button class="regents-page-btn" id="borPrevPage" type="button" aria-label="Previous page">
					<i data-lucide="chevron-left"></i>
				</button>
				<div class="regents-page-numbers" id="borPageNumbers"></div>
				<button class="regents-page-btn" id="borNextPage" type="button" aria-label="Next page">
					<i data-lucide="chevron-right"></i>
				</button>
				<span class="regents-page-total" id="borPageTotal">1 page</span>
			</nav>

			<div id="borModal" class="regents-modal" aria-hidden="true">
				<div class="regents-modal-backdrop" data-close-bor-modal></div>
				<div class="regents-modal-panel" role="dialog" aria-modal="true" aria-labelledby="borModalTitle">
					<div class="regents-modal-head">
						<div>
							<h2 id="borModalTitle">Add Regent</h2>
							<p>Add or update a regent’s photo, name, position, biography, display order, status, and expiry date.</p>
						</div>
						<button type="button" class="regents-modal-close" data-close-bor-modal aria-label="Close form"><i data-lucide="x"></i></button>
					</div>
					<form id="borForm" class="regents-form" enctype="multipart/form-data">
						<div class="regents-upload-area">
							<div id="borUploadInitials" class="regents-upload-placeholder"><i data-lucide="user"></i><strong>Regent Photo</strong></div>
							<img id="borUploadImage" class="regents-image-preview" alt="Regent photo preview">
							<input id="borPhoto" type="file" accept="image/*" hidden>
							<button type="button" id="chooseBorPhotoBtn" class="regents-btn regents-btn-ghost"><i data-lucide="upload"></i> Choose Photo</button>
							<button type="button" id="removeBorPhoto" class="regents-btn regents-btn-danger" hidden><i data-lucide="x"></i> Remove Photo</button>
						</div>

						<div class="regents-form-grid">
							<label class="regents-form-field">
								<span>Full Name</span>
								<input id="borName" name="name" type="text" placeholder="Enter full name" required>
							</label>
							<label class="regents-form-field">
								<span>Position</span>
								<input id="borPosition" name="position" type="text" placeholder="Enter regent position" required>
							</label>
							<label class="regents-form-field full-width">
								<span>Biography / Short Description</span>
								<textarea id="borBio" name="bio" rows="4" placeholder="Enter biography or additional role information"></textarea>
							</label>
							<label class="regents-form-field">
								<span>Display Order</span>
								<input id="borOrder" name="displayOrder" type="number" min="1" value="1" required>
							</label>
							<label class="regents-form-field">
								<span>Status</span>
								<select id="borStatus" name="status">
									<option value="Active">Active</option>
									<option value="Hidden">Hidden</option>
								</select>
							</label>
							<label class="regents-form-field full-width">
								<span>Expiry Date (optional)</span>
								<input id="borExpiryDate" name="expiryDate" type="date">
							</label>
						</div>

						<div class="regents-form-actions">
							<button type="button" id="cancelBorBtn" class="regents-btn regents-btn-ghost">Cancel</button>
							<div class="regents-form-actions-right">
								<button type="button" id="previewBorFormBtn" class="regents-btn regents-btn-ghost"><i data-lucide="eye"></i> Preview</button>
								<button type="submit" id="saveBorBtn" class="regents-btn regents-btn-primary"><i data-lucide="save"></i> Save Regent</button>
							</div>
						</div>
					</form>
				</div>
			</div>

			<div id="borPreviewModal" class="regents-modal" aria-hidden="true">
				<div class="regents-modal-backdrop" data-close-bor-preview></div>
				<article class="regents-modal-panel regents-preview-panel" role="dialog" aria-modal="true" aria-labelledby="borPreviewHeading">
					<div class="regents-modal-head">
						<div><h2 id="borPreviewHeading">Regent Details</h2></div>
						<button type="button" class="regents-modal-close" data-close-bor-preview aria-label="Close preview"><i data-lucide="x"></i></button>
					</div>
					<div class="regents-preview-scroll">
						<div class="regents-preview-body">
							<div class="regents-preview-top">
								<div class="regents-preview-photo">
									<span id="borPreviewInitials">RG</span>
									<img id="borPreviewImage" alt="Regent photo" hidden>
								</div>
								<div>
									<h3 class="regents-preview-name" id="borPreviewName">Regent Name</h3>
									<p class="regents-preview-position" id="borPreviewPosition"></p>
									<span id="borPreviewStatus" class="regents-status regents-status-active">Active</span>
								</div>
							</div>
							<div class="regents-preview-section">
								<p class="regents-preview-label">Biography / Short Description</p>
								<p id="borPreviewBio" class="regents-preview-text"></p>
							</div>
							<p id="borPreviewOrder" class="regents-preview-order"></p>
						</div>
					</div>
					<footer class="regents-preview-meta">
						<div><span>Created by</span><strong id="borPreviewCreatedBy">—</strong></div>
						<div><span>Date created</span><strong id="borPreviewDateCreated">—</strong></div>
						<div><span>Last updated by</span><strong id="borPreviewLastUpdatedBy">—</strong></div>
						<div><span>Expiry date</span><strong id="borPreviewExpiry">—</strong></div>
					</footer>
				</article>
			</div>

			<div class="regents-toast" id="borToast" role="status" aria-live="polite"></div>
		</section>
	</main>
</div>

<script src="assets/js/shared-layout.js"></script>
<script src="assets/js/regents.js"></script>

</body>
</html>