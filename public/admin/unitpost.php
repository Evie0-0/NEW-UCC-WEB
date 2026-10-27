<?php
$pageTitle = "Unit Posts";
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
	<link rel="stylesheet" href="assets/css/unitnews.css">
</head>
<body data-page-title="<?= htmlspecialchars($pageTitle) ?>">
<div class="dashboard-layout">
	<?php include __DIR__ . '/template/sidebar.php'; ?>
	<main class="main-content">
		<?php include __DIR__ . '/template/header.php'; ?>
		
		<section class="unitnews-page">
			<div class="unitnews-intro">
				<div>
					<p>Manage news and announcements published by university units.</p>
				</div>
			</div>

			<div class="unitnews-toolbar">
				<div class="unitnews-tools-left">
					<div class="unitnews-search">
						<input type="search" id="newsSearch" placeholder="Search unit posts..." aria-label="Search unit posts">
						<i data-lucide="search"></i>
					</div>

					<select id="newsUnitFilter" class="unitnews-filter" aria-label="Filter by unit">
						<option value="All">All units</option>
						<option>College of Arts and Sciences</option>
						<option>College of Business and Accountancy</option>
						<option>College of Education</option>
						<option>Office of Student Affairs</option>
					</select>

					<select id="newsStatusFilter" class="unitnews-filter" aria-label="Filter by status">
						<option value="All">All statuses</option>
						<option>Draft</option>
						<option>Published</option>
						<option>Expired</option>
					</select>

					<select id="newsSort" class="unitnews-filter" aria-label="Sort unit posts">
						<option value="newest">Newest first</option>
						<option value="oldest">Oldest first</option>
						<option value="title-asc">Title: A to Z</option>
						<option value="title-desc">Title: Z to A</option>
						<option value="expiry-asc">Expiry date: soonest</option>
						<option value="expiry-desc">Expiry date: latest</option>
					</select>
				</div>
				<button type="button" id="addNewsBtn" class="unitnews-btn unitnews-btn-primary"><i data-lucide="plus"></i> Add Unit Posts</button>
			</div>

			<div class="unitnews-card">
				<div class="unitnews-card-head">
					<h2>Unit Posts</h2>
					<span id="newsCount" class="unitnews-count">0 items</span>
				</div>
				<div class="unitnews-table-wrap">
					<table class="unitnews-table">
						<thead>
							<tr>
								<th>Featured Image</th>
								<th>Title</th>
								<th>Unit</th>
								<th>Excerpt</th>
								<th>Publish Date</th>
								<th>Expiry Date</th>
								<th>Status</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody id="newsTableBody"></tbody>
					</table>
				</div>
				<div id="newsEmpty" class="unitnews-empty" hidden>
					<i data-lucide="newspaper"></i>
					<h3>No unit posts found</h3>
					<p>Try a different search term or filter.</p>
				</div>
			</div>

			<nav class="unitnews-pagination" id="newsPagination" aria-label="Unit news pagination" hidden>
				<button class="unitnews-page-btn" id="newsPrevPage" type="button" aria-label="Previous page">
					<i data-lucide="chevron-left"></i>
				</button>
				<div class="unitnews-page-numbers" id="newsPageNumbers"></div>
				<button class="unitnews-page-btn" id="newsNextPage" type="button" aria-label="Next page">
					<i data-lucide="chevron-right"></i>
				</button>
				<span class="unitnews-page-total" id="newsPageTotal">1 page</span>
			</nav>

			<div id="newsFormModal" class="unitnews-modal" aria-hidden="true">
				<div class="unitnews-modal-backdrop" data-close-news-form></div>
				<div class="unitnews-modal-panel" role="dialog" aria-modal="true" aria-labelledby="newsFormTitle">
					<div class="unitnews-modal-head">
						<div>
							<h2 id="newsFormTitle">Add Unit Posts</h2>
							<p>Add or update the featured image, title, excerpt, publication date, and status.</p>
						</div>
						<button type="button" class="unitnews-modal-close" data-close-news-form aria-label="Close form"><i data-lucide="x"></i></button>
					</div>
					<form id="newsForm" class="unitnews-form" enctype="multipart/form-data">
						<div class="unitnews-upload-area">
							<div id="newsImagePlaceholder" class="unitnews-upload-placeholder"><i data-lucide="image"></i><strong>Featured Image</strong></div>
							<img id="newsImagePreview" class="unitnews-image-preview" alt="Featured image preview">
							<input id="newsImageFile" type="file" accept="image/*" hidden>
							<button type="button" id="chooseNewsImageBtn" class="unitnews-btn unitnews-btn-ghost"><i data-lucide="upload"></i> Choose Photo</button>
							<button type="button" id="removeNewsImage" class="unitnews-btn unitnews-btn-danger" hidden><i data-lucide="x"></i> Remove Photo</button>
						</div>

						<div class="unitnews-form-grid">
							<label class="unitnews-form-field full-width">
								<span>Unit</span>
								<select id="newsUnit" name="unit" required>
									<option value="">Select a unit</option>
									<option>College of Arts and Sciences</option>
									<option>College of Business and Accountancy</option>
									<option>College of Education</option>
									<option>Office of Student Affairs</option>
								</select>
							</label>
							<label class="unitnews-form-field full-width">
								<span>Title</span>
								<input id="newsTitle" name="title" type="text" maxlength="180" placeholder="Enter unit posts title" required>
							</label>
							<label class="unitnews-form-field full-width">
								<span>Excerpt</span>
								<textarea id="newsExcerpt" name="excerpt" rows="4" maxlength="500" placeholder="Write a short summary of the unit posts" required></textarea>
							</label>
							<label class="unitnews-form-field">
								<span>Publish Date</span>
								<input id="newsPublishDate" name="publishDate" type="date" required>
							</label>
							<label class="unitnews-form-field">
								<span>Expiry Date (optional)</span>
								<input id="newsExpiryDate" name="expiryDate" type="date">
							</label>
							<label class="unitnews-form-field full-width">
								<span>Status</span>
								<select id="newsStatus" name="status">
									<option>Draft</option>
									<option>Published</option>
								</select>
							</label>
						</div>

						<div class="unitnews-form-actions">
							<button type="button" id="cancelNewsBtn" class="unitnews-btn unitnews-btn-ghost">Cancel</button>
							<div class="unitnews-form-actions-right">
								<button type="button" id="previewNewsBtn" class="unitnews-btn unitnews-btn-ghost"><i data-lucide="eye"></i> Preview</button>
								<button type="submit" class="unitnews-btn unitnews-btn-primary"><i data-lucide="save"></i> Save Unit Posts</button>
							</div>
						</div>
					</form>
				</div>
			</div>

			<div id="newsPreviewModal" class="unitnews-modal" aria-hidden="true">
				<div class="unitnews-modal-backdrop" data-close-news-preview></div>
				<article class="unitnews-modal-panel unitnews-preview-panel" role="dialog" aria-modal="true" aria-labelledby="newsPreviewHeading">
					<div class="unitnews-modal-head">
						<div><h2 id="newsPreviewHeading">Unit Posts Preview</h2></div>
						<button type="button" class="unitnews-modal-close" data-close-news-preview aria-label="Close preview"><i data-lucide="x"></i></button>
					</div>
					<div class="unitnews-preview-scroll">
						<div id="previewNewsImageWrap" class="unitnews-preview-image">
							<div id="previewNewsImagePlaceholder" class="unitnews-preview-noimg"><i data-lucide="image"></i><span>No featured image</span></div>
							<img id="previewNewsImage" alt="Featured image" hidden>
						</div>
						<div class="unitnews-preview-body">
							<p class="unitnews-preview-eyebrow" id="previewNewsUnit">Unit name</p>
							<h3 class="unitnews-preview-name" id="previewNewsTitle">Unit Posts title</h3>
							<span id="previewNewsStatus" class="unitnews-status unitnews-status-draft">Draft</span>
							<p class="unitnews-preview-date" id="previewNewsDate">—</p>
							<p class="unitnews-preview-date" id="previewNewsExpiry">—</p>

							<div class="unitnews-preview-section">
								<p class="unitnews-preview-label">Excerpt</p>
								<p id="previewNewsExcerpt" class="unitnews-preview-text"></p>
							</div>
						</div>
					</div>
					<footer class="unitnews-preview-meta">
						<div><span>Created by</span><strong id="newsCreatedBy">Admin</strong></div>
						<div><span>Date created</span><strong id="newsDateCreated">—</strong></div>
						<div><span>Last updated</span><strong id="newsLastUpdatedBy">—</strong></div>
					</footer>
				</article>
			</div>

			<div class="unitnews-toast" id="newsToast" role="status" aria-live="polite"></div>
		</section>
	</main>
</div>

<script src="assets/js/shared-layout.js"></script>
<script src="assets/js/unitnews.js"></script>

</body>
</html>