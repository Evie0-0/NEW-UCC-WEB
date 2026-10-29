<?php
declare(strict_types=1);

use Serv\UserServices;

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Support/helper.php';

if (!UserServices::isAuthenticated()) {
    header('Location: login.php?error=401');
    exit;
}

if (!UserServices::isAuthorized([UserServices::SUPERADMIN_KEY])) {
    header('Location: login.php?error=403');
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$username = $_SESSION[UserServices::USERNAME_KEY];
$role = $_SESSION[UserServices::ROLE_KEY];

session_write_close();

$pageTitle = 'Audit Trail';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1.0">
	<title>UCC Admin | <?= e($pageTitle) ?></title>
	<link rel="icon" type="image/png" href="assets/images/ucc-LOGO.png">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Libre+Baskerville:wght@700&display=swap" rel="stylesheet">
	<script src="https://unpkg.com/lucide@latest"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script type="module" src="assets/js/audit-trail.js"></script>
	<link rel="stylesheet" href="assets/css/style.css">
	<link rel="stylesheet" href="assets/css/post.css">
	<link rel="stylesheet" href="assets/css/audit.css">
	<style>
		#auditTable { table-layout: fixed; }
		#auditTable th, #auditTable td { text-align: center; }
		#auditTable th:nth-child(1) { width: 15%; }
		#auditTable th:nth-child(2) { width: 40%; }
		#auditTable th:nth-child(3) { width: 20%; }
		#auditTable th:nth-child(4) { width: 25%; }
		#auditTable td[data-field] {
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
		}
		#auditPagination[hidden] { display: none; }
	</style>
</head>
<body data-page-title="<?= e($pageTitle) ?>">
<div class="dashboard-layout">
	<?php include __DIR__ . '/template/sidebar.php'; ?>
	<main class="main-content">
		<?php include __DIR__ . '/template/header.php'; ?>
		<section class="posts-page">
			<div class="posts-intro"><div><p>A read-only record of actions performed in the CMS.</p></div></div>
			<div class="posts-toolbar">
				<div class="posts-tools-left">
					<div class="posts-search">
						<input type="search" id="auditSearch" placeholder="Search audit trails..." aria-label="Search description">
						<i data-lucide="search"></i>
					</div>
					<select id="auditFilter" class="posts-filter" aria-label="Filter by action" disabled>
						<option value="">Loading actions…</option>
					</select>
					<select id="auditSort" class="posts-filter" aria-label="Order entries">
						<option value="added-desc">Date added (newest first)</option>
						<option value="added-asc">Date added (oldest first)</option>
						<option value="description-asc">Description (A–Z)</option>
						<option value="description-desc">Description (Z–A)</option>
						<option value="username-asc">Username (A–Z)</option>
						<option value="username-desc">Username (Z–A)</option>
					</select>
				</div>
			</div>

			<div class="posts-card">
				<div class="posts-card-head"><h2>Audit Trail</h2><span class="posts-count" id="auditCount">0 logs</span></div>
				<div class="posts-table-wrap">
					<table class="posts-table" id="auditTable">
						<thead>
							<tr><th>Action</th><th>Description</th><th>Performed By</th><th>Date &amp; Time</th></tr>
						</thead>
						<tbody id="auditTableBody"></tbody>
					</table>
				</div>
			</div>

			<template id="auditRowTemplate">
				<tr>
					<td data-field="action"></td>
					<td data-field="description"></td>
					<td data-field="username"></td>
					<td data-field="createdAt"></td>
				</tr>
			</template>

			<nav class="posts-pagination" id="auditPagination" aria-label="Audit trail pagination" hidden>
				<button class="page-btn" id="prevPageBtn" type="button" aria-label="Previous page"><i data-lucide="chevron-left"></i></button>
				<div class="page-numbers" id="pageNumbers"></div>
				<button class="page-btn" id="nextPageBtn" type="button" aria-label="Next page"><i data-lucide="chevron-right"></i></button>
				<span class="page-total" id="pageTotal">1 page</span>
			</nav>
		</section>
	</main>
</div>
</body>
</html>
