<?php
$pageTitle = "User Management";
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
	<link rel="stylesheet" href="assets/css/users.css">
</head>
<body data-page-title="<?= htmlspecialchars($pageTitle) ?>">
<div class="dashboard-layout">
	<?php include __DIR__ . '/template/sidebar.php'; ?>
	<main class="main-content">
		<?php include __DIR__ . '/template/header.php'; ?>
        
            <section class="users-page">
                <div class="users-intro">
                    <div><p>Manage administrative users and their access levels.</p></div>
                </div>

                <div class="users-toolbar">
                    <div class="users-tools-left">
                        <div class="users-search">
                            <input type="search" id="userSearch" placeholder="Search users..." aria-label="Search users">
                            <i data-lucide="search"></i>
                        </div>
                    </div>
                    <button type="button" id="addUserBtn" class="user-btn user-btn-primary"><i data-lucide="plus"></i> Add New User</button>
                </div>

                <div class="users-card">
                    <div class="users-card-head">
                        <h2>Users</h2>
                        <span id="userCount" class="users-count">0 users</span>
                    </div>
                    <div class="users-table-wrap">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Last Login</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="userTableBody"></tbody>
                        </table>
                    </div>
                    <div id="userEmptyState" class="user-empty" hidden>
                        <i data-lucide="users"></i>
                        <h3>No users found</h3>
                        <p>Try a different search term.</p>
                    </div>
                </div>

                <nav class="users-pagination" id="usersPagination" aria-label="Users pagination" hidden>
                    <button class="user-page-btn" id="usersPrevPage" type="button" aria-label="Previous page">
                        <i data-lucide="chevron-left"></i>
                    </button>
                    <div class="user-page-numbers" id="usersPageNumbers"></div>
                    <button class="user-page-btn" id="usersNextPage" type="button" aria-label="Next page">
                        <i data-lucide="chevron-right"></i>
                    </button>
                    <span class="user-page-total" id="usersPageTotal">1 page</span>
                </nav>

                <div id="userFormModal" class="user-modal" aria-hidden="true">
                    <div class="user-modal-backdrop" data-close-modal></div>
                    <div class="user-modal-panel" role="dialog" aria-modal="true" aria-labelledby="userFormTitle">
                        <div class="user-modal-head">
                            <div><h2 id="userFormTitle">Add New User</h2><p id="userFormDescription">Create a new administrative account.</p></div>
                            <button type="button" class="user-modal-close" data-close-modal aria-label="Close form"><i data-lucide="x"></i></button>
                        </div>
                        <form id="userForm" class="user-form">
                            <input type="hidden" id="userId">

                            <h3 class="user-profile-heading">User profile</h3>
                            <div class="user-form-grid">
                                <label class="user-form-field full-width"><span>Name</span>
                                    <input type="text" id="username" name="username" placeholder="Enter name" pattern="[A-Za-z0-9_.\-]{3,30}" title="3 to 30 characters: letters, numbers, dots, dashes, or underscores" required>
                                    <small class="user-field-hint">Used to sign in to the admin panel. Letters, numbers, dots, dashes, and underscores only.</small>
                                </label>
                                <label class="user-form-field full-width"><span>Email</span>
                                    <input type="email" id="email" name="email" placeholder="Enter email address" required>
                                    <small class="user-field-hint">Used for account notices and password recovery.</small>
                                </label>
                                <label class="user-form-field"><span>Role</span>
                                    <select id="role" name="role" required>
                                        <option value="admin">Administrator</option>
                                        <option value="editor">Editor</option>
                                    </select>
                                    <small class="user-field-hint">Controls what this user can access.</small>
                                </label>
                                <label class="user-form-field"><span>Status</span>
                                    <select id="status" name="status" required>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                    <small class="user-field-hint">Inactive users cannot sign in.</small>
                                </label>
                                <label class="user-form-field full-width"><span>Password</span>
                                    <input type="password" id="password" name="password" placeholder="Enter password" autocomplete="new-password">
                                    <small class="user-field-hint" id="passwordHint">Use at least 8 characters.</small>
                                </label>
                            </div>

                            <div class="user-form-actions">
                                <button type="button" class="user-btn user-btn-ghost" data-close-modal>Cancel</button>
                                <div class="user-form-actions-right">
                                    <button type="button" id="previewUserBtn" class="user-btn user-btn-ghost"><i data-lucide="eye"></i> Preview</button>
                                    <button type="submit" class="user-btn user-btn-primary"><i data-lucide="save"></i> Save User</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- preview -->
                <div id="userPreviewModal" class="user-modal" aria-hidden="true">
                    <div class="user-modal-backdrop" data-close-preview></div>
                    <div class="user-modal-panel" role="dialog" aria-modal="true" aria-labelledby="previewUserName">
                        <div class="user-modal-head">
                            <div>
                                <span class="user-preview-eyebrow">User Details</span>
                                <h2>User Preview</h2>
                                <p id="previewUserNote"></p>
                            </div>
                            <button type="button" class="user-modal-close" data-close-preview aria-label="Close preview"><i data-lucide="x"></i></button>
                        </div>

                        <span id="previewUserAvatar" hidden>U</span>

                        <div class="user-preview-body">
                            <div class="user-preview-title-row">
                                <strong id="previewUserName">—</strong>
                                <span id="previewUserStatus" class="user-status user-status-active">—</span>
                            </div>
                            <p class="user-preview-role" id="previewUserRole">—</p>
                            <div class="user-preview-line">
                                <i data-lucide="mail"></i>
                                <span id="previewUserEmail">—</span>
                            </div>
                        </div>

                        <div class="user-preview-meta">
                            <div><span>LAST LOGIN</span><strong id="previewUserLastLogin">—</strong></div>
                            <div><span>PASSWORD</span><strong id="previewUserPassword">—</strong></div>
                        </div>
                    </div>
                </div>

                <div class="user-toast" id="usersToast" role="status" aria-live="polite"></div>
            </section>
		</main>
	</div>

	<script src="assets/js/shared-layout.js"></script>
	<script src="assets/js/users.js"></script>

</body>
</html>