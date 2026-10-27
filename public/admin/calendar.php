<?php
$pageTitle = "Calendar";
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
	<link rel="stylesheet" href="assets/css/calendar.css">
</head>
<body data-page-title="<?= htmlspecialchars($pageTitle) ?>">
<div class="dashboard-layout">
	<?php include __DIR__ . '/template/sidebar.php'; ?>
	<main class="main-content">
		<?php include __DIR__ . '/template/header.php'; ?>

		<section class="posts-page">
			<div class="posts-intro"><div><p>Manage events and activities shown on the university calendar.</p></div></div>

			<div class="posts-toolbar">
				<div class="posts-tools-left">
					<div class="posts-search">
						<input type="search" id="calendarSearch" placeholder="Search by title..." aria-label="Search events">
						<i data-lucide="search"></i>
					</div>
					<select id="calendarFilter" class="posts-filter" aria-label="Filter by status">
						<option value="All">All statuses</option>
						<option value="Published">Published</option>
						<option value="Pending">Pending</option>
						<option value="Draft">Draft</option>
					</select>
					<input type="date" id="calendarDate" class="posts-filter" aria-label="Filter by publish date" title="Publish date">
					<select id="calendarSort" class="posts-filter" aria-label="Order events">
						<option value="created-desc">Newest created</option>
						<option value="created-asc">Oldest created</option>
						<option value="date-desc">Publish date: newest</option>
						<option value="date-asc">Publish date: oldest</option>
						<option value="start-asc">Start date: soonest</option>
						<option value="start-desc">Start date: latest</option>
						<option value="title-asc">Title: A–Z</option>
						<option value="title-desc">Title: Z–A</option>
					</select>
				</div>
				<button class="post-btn post-btn-primary" id="addEventBtn" type="button"><i data-lucide="plus"></i> Add New Event</button>
			</div>

			<div class="posts-card">
				<div class="posts-card-head"><h2>Calendar</h2><span class="posts-count" id="calendarCount">0 events</span></div>
				<div class="posts-table-wrap">
					<table class="posts-table">
						<thead>
							<tr>
								<th class="col-image">Featured Image</th>
								<th class="col-title">Title</th>
								<th class="col-date">Publish Date</th>
								<th class="col-date">Start Date</th>
								<th class="col-date">End Date</th>
								<th class="col-status">Status</th>
								<th class="col-actions">Actions</th>
							</tr>
						</thead>
						<tbody id="calendarTableBody"></tbody>
					</table>
				</div>
			</div>

			<nav class="posts-pagination" id="calendarPagination" aria-label="Calendar pagination" hidden>
				<button class="page-btn" id="prevPageBtn" type="button" aria-label="Previous page"><i data-lucide="chevron-left"></i></button>
				<div class="page-numbers" id="pageNumbers"></div>
				<button class="page-btn" id="nextPageBtn" type="button" aria-label="Next page"><i data-lucide="chevron-right"></i></button>
				<span class="page-total" id="pageTotal">1 page</span>
			</nav>

			<div class="post-modal" id="eventModal" aria-hidden="true">
				<div class="post-modal-backdrop" data-close-modal></div>
				<div class="post-modal-panel" role="dialog" aria-modal="true" aria-labelledby="eventFormTitle">
					<div class="post-modal-head">
						<div><h2 id="eventFormTitle">Add New Event</h2><p id="eventFormDescription">Create an event for the university calendar.</p></div>
						<button class="modal-close" type="button" data-close-modal aria-label="Close event form"><i data-lucide="x"></i></button>
					</div>
					<form class="post-form" id="eventForm">
						<div class="image-upload-area">
							<div class="upload-placeholder" id="uploadPlaceholder"><i data-lucide="image-plus"></i><strong>Featured Image</strong></div>
							<img id="imagePreview" class="image-preview" alt="Featured image preview">
							<input type="file" id="featuredImage" accept="image/*" hidden>
							<button type="button" class="post-btn post-btn-ghost" id="uploadImageBtn"><i data-lucide="upload"></i> Choose Photo</button>
							<button type="button" class="post-btn post-btn-danger" id="removeImageBtn" hidden><i data-lucide="x"></i> Remove Photo</button>
						</div>
						<div class="form-grid">
							<label class="form-field full-width"><span>Title</span><input type="text" id="eventTitle" placeholder="Enter event title" maxlength="180" required></label>
							<label class="form-field full-width"><span>Description</span><textarea id="eventDescription" rows="5" placeholder="Describe the event" required></textarea></label>
							<label><span>Status</span><select id="eventStatus"><option>Draft</option><option>Published</option></select></label>
							<label class="publish-date-field"><span>Publish Date</span><input type="date" id="eventPublishDate"></label>
							<label class="publish-date-field"><span>Start Date</span><input type="date" id="eventStartDate" required></label>
							<label class="publish-date-field"><span>End Date</span><input type="date" id="eventEndDate" required></label>
						</div>
						<div class="form-actions">
							<button type="button" class="post-btn post-btn-ghost form-cancel" data-close-modal>Cancel</button>
							<div class="form-actions-right">
								<button type="button" class="post-btn post-btn-ghost" id="formPreviewBtn"><i data-lucide="eye"></i> Preview</button>
								<button type="submit" class="post-btn post-btn-primary"><i data-lucide="save"></i> Save Event</button>
							</div>
						</div>
					</form>
				</div>
			</div>

			<div class="post-modal preview-modal" id="eventPreviewModal" aria-hidden="true">
				<div class="post-modal-backdrop" data-close-preview></div>
				<article class="post-modal-panel post-preview-panel" role="dialog" aria-modal="true" aria-labelledby="previewTitle">
					<div class="post-modal-head"><div><h2>Event Preview</h2></div><button class="modal-close" type="button" data-close-preview aria-label="Close preview"><i data-lucide="x"></i></button></div>
					<div class="public-post-preview">
						<div class="preview-image-wrap" id="previewImageWrap"><div class="preview-default-image"><i data-lucide="image"></i><span>Featured image</span></div><img id="previewImage" alt="Event featured image" hidden></div>
						<div class="preview-article-body"><h2 id="previewTitle">Event title</h2><div class="preview-content" id="previewDescription"></div></div>
					</div>
					<footer class="post-preview-metadata">
						<div class="post-preview-metadata-grid" style="display:flex; gap:56px; text-align:left; flex-wrap:wrap;">
							<div><span>PUBLISH DATE</span><strong id="previewPublish">—</strong></div>
							<div><span>START DATE</span><strong id="previewStart">—</strong></div>
							<div><span>END DATE</span><strong id="previewEnd">—</strong></div>
							<div><span>CREATED BY</span><strong id="previewCreatedBy">—</strong></div>
						</div>
					</footer>
				</article>
			</div>
			<div class="ui-toast" id="uiToast" role="status" aria-live="polite"></div>
		</section>
	</main>
</div>
<script src="assets/js/ucc-common.js"></script>
<script src="assets/js/calendar.js"></script>
</body>
</html>