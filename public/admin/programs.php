<?php
$pageTitle = "Programs";
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
  <link rel="stylesheet" href="assets/css/program.css">
</head>
<body data-page-title="<?= htmlspecialchars($pageTitle) ?>">
<div class="dashboard-layout">
	<?php include __DIR__ . '/template/sidebar.php'; ?>
	<main class="main-content">
		<?php include __DIR__ . '/template/header.php'; ?>
        
        <section class="programs-page">
            <div class="programs-intro">
                <div>
                    <p>Manage academic programs, review their mission and vision, and control which programs are visible on the website.</p>
                </div>
            </div>

            <div class="programs-toolbar">
                <div class="programs-tools-left">
                    <div class="programs-search">
                        <input type="search" id="programSearch" placeholder="Search programs..." aria-label="Search programs">
                        <i data-lucide="search"></i>
                    </div>

                    <select id="collegeFilter" class="programs-filter" aria-label="Filter by college">
                        <option value="All">All colleges</option>
                        <option>College of Arts and Sciences</option>
                        <option>College of Business and Accountancy</option>
                        <option>College of Education</option>
                        <option>College of Computer Studies</option>
                        <option>College of Engineering</option>
                    </select>

                    <select id="programStatusFilter" class="programs-filter" aria-label="Filter by status">
                        <option value="All">All statuses</option>
                        <option value="Active">Active</option>
                        <option value="Hidden">Hidden</option>
                    </select>

                    <select id="programSort" class="programs-filter" aria-label="Sort programs">
                        <option value="name-asc">Name: A to Z</option>
                        <option value="name-desc">Name: Z to A</option>
                        <option value="newest">Newest added</option>
                        <option value="oldest">Oldest added</option>
                    </select>
                </div>
                <button type="button" id="addProgramBtn" class="program-btn program-btn-primary"><i data-lucide="plus"></i> Add Program</button>
            </div>

            <div class="programs-card">
                <div class="programs-card-head">
                    <h2>Programs</h2>
                    <span id="programCount" class="programs-count">0 programs</span>
                </div>
                <div class="programs-table-wrap">
                    <table class="programs-table">
                        <thead>
                            <tr>
                                <th>Program Name</th>
                                <th>College</th>
                                <th>Mission &amp; Vision</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="programsTableBody"></tbody>
                    </table>
                </div>
                <div id="programsEmpty" class="program-empty" hidden>
                    <i data-lucide="graduation-cap"></i>
                    <h3>No programs found</h3>
                    <p>Try another search term, college, or status filter.</p>
                </div>
            </div>

            <nav class="programs-pagination" id="programsPagination" aria-label="Programs pagination" hidden>
                <button class="program-page-btn" id="programsPrevPage" type="button" aria-label="Previous page">
                    <i data-lucide="chevron-left"></i>
                </button>
                <div class="program-page-numbers" id="programsPageNumbers"></div>
                <button class="program-page-btn" id="programsNextPage" type="button" aria-label="Next page">
                    <i data-lucide="chevron-right"></i>
                </button>
                <span class="program-page-total" id="programsPageTotal">1 page</span>
            </nav>

            <div id="programFormModal" class="program-modal" aria-hidden="true">
                <div class="program-modal-backdrop" data-close-program-form></div>
                <div class="program-modal-panel" role="dialog" aria-modal="true" aria-labelledby="programFormTitle">
                    <div class="program-modal-head">
                        <div>
                            <h2 id="programFormTitle">Add Program</h2>
                            <p>Add or update the program details and publication status.</p>
                        </div>
                        <button type="button" class="program-modal-close" data-close-program-form aria-label="Close program form"><i data-lucide="x"></i></button>
                    </div>
                    <form id="programForm" class="program-form">
                        <div class="program-form-grid">
                            <label class="program-form-field full-width">
                                <span>Unit</span>
                                <select id="programCollege" required>
                                    <option value="">Select a unit</option>
                                    <option>College of Arts and Sciences</option>
                                    <option>College of Business and Accountancy</option>
                                    <option>College of Education</option>
                                    <option>College of Computer Studies</option>
                                    <option>College of Engineering</option>
                                </select>
                            </label>
                            <label class="program-form-field full-width">
                                <span>Program Name</span>
                                <input id="programName" name="programName" type="text" maxlength="180" placeholder="Enter program name" required>
                            </label>
                            <label class="program-form-field full-width">
                                <span>Mission</span>
                                <textarea id="programMission" name="mission" rows="4" placeholder="Enter the program's mission" required></textarea>
                            </label>
                            <label class="program-form-field full-width">
                                <span>Vision</span>
                                <textarea id="programVision" name="vision" rows="4" placeholder="Enter the program's vision" required></textarea>
                            </label>
                            <label class="program-form-field">
                                <span>Status</span>
                                <select id="programStatus" name="status">
                                    <option value="Active">Active</option>
                                    <option value="Hidden">Hidden</option>
                                </select>
                            </label>
                        </div>
                        <div class="program-form-actions">
                            <button type="button" id="cancelProgramBtn" class="program-btn program-btn-ghost">Cancel</button>
                            <div class="program-form-actions-right">
                                <button type="button" id="previewProgramBtn" class="program-btn program-btn-ghost"><i data-lucide="eye"></i> Preview</button>
                                <button type="submit" class="program-btn program-btn-primary"><i data-lucide="save"></i> Save Program</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div id="programPreviewModal" class="program-modal" aria-hidden="true">
                <div class="program-modal-backdrop" data-close-program-preview></div>
                <article class="program-modal-panel program-preview-panel" role="dialog" aria-modal="true" aria-labelledby="programPreviewTitle">
                    <div class="program-modal-head">
                        <div><h2 id="programPreviewTitle">Program Preview</h2></div>
                        <button type="button" class="program-modal-close" data-close-program-preview aria-label="Close preview"><i data-lucide="x"></i></button>
                    </div>
                    <div class="program-preview-body">
                        <p class="program-preview-college" id="previewProgramCollege">College Name</p>
                        <h3 class="program-preview-name" id="previewProgramName">Program Name</h3>
                        <span id="previewProgramStatus" class="program-status program-status-active">Active</span>

                        <div class="program-preview-section">
                            <p class="program-preview-label">Mission</p>
                            <p id="previewProgramMission" class="program-preview-text"></p>
                        </div>
                        <div class="program-preview-section">
                            <p class="program-preview-label">Vision</p>
                            <p id="previewProgramVision" class="program-preview-text"></p>
                        </div>
                    </div>
                    <footer class="program-preview-meta">
                        <div><span>Created by</span><strong id="programCreatedBy">Admin</strong></div>
                        <div><span>Date created</span><strong id="programDateCreated">—</strong></div>
                        <div><span>Last updated by</span><strong id="programLastUpdatedBy">—</strong></div>
                    </footer>
                </article>
            </div>
            <div class="program-toast" id="programsToast" role="status" aria-live="polite"></div>
        </section>
    </main>
</div>

<script src="assets/js/shared-layout.js"></script>
<script src="assets/js/programs.js"></script>
</body>
</html>