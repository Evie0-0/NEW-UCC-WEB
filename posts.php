<?php
$pageTitle = "Posts";
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1.0">
	<title>UCC Admin | <?= htmlspecialchars($pageTitle) ?> </title>
	<link rel="icon" type="image/png" href="images/ucc-LOGO.png">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Libre+Baskerville:wght@700&display=swap" rel="stylesheet">
	<script src="https://unpkg.com/lucide@latest"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/post.css">


</head>
<body data-page-title="<?= htmlspecialchars($pageTitle) ?>">
<div class="dashboard-layout">
	<?php include __DIR__ . '/template/sidebar.php'; ?>
	<main class="main-content">
		<?php include __DIR__ . '/template/header.php'; ?>

        </header>

            <section class="posts-page">
                <div class="posts-intro">
                    <div><p>View, organize, and maintain posts published on the university website.</p></div>
                </div>
                <div class="posts-toolbar">
                    <div class="posts-tools-left">
                        <div class="posts-search">
                            <input type="search" id="postSearch" placeholder="Search posts..." aria-label="Search posts">
                            <i data-lucide="search"></i>
                        </div>
                        <select id="postFilter" class="posts-filter" aria-label="Filter posts">
                            <option value="All">All statuses</option>
                            <option value="Published">Published</option>
                            <option value="Draft">Draft</option>
                            <option value="Pending">Pending</option>
                            <option value="Archived">Archived</option>
                            <option value="Expired">Expired</option>
                        </select>
                        <select id="postSort" class="posts-filter" aria-label="Order posts">
                            <option value="created-desc">Newest created</option>
                            <option value="created-asc">Oldest created</option>
                            <option value="date-desc">Publish date: newest</option>
                            <option value="date-asc">Publish date: oldest</option>
                            <option value="expiry-asc">Expiry date: soonest</option>
                            <option value="expiry-desc">Expiry date: latest</option>
                            <option value="title-asc">Title: A–Z</option>
                            <option value="title-desc">Title: Z–A</option>
                        </select>
                    </div>
                    <button class="post-btn post-btn-primary" id="addPostBtn" type="button"><i data-lucide="plus"></i> Add New Post</button>
                </div>

                <div class="posts-card">
                    <div class="posts-card-head">
                        <h2>Posts</h2>
                        <span class="posts-count" id="postsCount">0 posts</span>
                    </div>
                    <div class="posts-table-wrap">
                        <table class="posts-table">
                            <thead>
                                <tr>
                                    <th class="col-image">Featured Image</th>
                                    <th class="col-title">Title</th>
                                    <th class="col-category">Category</th>
                                    <th class="col-date">Publish Date</th>
                                    <th class="col-expiry">Expiry Date</th>
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

                <div class="post-modal" id="postModal" aria-hidden="true">
                    <div class="post-modal-backdrop" data-close-modal></div>
                    <div class="post-modal-panel" role="dialog" aria-modal="true" aria-labelledby="postFormTitle">
                        <div class="post-modal-head">
                            <div><h2 id="postFormTitle">Add New Post</h2><p id="postFormDescription">Create a post for the university website.</p></div>
                            <button class="modal-close" type="button" data-close-modal aria-label="Close post form"><i data-lucide="x"></i></button>
                        </div>
                        <form class="post-form" id="postForm">
                    
                            <div class="image-upload-area">
                                <div class="upload-placeholder" id="uploadPlaceholder"><i data-lucide="image-plus"></i><strong>Featured Image</strong></div>
                                <img id="imagePreview" class="image-preview" alt="Featured image preview">
                                <input type="file" id="featuredImage" accept="image/*" hidden>
                                <button type="button" class="post-btn post-btn-ghost" id="uploadImageBtn"><i data-lucide="upload"></i> Choose Photo</button>
                                <button type="button" class="post-btn post-btn-danger" id="removeImageBtn" hidden><i data-lucide="x"></i> Remove Photo</button>
                            </div>
                            <div class="form-grid">
                                <label class="form-field full-width"><span>Post Title</span><input type="text" id="postTitle" placeholder="Enter post title" maxlength="180" required></label>
                                <label class="form-field full-width"><span>Excerpt</span><textarea id="postExcerpt" rows="3" maxlength="320" placeholder="Write a short summary for the post" required></textarea></label>
                                <label><span>Category</span><select id="postCategory"><option>News</option><option>Announcement</option><option>Event</option><option>Student Life</option><option>Community Extension Services</option><option>Admission</option><option>Alumni</option><option>Careers</option></select></label>
                                <label><span>Status</span><select id="postStatus"><option>Draft</option><option>Published</option></select></label>
                                <label class="publish-date-field"><span>Publish Date</span><input type="date" id="postDate"></label>
                                <label class="publish-date-field"><span>Expiry Date (optional)</span><input type="date" id="postExpiryDate"></label>
                            </div>
                           
                            <label class="content-field"><span>Content</span>
                                <textarea id="postContent" class="content-editor" rows="7" placeholder="Write your post content here..." required></textarea>
                            </label>
                            <div class="form-actions">
                                <button type="button" class="post-btn post-btn-ghost form-cancel" data-close-modal>Cancel</button>
                                <div class="form-actions-right">
                                    <button type="button" class="post-btn post-btn-ghost" id="formPreviewBtn"><i data-lucide="eye"></i> Preview</button>
                                    <button type="submit" class="post-btn post-btn-primary" id="savePostBtn"><i data-lucide="save"></i> Save Post</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="post-modal preview-modal" id="postPreviewModal" aria-hidden="true">
                    <div class="post-modal-backdrop" data-close-preview></div>
                    <article class="post-modal-panel post-preview-panel" role="dialog" aria-modal="true" aria-labelledby="previewTitle">
                        <div class="post-modal-head"><div><h2>Post Preview</h2></div><button class="modal-close" type="button" data-close-preview aria-label="Close preview"><i data-lucide="x"></i></button></div>
                        <div class="public-post-preview">
                            <div class="preview-image-wrap" id="previewImageWrap"><div class="preview-default-image"><i data-lucide="image"></i><span>Featured image</span></div><img id="previewImage" alt="Post featured image" hidden></div>
                            <div class="preview-article-body"><h2 id="previewTitle">Post title</h2><p class="preview-excerpt" id="previewExcerpt"></p><div class="preview-content" id="previewContent"></div></div>
                        </div>
                        <footer class="post-preview-metadata">
                            <div class="post-preview-metadata-grid" style="display:flex; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:56px; justify-items:center; text-align:left;">
                                <div><span>CREATED BY</span><strong id="previewMetadataCreatedBy">—</strong></div>
                                <div><span>DATE CREATED</span><strong id="previewMetadataDateCreated">—</strong></div>
                                <div><span>LAST UPDATED</span><strong id="previewMetadataLastUpdatedBy">—</strong></div>
                                <div><span>EXPIRY DATE</span><strong id="previewMetadataExpiry">—</strong></div>
                            </div>
                        </footer>
                    </article>
                </div>
                <div class="ui-toast" id="uiToast" role="status" aria-live="polite"></div>
            </section>

</main>
</div>
<script src="assets/js/ucc-common.js"></script>
<script src="assets/js/posts.js"></script>

</body>
</html>