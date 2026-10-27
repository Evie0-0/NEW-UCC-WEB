<?php
$pageTitle = "Program Posts";
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
	<link rel="stylesheet" href="assets/css/programnews.css">
</head>
<body data-page-title="<?= htmlspecialchars($pageTitle) ?>">
<div class="dashboard-layout">
	<?php include __DIR__ . '/template/sidebar.php'; ?>
	<main class="main-content">
		<?php include __DIR__ . '/template/header.php'; ?>
		
		<section class="programnews-page">
			<div class="programnews-intro">
				<div>
					<p>Manage program-specific news, update announcements, preview content, and control what is published.</p>
				</div>
			</div>

			<div class="programnews-toolbar">
				<div class="programnews-tools-left">
					<div class="programnews-search">
						<input type="search" id="pnSearch" placeholder="Search program posts..." aria-label="Search program posts">
						<i data-lucide="search"></i>
					</div>

					<select id="programFilter" class="programnews-filter" aria-label="Filter by program">
						<option value="All">All programs</option>
						<option value="BSIT">Bachelor of Science in Information Technology</option>
						<option value="BSCS">Bachelor of Science in Computer Science</option>
						<option value="BSA">Bachelor of Science in Accountancy</option>
						<option value="BSED">Bachelor of Secondary Education</option>
						<option value="BSHM">Bachelor of Science in Hospitality Management</option>
					</select>

					<select id="pnStatusFilter" class="programnews-filter" aria-label="Filter by status">
						<option value="All">All statuses</option>
						<option value="Draft">Draft</option>
						<option value="Published">Published</option>
						<option value="Archived">Archived</option>
						<option value="Expired">Expired</option>
					</select>

					<select id="pnSort" class="programnews-filter" aria-label="Sort program posts">
						<option value="newest">Newest first</option>
						<option value="oldest">Oldest first</option>
						<option value="title">Title A–Z</option>
						<option value="expiry-asc">Expiry date: soonest</option>
						<option value="expiry-desc">Expiry date: latest</option>
					</select>
				</div>
				<button type="button" id="addProgramNews" class="programnews-btn programnews-btn-primary"><i data-lucide="plus"></i> Add Program News</button>
			</div>

			<div class="programnews-card">
				<div class="programnews-card-head">
					<h2>Program Posts</h2>
					<span id="pnCount" class="programnews-count">0 items</span>
				</div>
				<div class="programnews-table-wrap">
					<table class="programnews-table">
						<thead>
							<tr>
								<th>Featured Image</th>
								<th>Title / Excerpt</th>
								<th>Program</th>
								<th>Publish Date</th>
								<th>Expiry Date</th>
								<th>Status</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody id="pnTableBody"></tbody>
					</table>
				</div>
				<div id="pnEmpty" class="programnews-empty" hidden>
					<i data-lucide="newspaper"></i>
					<h3>No program posts found</h3>
					<p>Try a different search term or filter.</p>
				</div>
			</div>

			<nav class="programnews-pagination" id="programnewsPagination" aria-label="Program news pagination" hidden>
				<button class="programnews-page-btn" id="programnewsPrevPage" type="button" aria-label="Previous page">
					<i data-lucide="chevron-left"></i>
				</button>
				<div class="programnews-page-numbers" id="programnewsPageNumbers"></div>
				<button class="programnews-page-btn" id="programnewsNextPage" type="button" aria-label="Next page">
					<i data-lucide="chevron-right"></i>
				</button>
				<span class="programnews-page-total" id="programnewsPageTotal">1 page</span>
			</nav>

			<div id="pnFormModal" class="programnews-modal" aria-hidden="true">
				<div class="programnews-modal-backdrop" data-close-form></div>
				<div class="programnews-modal-panel" role="dialog" aria-modal="true" aria-labelledby="pnFormHeading">
					<div class="programnews-modal-head">
						<div>
							<h2 id="pnFormHeading">Add Program Posts</h2>
							<p>Create or update posts for a specific academic program.</p>
						</div>
						<button type="button" class="programnews-modal-close" data-close-form aria-label="Close form"><i data-lucide="x"></i></button>
					</div>
					<form id="pnForm" class="programnews-form" enctype="multipart/form-data">
						<div class="programnews-upload-area">
							<div id="pnImagePlaceholder" class="programnews-upload-placeholder"><i data-lucide="image"></i><strong>Featured Image</strong></div>
							<img id="pnImagePreview" class="programnews-image-preview" alt="Featured image preview">
							<input id="pnImageFile" type="file" accept="image/*" hidden>
							<button type="button" id="pnChooseImage" class="programnews-btn programnews-btn-ghost"><i data-lucide="upload"></i> Choose Photo</button>
							<button type="button" id="pnRemoveImage" class="programnews-btn programnews-btn-danger" hidden><i data-lucide="x"></i> Remove Photo</button>
						</div>

						<div class="programnews-form-grid">
							<label class="programnews-form-field full-width">
								<span>Title</span>
								<input id="pnTitle" type="text" required maxlength="160" placeholder="Enter program posts title">
							</label>
							<label class="programnews-form-field full-width">
								<span>Excerpt</span>
								<textarea id="pnExcerpt" required rows="4" maxlength="400" placeholder="Write a short summary of this post"></textarea>
							</label>
							<label class="programnews-form-field">
								<span>Program</span>
								<select id="pnProgram" required>
									<option value="">Select a program</option>
									<option value="BSIT">BSIT</option>
									<option value="BSCS">BSCS</option>
									<option value="BSA">BSA</option>
									<option value="BSED">BSED</option>
									<option value="BSHM">BSHM</option>
								</select>
							</label>
							<label class="programnews-form-field">
								<span>Status</span>
								<select id="pnStatus">
									<option value="Draft">Draft</option>
									<option value="Published">Published</option>
								</select>
							</label>
							<label class="programnews-form-field">
								<span>Publish Date</span>
								<input id="pnDate" type="date" required>
							</label>
							<label class="programnews-form-field">
								<span>Expiry Date (optional)</span>
								<input id="pnExpiry" type="date">
							</label>
						</div>

						<div class="programnews-form-actions">
							<button type="button" class="programnews-btn programnews-btn-ghost" data-close-form>Cancel</button>
							<div class="programnews-form-actions-right">
								<button type="button" id="pnPreviewFromForm" class="programnews-btn programnews-btn-ghost"><i data-lucide="eye"></i> Preview</button>
								<button type="submit" class="programnews-btn programnews-btn-primary"><i data-lucide="save"></i> Save Program Posts</button>
							</div>
						</div>
					</form>
				</div>
			</div>

			<div id="pnPreviewModal" class="programnews-modal" aria-hidden="true">
				<div class="programnews-modal-backdrop" data-close-preview></div>
				<article class="programnews-modal-panel programnews-preview-panel" role="dialog" aria-modal="true" aria-labelledby="pnPreviewHeading">
					<div class="programnews-modal-head">
						<div><h2 id="pnPreviewHeading">Program Posts Preview</h2></div>
						<button type="button" class="programnews-modal-close" data-close-preview aria-label="Close preview"><i data-lucide="x"></i></button>
					</div>
					<div class="programnews-preview-scroll">
						<div id="previewPnImageWrap" class="programnews-preview-image">
							<div id="pnPreviewNoImage" class="programnews-preview-noimg"><i data-lucide="image"></i><span>No featured image</span></div>
							<img id="pnPreviewImage" alt="Featured image" hidden>
						</div>
						<div class="programnews-preview-body">
							<p class="programnews-preview-eyebrow" id="pnPreviewProgram">Program</p>
							<h3 class="programnews-preview-name" id="pnPreviewTitle">Untitled program posts</h3>
							<span id="pnPreviewStatus" class="programnews-status programnews-status-draft">Draft</span>
							<p class="programnews-preview-date" id="pnPreviewDate">—</p>
							<p class="programnews-preview-date" id="pnPreviewExpiry">—</p>

							<div class="programnews-preview-section">
								<p class="programnews-preview-label">Excerpt</p>
								<p id="pnPreviewExcerpt" class="programnews-preview-text"></p>
							</div>
						</div>
					</div>
					<footer class="programnews-preview-meta">
						<div><span>Created by</span><strong id="pnCreatedBy">Admin</strong></div>
						<div><span>Date created</span><strong id="pnDateCreated">—</strong></div>
						<div><span>Last updated</span><strong id="pnUpdatedBy">Admin</strong></div>
					</footer>
				</article>
			</div>

			<div class="programnews-toast" id="pnToast" role="status" aria-live="polite"></div>
		</section>
	</main>
</div>

<script src="assets/js/shared-layout.js"></script>
<script src="assets/js/program-posts.js"></script>
<script src="assets/js/ucc-common.js"></script>

</body>
</html>