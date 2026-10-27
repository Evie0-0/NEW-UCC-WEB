<?php
$pageTitle = "Audit Trail";
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
	<link rel="stylesheet" href="assets/css/post.css">
	<link rel="stylesheet" href="assets/css/audit.css">
</head>
<body data-page-title="<?= htmlspecialchars($pageTitle) ?>">
<div class="dashboard-layout">
	<?php include __DIR__ . '/template/sidebar.php'; ?>
	<main class="main-content">
		<?php include __DIR__ . '/template/header.php'; ?>

		<section class="posts-page">
			<div class="posts-intro"><div><p>A read-only record of actions performed in the CMS.</p></div></div>

			<div class="posts-toolbar">
				<div class="posts-tools-left">
					<div class="posts-search">
						<input type="search" id="auditSearch" placeholder="Search description..." aria-label="Search description">
						<i data-lucide="search"></i>
					</div>
					<select id="auditFilter" class="posts-filter" aria-label="Filter by action">
						<option value="All">All actions</option>
						<option value="Create">Create</option>
						<option value="Update">Update</option>
						<option value="Delete">Delete</option>
						<option value="Archive">Archive</option>
						<option value="Login">Login</option>
						<option value="Logout">Logout</option>
					</select>
					<input type="date" id="auditDate" class="posts-filter" aria-label="Filter by date created" title="Date created">
					<select id="auditSort" class="posts-filter" aria-label="Order entries">
						<option value="created-desc">Newest first</option>
						<option value="created-asc">Oldest first</option>
						<option value="action-asc">Action: A–Z</option>
						<option value="action-desc">Action: Z–A</option>
						<option value="by-asc">Performed by: A–Z</option>
						<option value="by-desc">Performed by: Z–A</option>
					</select>
				</div>
			</div>

			<div class="posts-card">
				<div class="posts-card-head"><h2>Audit Trail</h2><span class="posts-count" id="auditCount">0 entries</span></div>
				<div class="posts-table-wrap">
					<table class="posts-table">
						<thead>
							<tr><th>Action</th><th>Description</th><th>Performed By</th><th>Date &amp; Time</th></tr>
						</thead>
						<tbody id="auditTableBody"></tbody>
					</table>
				</div>
			</div>

			<nav class="posts-pagination" id="auditPagination" aria-label="Audit trail pagination" hidden>
				<button class="page-btn" id="prevPageBtn" type="button" aria-label="Previous page"><i data-lucide="chevron-left"></i></button>
				<div class="page-numbers" id="pageNumbers"></div>
				<button class="page-btn" id="nextPageBtn" type="button" aria-label="Next page"><i data-lucide="chevron-right"></i></button>
				<span class="page-total" id="pageTotal">1 page</span>
			</nav>
		</section>
	</main>
</div>
<script src="assets/js/ucc-common.js"></script>
<script src="assets/js/audit.js"></script>
</body>
</html>