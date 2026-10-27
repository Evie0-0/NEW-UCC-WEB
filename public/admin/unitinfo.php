<?php
$pageTitle = "Unit Information";
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
	<link rel="stylesheet" href="assets/css/unitinfo.css">
</head>
<body data-page-title="<?= htmlspecialchars($pageTitle) ?>">
<div class="dashboard-layout">
	<?php include __DIR__ . '/template/sidebar.php'; ?>
	<main class="main-content">
		<?php include __DIR__ . '/template/header.php'; ?>
		
		<section class="unitinfo-page">
			<div class="unitinfo-intro">
				<div>
					<p>Manage unit mission, vision, descriptions, and publication status.</p>
				</div>
			</div>

			<div class="unitinfo-toolbar">
				<div class="unitinfo-tools-left">
					<div class="unitinfo-search">
						<input type="search" id="unitInfoSearch" placeholder="Search unit information..." aria-label="Search unit information">
						<i data-lucide="search"></i>
					</div>

					<select id="unitInfoUnitFilter" class="unitinfo-filter" aria-label="Filter by unit">
						<option value="All">All units</option>
						<option>College of Arts and Sciences</option>
						<option>College of Business and Accountancy</option>
						<option>College of Education</option>
						<option>College of Computer Studies</option>
						<option>College of Engineering</option>
						<option>Office of Student Affairs</option>
					</select>

					<select id="unitInfoStatusFilter" class="unitinfo-filter" aria-label="Filter by status">
						<option value="All">All statuses</option>
						<option value="Active">Active</option>
						<option value="Hidden">Hidden</option>
					</select>

					<select id="unitInfoSort" class="unitinfo-filter" aria-label="Sort records">
						<option value="name-asc">Unit: A to Z</option>
						<option value="name-desc">Unit: Z to A</option>
						<option value="newest">Newest added</option>
						<option value="oldest">Oldest added</option>
					</select>
				</div>
				<button type="button" id="addUnitInfoBtn" class="unitinfo-btn unitinfo-btn-primary"><i data-lucide="plus"></i> Add Information</button>
			</div>

			<div class="unitinfo-card">
				<div class="unitinfo-card-head">
					<h2>Unit Information</h2>
					<span id="unitInfoCount" class="unitinfo-count">0 records</span>
				</div>
				<div class="unitinfo-table-wrap">
					<table class="unitinfo-table">
						<thead>
							<tr>
								<th>Unit</th>
								<th>Mission</th>
								<th>Vision</th>
								<th>Description</th>
								<th>Status</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody id="unitInfoTableBody"></tbody>
					</table>
				</div>
				<div id="unitInfoEmpty" class="unitinfo-empty" hidden>
					<i data-lucide="building-2"></i>
					<h3>No unit information found</h3>
					<p>Try another search term, unit, or status filter.</p>
				</div>
			</div>

			<nav class="unitinfo-pagination" id="unitInfoPagination" aria-label="Unit information pagination" hidden>
				<button class="unitinfo-page-btn" id="unitInfoPrevPage" type="button" aria-label="Previous page">
					<i data-lucide="chevron-left"></i>
				</button>
				<div class="unitinfo-page-numbers" id="unitInfoPageNumbers"></div>
				<button class="unitinfo-page-btn" id="unitInfoNextPage" type="button" aria-label="Next page">
					<i data-lucide="chevron-right"></i>
				</button>
				<span class="unitinfo-page-total" id="unitInfoPageTotal">1 page</span>
			</nav>

			<div id="unitInfoFormModal" class="unitinfo-modal" aria-hidden="true">
				<div class="unitinfo-modal-backdrop" data-close-unit-info-form></div>
				<div class="unitinfo-modal-panel" role="dialog" aria-modal="true" aria-labelledby="unitInfoFormTitle">
					<div class="unitinfo-modal-head">
						<div>
							<h2 id="unitInfoFormTitle">Add Unit Information</h2>
							<p>Add or update a unit's mission, vision, description, and status.</p>
						</div>
						<button type="button" class="unitinfo-modal-close" data-close-unit-info-form aria-label="Close form"><i data-lucide="x"></i></button>
					</div>
					<form id="unitInfoForm" class="unitinfo-form">
						<div class="unitinfo-form-grid">
							<label class="unitinfo-form-field full-width">
								<span>Unit</span>
								<select id="unitInfoUnit" required>
									<option value="">Select a unit</option>
									<option>College of Arts and Sciences</option>
									<option>College of Business and Accountancy</option>
									<option>College of Education</option>
									<option>College of Computer Studies</option>
									<option>College of Engineering</option>
									<option>Office of Student Affairs</option>
								</select>
							</label>
							<label class="unitinfo-form-field full-width">
								<span>Mission</span>
								<textarea id="unitInfoMission" rows="3" placeholder="Enter the unit's mission" required></textarea>
							</label>
							<label class="unitinfo-form-field full-width">
								<span>Vision</span>
								<textarea id="unitInfoVision" rows="3" placeholder="Enter the unit's vision" required></textarea>
							</label>
							<label class="unitinfo-form-field full-width">
								<span>Description</span>
								<textarea id="unitInfoDescription" rows="4" placeholder="Enter a description of the unit" required></textarea>
							</label>
							<label class="unitinfo-form-field">
								<span>Status</span>
								<select id="unitInfoStatus" name="status">
									<option value="Active">Active</option>
									<option value="Hidden">Hidden</option>
								</select>
							</label>
						</div>
						<div class="unitinfo-form-actions">
							<button type="button" id="cancelUnitInfoBtn" class="unitinfo-btn unitinfo-btn-ghost">Cancel</button>
							<div class="unitinfo-form-actions-right">
								<button type="button" id="previewUnitInfoBtn" class="unitinfo-btn unitinfo-btn-ghost"><i data-lucide="eye"></i> Preview</button>
								<button type="submit" class="unitinfo-btn unitinfo-btn-primary"><i data-lucide="save"></i> Save Information</button>
							</div>
						</div>
					</form>
				</div>
			</div>

			<div id="unitInfoPreviewModal" class="unitinfo-modal" aria-hidden="true">
				<div class="unitinfo-modal-backdrop" data-close-unit-info-preview></div>
				<article class="unitinfo-modal-panel unitinfo-preview-panel" role="dialog" aria-modal="true" aria-labelledby="unitInfoPreviewTitle">
					<div class="unitinfo-modal-head">
						<div><h2 id="unitInfoPreviewTitle">Unit Information Preview</h2></div>
						<button type="button" class="unitinfo-modal-close" data-close-unit-info-preview aria-label="Close preview"><i data-lucide="x"></i></button>
					</div>
					<div class="unitinfo-preview-body">
						<p class="unitinfo-preview-eyebrow">Unit</p>
						<h3 class="unitinfo-preview-name" id="previewUnitInfoUnit">Unit name</h3>
						<span id="previewUnitInfoStatus" class="unitinfo-status unitinfo-status-active">Active</span>

						<div class="unitinfo-preview-section">
							<p class="unitinfo-preview-label">Mission</p>
							<p id="previewUnitInfoMission" class="unitinfo-preview-text"></p>
						</div>
						<div class="unitinfo-preview-section">
							<p class="unitinfo-preview-label">Vision</p>
							<p id="previewUnitInfoVision" class="unitinfo-preview-text"></p>
						</div>
						<div class="unitinfo-preview-section">
							<p class="unitinfo-preview-label">Description</p>
							<p id="previewUnitInfoDescription" class="unitinfo-preview-text"></p>
						</div>
					</div>
					<footer class="unitinfo-preview-meta">
						<div><span>Created by</span><strong id="unitInfoCreatedBy">Admin</strong></div>
						<div><span>Date created</span><strong id="unitInfoDateCreated">—</strong></div>
						<div><span>Last updated by</span><strong id="unitInfoLastUpdatedBy">—</strong></div>
					</footer>
				</article>
			</div>

			<div class="unitinfo-toast" id="unitInfoToast" role="status" aria-live="polite"></div>
		</section>
	</main>
</div>

<script src="assets/js/shared-layout.js"></script>
<script src="assets/js/unitinfo.js"></script>

</body>
</html>