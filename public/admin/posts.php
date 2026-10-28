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

$pageTitle = "Posts";
$today = (new DateTimeImmutable('today', new DateTimeZone('Asia/Manila')))->format('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1.0">
	<title>UCC Admin | <?= e($pageTitle) ?> </title>
	<link rel="icon" type="image/png" href="assets/images/ucc-LOGO.png">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Libre+Baskerville:wght@700&display=swap" rel="stylesheet">
	<script src="https://unpkg.com/lucide@latest"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script type="module" src="assets/js/posts.js"></script>
	<link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/post.css">
</head>
<body data-page-title="<?= e($pageTitle) ?>">
    <div class="dashboard-layout">
    	<?php include __DIR__ . '/template/sidebar.php'; ?>
    	<main class="main-content">
    		<?php include __DIR__ . '/template/header.php'; ?>
            <section class="posts-page">
                <div class="posts-intro">
                    <div><p>View, organize, and maintain posts published on the university website.</p></div>
                </div>
                <div class="posts-toolbar">
                    <div class="posts-tools-left">
                        <div class="posts-search">
                            <input type="search" name="search" placeholder="Search posts..." aria-label="Search posts">
                            <i data-lucide="search"></i>
                        </div>
                        <select name="filter" class="posts-filter" aria-label="Filter posts">
                            <option value="all">All posts</option>
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                            <option value="pending">Pending</option>
                            <option value="expired">Expired</option>
                        </select>
                        <select name="sort" class="posts-filter" aria-label="Order posts">
                            <option value="create-desc">Creation date (newest first)</option>
                            <option value="create-asc">Creation date (oldest first)</option>
                            <option value="publish-desc">Publish date (newest first)</option>
                            <option value="publish-asc">Publish date (oldest first)</option>
                            <option value="expiry-asc">Expiration date (soonest first)</option>
                            <option value="expiry-desc">Expiration date (latest first)</option>
                            <option value="title-asc">Title (A–Z)</option>
                            <option value="title-desc">Title (Z–A)</option>
                        </select>
                    </div>
                    <button type="button" data-action="add" class="post-btn post-btn-primary"><i data-lucide="plus"></i> Add New Post</button>
                </div>

                <div class="posts-card">
                    <div class="posts-card-head">
                        <h2>Posts</h2>
                        <span class="posts-count" id="itemCount">0 posts</span>
                    </div>
                    <div class="posts-table-wrap">
                        <table class="posts-table">
                            <thead>
                                <tr>
                                    <th class="col-image">Featured Image</th>
                                    <th class="col-title">Title</th>
                                    <th class="col-category">Category</th>
                                    <th class="col-date">Publish Date</th>
                                    <th class="col-expiry">Expiration Date</th>
                                    <th class="col-status">Status</th>
                                    <th class="col-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="postsTableBody"></tbody>
                        </table>
                    </div>
                </div>

                <nav class="posts-pagination" id="postsPagination" aria-label="Posts pagination" hidden>
                    <button class="page-btn" id="prevPageBtn" type="button" aria-label="Previous page">
                        <i data-lucide="chevron-left"></i>
                    </button>
                    <div class="page-numbers" id="pageNumbers"></div>
                    <button class="page-btn" id="nextPageBtn" type="button" aria-label="Next page">
                        <i data-lucide="chevron-right"></i>
                    </button>
                    <span class="page-total" id="pageTotal">1 page</span>
                </nav>

                <div class="post-modal" id="modal" aria-hidden="true">
                    <div class="post-modal-backdrop" data-close-modal></div>
                    <div class="post-modal-panel" role="dialog" aria-modal="true" aria-labelledby="postFormTitle">
                        <div class="post-modal-head">
                            <div><h2 id="postFormTitle">Add New Post</h2><p id="postFormDescription">Create a post for the university website.</p></div>
                            <button type="button" data-action="closeModal" class="modal-close" aria-label="Close post form"><i data-lucide="x"></i></button>
                        </div>
                        <form class="post-form" id="form">
                            <input type="hidden" name="<?= e(Token::KEY) ?>" value="<?= e($csrfToken) ?>">
                            <div class="image-upload-area">
                                <div class="upload-placeholder" id="uploadPlaceholder"><i data-lucide="image-plus"></i><strong>Featured Image</strong></div>
                                <img id="imagePreview" class="image-preview" alt="Featured image preview" hidden>
                                <input type="file" name="featured_image" id="featuredImage" accept="image/*" hidden>
                                <button type="button" data-action="uploadFeaturedImage" class="post-btn post-btn-ghost" id="uploadImageBtn"><i data-lucide="upload"></i> Choose Image</button>
                                <button type="button" data-action="removeFeaturedImage" class="post-btn post-btn-danger" id="removeImageBtn" hidden><i data-lucide="x"></i> Remove Image</button>
                            </div>
                            <div class="form-grid">
                                <label class="form-field full-width"><span>Post Title</span><input type="text" id="postTitle" placeholder="Enter post title" maxlength="180" required></label>
                                <label class="form-field full-width"><span>Excerpt</span><textarea id="postExcerpt" rows="3" maxlength="320" placeholder="Write a short summary for the post" required></textarea></label>
                                <label><span>Category</span><select id="postCategory" name="category" required>
                                    <option value="" selected disabled>Select a category</option>
                                </select></label>
                                <label><span>Status</span><select name="status">
                                    <option value="" selected disabled>Select a status</option>
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                </select></label>
                                <label class="publish-date-field"><span>Publish Date</span><input type="date" id="postDate" min="<?= e($today) ?>"></label>
                                <label class="publish-date-field"><span>Expiration Date</span><input type="date" id="postExpiryDate" min="<?= e($today) ?>"></label>
                            </div>

                            <label class="content-field"><span>Content</span>
                                <textarea id="postContent" class="content-editor" rows="7" placeholder="Write your post content here..." required></textarea>
                            </label>
                            <div class="form-actions">
                                <button type="button" data-action="cancel" class="post-btn post-btn-ghost form-cancel">Cancel</button>
                                <div class="form-actions-right">
                                    <button type="button" data-action="showPreview" class="post-btn post-btn-ghost" id="formPreviewBtn"><i data-lucide="eye"></i> Preview</button>
                                    <button type="submit" class="post-btn post-btn-primary" id="savePostBtn"><i data-lucide="save"></i> Save Post</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="post-modal preview-modal" id="postPreviewModal" aria-hidden="true">
                    <div class="post-modal-backdrop" data-close-preview></div>
                    <article class="post-modal-panel post-preview-panel" role="dialog" aria-modal="true" aria-labelledby="previewTitle">
                        <div class="post-modal-head"><div>
                            <h2>Post Preview</h2></div>
                            <button type="button" data-action="closePreview" class="modal-close" aria-label="Close preview"><i data-lucide="x"></i></button></div>
                        <div class="public-post-preview">
                            <div class="preview-image-wrap" id="previewImageWrap" hidden><img id="previewImage" alt="Post featured image" hidden></div>
                            <div class="preview-article-body"><h2 id="previewTitle">Post title</h2><p class="preview-excerpt" id="previewExcerpt"></p><div class="preview-content" id="previewContent"></div></div>
                        </div>
                        <footer class="post-preview-metadata">
                            <div class="post-preview-metadata-grid" style="display:flex; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:56px; justify-items:center; text-align:left;">
                                <div><span>CREATED BY</span><strong id="previewMetadataCreatedBy">—</strong></div>
                                <div><span>CREATED AT</span><strong id="previewMetadataDateCreated">—</strong></div>
                                <div><span>UPDATED BY</span><strong id="previewMetadataLastUpdatedBy">—</strong></div>
                                <div><span>UPDATED AT</span><strong id="previewMetadataExpiry">—</strong></div>
                            </div>
                        </footer>
                    </article>
                </div>
                <div class="ui-toast" id="uiToast" role="status" aria-live="polite"></div>
            </section>
        </main>
    </div>
</body>
</html>
