<?php
declare(strict_types=1);

use Serv\UserServices;
use Core\Token;

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
$csrfToken = Token::generate();

session_write_close();

$pageTitle = "Executive Officials";
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>UCC CMS | <?= e($pageTitle) ?></title>
	<link rel="icon" type="image/png" href="assets/images/ucc-LOGO.png">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Libre+Baskerville:wght@700&display=swap" rel="stylesheet">
	<script src="https://unpkg.com/lucide@latest"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script type="module" src="assets/js/officials.js"></script>
	<link rel="stylesheet" href="assets/css/style.css">
	<link rel="stylesheet" href="assets/css/officials.css">
	<style>
        .executive-table { table-layout: fixed; }
        .executive-table th, .executive-table td { text-align: center; }
        .executive-table td { cursor: default; }
        .executive-table td[data-field], .executive-table [data-field="status"] {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .executive-table [data-field="status"] { display: inline-block; max-width: 100%; box-sizing: border-box; }
        .executive-thumb { width: 64px; margin: 0 auto; border-radius: 12px; border: 1px dashed #d8b056; color: #9a8a62; }
        .executive-thumb [hidden] { display: none !important; }
        .executive-thumb [data-field="imagePlaceholder"] { display: flex; align-items: center; justify-content: center; }
        .executive-thumb svg { width: 18px; height: 18px; }
        .executive-thumb img:not([hidden]) { object-fit: contain; }
        .executive-actions { justify-content: center; padding-right: 0; }
        .executive-image-preview { border-radius: 10px; }
        .executive-preview-panel .executive-modal-head { background: #fffdf8; }
        .executive-preview-scroll { background: #fff; }
        .executive-preview-photo { border-radius: 10px; border: 1px dashed #d8b056; color: #9a8a62; }
        .executive-preview-photo #officialPreviewImagePlaceholder { display: flex; align-items: center; justify-content: center; }
        .executive-preview-photo svg { width: 24px; height: 24px; }
        .executive-preview-position { color: #0d2a21; }
        .executive-preview-section { margin-top: 24px; padding: 24px 0 0; border-top: 1px solid #e8e3d8; background: transparent; border-radius: 0; }
        .executive-preview-text { color: #263b32; font: 400 14px/1.85 "DM Sans", sans-serif; white-space: pre-wrap; overflow-wrap: anywhere; }
        .executive-preview-meta span { margin-bottom: 0; }
        .executive-preview-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr)) 24px repeat(2, minmax(0, 1fr));
            gap: 18px;
            padding: 20px 32px;
            background: #f8f4ea;
            border-top: 1px solid #e8e3d8;
        }
        .executive-preview-meta > div { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
        .executive-preview-meta > div:nth-child(3) { grid-column: 4; position: relative; }
        .executive-preview-meta > div:nth-child(3)::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: -30px;
            width: 1px;
            background: #d8d1c2;
        }
        .executive-preview-meta span { color: #7a847f; font: 700 10px "DM Sans", sans-serif; letter-spacing: .06em; }
        .executive-preview-meta strong {
            color: #0d2a21;
            font: 600 12px "DM Sans", sans-serif;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            cursor: default;
        }
        @media (max-width: 760px) {
            .executive-preview-meta { grid-template-columns: 1fr; }
            .executive-preview-meta > div:nth-child(3) { grid-column: auto; margin-top: 6px; padding-top: 12px; }
            .executive-preview-meta > div:nth-child(3)::before {
                left: 0;
                right: 0;
                bottom: auto;
                width: auto;
                height: 1px;
            }
        }
	</style>
</head>
<body data-page-title="<?= e($pageTitle) ?>">
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
						<option value="inactive">Inactive</option>
					</select>
					<select id="officialOrderFilter" class="executive-filter" aria-label="Sort officials">
						<option value="order-asc">Order (low to high)</option>
						<option value="order-desc">Order (high to low)</option>
						<option value="name-asc">Name (A–Z)</option>
						<option value="name-desc">Name (Z–A)</option>
						<option value="position-asc">Position (A–Z)</option>
						<option value="position-desc">Position (Z–A)</option>
						<option value="added-desc">Date added (newest first)</option>
						<option value="added-asc">Date added (oldest first)</option>
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
						<colgroup>
							<col style="width: 12%">
							<col style="width: 24%">
							<col style="width: 24%">
							<col style="width: 10%">
							<col style="width: 12%">
							<col style="width: 18%">
						</colgroup>
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

			<template id="officialRowTemplate">
				<tr data-official-id>
					<td>
						<div class="executive-thumb">
							<span data-field="imagePlaceholder" aria-hidden="true"><i data-lucide="image"></i></span>
							<img data-field="image" alt="" hidden>
						</div>
					</td>
					<td data-field="name"></td>
					<td data-field="position"></td>
					<td data-field="order"></td>
					<td><span class="executive-status" data-field="status"></span></td>
					<td>
						<div class="executive-actions">
							<button type="button" class="executive-action-btn" data-action="preview" title="Preview" aria-label="Preview official"><i data-lucide="eye"></i></button>
							<button type="button" class="executive-action-btn executive-action-edit" data-action="edit" title="Edit" aria-label="Edit official"><i data-lucide="pencil"></i></button>
							<button type="button" class="executive-action-btn executive-action-delete" data-action="delete" title="Delete" aria-label="Delete official"><i data-lucide="trash-2"></i></button>
						</div>
					</td>
				</tr>
			</template>

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
							<p id="officialFormDescription">Add an official profile for the university website.</p>
						</div>
						<button type="button" class="executive-modal-close" data-close-modal aria-label="Close form"><i data-lucide="x"></i></button>
					</div>
					<form id="officialForm" class="executive-form" enctype="multipart/form-data">
						<div class="executive-upload-area">
							<div id="officialUploadInitials" class="executive-upload-placeholder"><i data-lucide="image-plus"></i><strong>Official's Photo</strong></div>
							<img id="officialUploadImage" class="executive-image-preview" alt="Official photo preview" hidden>
							<input id="officialPhoto" name="featured_image" type="file" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" hidden>
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
								<input id="position" name="position" type="text" placeholder="Enter position" required>
							</label>
							<label class="executive-form-field full-width">
								<span>Biography</span>
								<textarea id="bio" name="bio" rows="4" placeholder="Enter biography"></textarea>
							</label>
							<label class="executive-form-field">
								<span>Display Order</span>
								<input id="displayOrder" name="displayOrder" type="number" min="1" value="1" required>
							</label>
							<label class="executive-form-field">
								<span>Status</span>
								<select id="status" name="status">
									<option value="" selected disabled>Select a status</option>
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
						<div><h2 id="officialPreviewHeading">Executive Official Preview</h2></div>
						<button type="button" class="executive-modal-close" data-close-preview aria-label="Close preview"><i data-lucide="x"></i></button>
					</div>
					<div class="executive-preview-scroll">
						<div class="executive-preview-body">
							<div class="executive-preview-top">
								<div class="executive-preview-photo">
									<span id="officialPreviewImagePlaceholder" aria-hidden="true"><i data-lucide="image"></i></span>
									<img id="officialPreviewImage" alt="Official photo" hidden>
								</div>
								<div>
									<h3 class="executive-preview-name" id="officialPreviewName">Official Name</h3>
									<p class="executive-preview-position" id="officialPreviewPosition"></p>
								</div>
							</div>
							<div class="executive-preview-section">
								<p id="officialPreviewBio" class="executive-preview-text"></p>
							</div>
						</div>
					</div>
					<footer class="executive-preview-meta">
				    	<div><span>CREATED BY</span><strong id="previewMetadataCreatedBy">-</strong></div>
                        <div><span>CREATED AT</span><strong id="previewMetadataDateCreated">-</strong></div>
                        <div><span>UPDATED BY</span><strong id="previewMetadataLastUpdatedBy">-</strong></div>
                        <div><span>UPDATED AT</span><strong id="previewMetadataExpiry">-</strong></div>
					</footer>
				</article>
			</div>
			<div class="ui-toast" id="uiToast" role="status" aria-live="polite"></div>
		</section>
	</main>
</div>
</body>
</html>
