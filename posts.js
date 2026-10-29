import { formatDate, formatTimestamp, showAlert, showConfirm, showUnexpectedError } from "./common.js";

window.lucide?.createIcons();

// Get from snapshot
const form = document.getElementById('form');
const imagePreview = document.getElementById('imagePreview');

function getFormSnapshot() {
    return JSON.stringify({
        fields: [...form.elements]
            .filter(element => element.id !== 'featuredImage')
            .map(element => element.value),
        imagePreview: imagePreview?.src ?? ''
    });
}

let originalForm = getFormSnapshot();
const isDirty = () => getFormSnapshot() !== originalForm;

// Check page change
let allowNavigationWithoutPrompt = false;

window.addEventListener("beforeunload", event => {
    if (!isDirty() || allowNavigationWithoutPrompt) return;

    event.preventDefault();
});

// Open modal
const modal = document.getElementById('modal');
let postId = 0;
let removeImage = false;
let currentPostMetadata = null;

document.querySelector('button[data-action="add"]').addEventListener('click', () => {
    form.reset();
    postId = 0;
    currentPostMetadata = null;
    removeImage = false;
    clearImage();
    originalForm = getFormSnapshot();
    modal.setAttribute('aria-hidden', 'false');
    modal.classList.add('open');
});

// Set current post for editing
function setCurrentPost(post) {
    currentPostMetadata = post;
    postId = Number(post.id);
    removeImage = false;
    clearImage();

    if (post.featured_image) {
        showImage(getFeaturedImageUrl(post.featured_image));
    }
}

function getFeaturedImageUrl(filename) {
    return `../../storage/uploads/${encodeURIComponent(filename)}`;
}

// Close form
async function closeForm() {
    if (isDirty()) {
        const discardChanges = await showConfirm(
            'warning',
            'Discard changes?',
            'Your unsaved changes will be lost.',
            'Discard changes',
            'Keep editing'
        );

        if (!discardChanges) return;
    }

    form.reset();
    postId = 0;
    currentPostMetadata = null;
    removeImage = false;
    clearImage();
    document.getElementById('postFormTitle').textContent = 'Add New Post';
    document.getElementById('postFormDescription').textContent = 'Create a post for the university website.';

    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    originalForm = getFormSnapshot();
}

document.querySelector('button[data-action="closeModal"]').addEventListener('click', async () => {
    await closeForm();
});

document.querySelector('button[data-action="cancel"]').addEventListener('click', async () => {
    await closeForm();
});

// Show image
const imageInput = document.querySelector('input[name="featured_image"]');
const imagePlaceholder = document.getElementById('uploadPlaceholder');
const uploadImageBtn = document.querySelector('button[data-action="uploadFeaturedImage"]');
const removeImageBtn = document.querySelector('button[data-action="removeFeaturedImage"]');
let imageObjectUrl = null;

function showImage(src) {
    if (imageObjectUrl) {
        URL.revokeObjectURL(imageObjectUrl);
    }

    imageObjectUrl = src.startsWith('blob:') ? src : null;

    imagePreview.src = src;
    imagePreview.hidden = false;
    imagePreview.style.display = 'block';
    imagePlaceholder.hidden = true;
    removeImageBtn.hidden = false;
}

uploadImageBtn.addEventListener('click', () => {
    imageInput.click();
});

imageInput.addEventListener('change', () => {
    const file = imageInput.files?.[0];

    if (!file) return;

    if (!file.type.startsWith('image/')) {
        clearImage();
        return;
    }

    removeImage = false;
    showImage(URL.createObjectURL(file));
});

// Clear image
function clearImage() {
    if (imageObjectUrl) {
        URL.revokeObjectURL(imageObjectUrl);
    }

    imageObjectUrl = null;

    imageInput.value = '';
    imagePreview.removeAttribute('src');
    imagePreview.hidden = true;
    imagePreview.style.display = 'none';
    imagePlaceholder.hidden = false;
    removeImageBtn.hidden = true;
}

removeImageBtn.addEventListener('click', () => {
    const hasSavedImage = postId > 0
        && Boolean(currentPostMetadata?.featured_image);

    removeImage = hasSavedImage;
    clearImage();
});

// Load categories
const categorySelect = document.querySelector('select[name="category"]');

async function loadCategories() {
    categorySelect.disabled = true;
    categorySelect.replaceChildren(new Option('Loading categories…', ''));

    try {
        const response = await fetch('../../api/categories/by-class.php?class=university-posts', {});
        const result = await response.json();

        const placeholder = new Option(
            result.data.length > 0 ? 'Select a category' : 'No categories available', '', true, true
        );

        placeholder.disabled = true;
        categorySelect.replaceChildren(placeholder);

        result.data.forEach(category => {
            categorySelect.add(new Option(category.name, String(category.id)));
        });

        categorySelect.disabled = categorySelect.options.length === 1;
        await loadPosts();
    } catch (error) {
        categorySelect.replaceChildren(new Option('Unable to load categories', ''));
        categorySelect.disabled = true;
        await showUnexpectedError(error);
    }
}

loadCategories();

// Show preview
const previewModal = document.getElementById('postPreviewModal');
const previewTitle = document.getElementById('previewTitle');
const previewExcerpt = document.getElementById('previewExcerpt');
const previewContent = document.getElementById('previewContent');
const previewImage = document.getElementById('previewImage');
const previewImageWrap = document.getElementById('previewImageWrap');
const previewMetadataCreatedBy = document.getElementById('previewMetadataCreatedBy');
const previewMetadataDateCreated = document.getElementById('previewMetadataDateCreated');
const previewMetadataLastUpdatedBy = document.getElementById('previewMetadataLastUpdatedBy');
const previewMetadataUpdatedAt = document.getElementById('previewMetadataExpiry');

function showPreviewMetadata(id = postId, post = currentPostMetadata) {
    if (id === 0) {
        previewMetadataCreatedBy.textContent = '-';
        previewMetadataDateCreated.textContent = '-';
        previewMetadataLastUpdatedBy.textContent = '—';
        previewMetadataUpdatedAt.textContent = '—';
        return;
    }

    previewMetadataCreatedBy.textContent = post?.author_username || '—';
    previewMetadataDateCreated.textContent = post?.created_at ? formatTimestamp(post.created_at) : '—';
    previewMetadataLastUpdatedBy.textContent = post?.editor_username || '—';
    previewMetadataUpdatedAt.textContent = post?.updated_at ? formatTimestamp(post.updated_at) : '—';
}

function openPreviewModal() {
    previewModal.classList.add('open');
    previewModal.setAttribute('aria-hidden', 'false');
}

function setPreviewImage(src) {
    if (src) {
        previewImage.src = src;
        previewImage.hidden = false;
        previewImageWrap.hidden = false;
        return;
    }

    previewImage.removeAttribute('src');
    previewImage.hidden = true;
    previewImageWrap.hidden = true;
}

function showPreview() {
    previewTitle.textContent = document.getElementById('postTitle').value.trim() || 'No title';
    previewExcerpt.textContent = document.getElementById('postExcerpt').value.trim() || 'No excerpt';
    previewContent.textContent = document.getElementById('postContent').value.trim() || 'No content';
    const imageSrc = imagePreview.getAttribute('src');
    setPreviewImage(imageSrc && !imagePreview.hidden ? imageSrc : '');
    showPreviewMetadata();
    openPreviewModal();
}

// Fetch post item
async function fetchPostItem(id) {
    const response = await fetch(`../../api/posts/item.php?id=${encodeURIComponent(id)}`);
    const result = await response.json();

    if (!response.ok) {
        if (Object.hasOwn(result.data ?? {}, 'redirect')) {
            allowNavigationWithoutPrompt = true;
            window.location.href = result.data.redirect;
            return null;
        }

        await showAlert(false, result.message || 'Unable to load this post.');
        return null;
    }

    return result.data.post;
}

// Show saved item
async function showSavedItem(id) {
    try {
        const post = await fetchPostItem(id);
        if (!post) return;

        previewTitle.textContent = post.title || 'No title';
        previewExcerpt.textContent = post.excerpt || 'No excerpt';
        previewContent.textContent = post.content || 'No content';
        setPreviewImage(post.featured_image ? getFeaturedImageUrl(post.featured_image) : '');
        showPreviewMetadata(Number(post.id), post);
        openPreviewModal();
    } catch (error) {
        await showUnexpectedError(error);
    }
}

// Edit saved item
async function editSavedItem(id) {
    try {
        const post = await fetchPostItem(id);
        if (!post) return;

        form.reset();
        form.elements.namedItem('title').value = post.title ?? '';
        form.elements.namedItem('excerpt').value = post.excerpt ?? '';
        form.elements.namedItem('category').value = String(post.category_id ?? '');
        form.elements.namedItem('status').value = post.status ?? '';
        form.elements.namedItem('publish_date').value = String(post.published_at ?? '').slice(0, 10);
        form.elements.namedItem('expiration_date').value = String(post.expires_at ?? '').slice(0, 10);
        form.elements.namedItem('content').value = post.content ?? '';

        setCurrentPost(post);
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        originalForm = getFormSnapshot();
    } catch (error) {
        await showUnexpectedError(error);
    }
}

// Archive saved item
async function archiveSavedItem(id) {
    const confirmed = await showConfirm(
        'warning',
        'Archive this post?',
        `The post will be removed from the list.`,
        'Archive post',
        'Cancel'
    );

    if (!confirmed) return;

    try {
        const formData = new FormData();
        formData.set('id', String(id));
        formData.set('csrf_key', document.querySelector('input[name="csrf_key"]').value);

        const response = await fetch('../../api/posts/archive.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (!response.ok) {
            if (Object.hasOwn(result.data ?? {}, 'redirect')) {
                if (!new URL(result.data.redirect, window.location.href).searchParams.has('error')) {
                    await showAlert(false, result.message);

                    allowNavigationWithoutPrompt = true;
                    window.location.href = result.data.redirect;
                    return;
                }

                allowNavigationWithoutPrompt = true;
                window.location.href = result.data.redirect;
                return;
            }

            await showAlert(false, result.message);
            return;
        }

        await showAlert(true, result.message);
        await loadPosts(1);
    } catch (error) {
        await showUnexpectedError(error);
    }
}

document.querySelector('button[data-action="showPreview"]').addEventListener('click', showPreview);

document.querySelector('button[data-action="closePreview"]').addEventListener('click', () => {
        previewModal.classList.remove('open');
        previewModal.setAttribute('aria-hidden', 'true');
});

// Save
const saveBtn = document.querySelector('button[data-action="save"]');

async function save(formData) {
    saveBtn.disabled = true;

    formData.set('id', String(postId));
    formData.set('remove_featured_image', String(removeImage));

    try {
        const response = await fetch('../../api/posts/save.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (!response.ok) {
            if (Object.hasOwn(result.data ?? {}, 'redirect')) {
                if (!new URL(result.data.redirect, window.location.href).searchParams.has('error')) {
                    await showAlert(false, result.message);

                    allowNavigationWithoutPrompt = true;
                    window.location.href = result.data.redirect;
                    return;
                }

                allowNavigationWithoutPrompt = true;
                window.location.href = result.data.redirect;
                return;
            }

            await showAlert(false, result.message);
            return;
        }

        await showAlert(true, result.message);

        form.reset();
        postId = 0;
        currentPostMetadata = null;
        removeImage = false;
        clearImage();
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        originalForm = getFormSnapshot();
        await loadPosts(1);
    } catch (error) {
        await showUnexpectedError(error);
    } finally {
        saveBtn.disabled = false;
    }
}

form.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (!form.reportValidity()) return;

    await save(new FormData(form));
});

// Filter
const searchInput = document.querySelector('input[name="search"]');
const statusFilter = document.querySelector('select[name="filter"]');
const sortSelect = document.querySelector('select[name="sort"]');
const tableBody = document.getElementById('tableBody');
const rowTemplate = document.getElementById('rowTemplate');
const itemCount = document.getElementById('itemCount');
const pagination = document.getElementById('pagination');
const prevBtn = document.querySelector('button[name="previous"]');
const nextBtn = document.querySelector('button[name="next"]');
const pageNumbers = document.getElementById('pageNum');
const pageTotal = document.getElementById('pageTotal');
let currentPage = 1;
let controller = null;
let timer = null;

function renderPostRow(post) {
    const row = rowTemplate.content.firstElementChild.cloneNode(true);
    row.dataset.postId = String(post.id);

    const image = row.querySelector('[data-field="image"]');
    const imagePlaceholder = row.querySelector('[data-field="imagePlaceholder"]');

    if (post.featured_image) {
        image.addEventListener('load', () => {
            image.hidden = false;
            imagePlaceholder.hidden = true;
        }, { once: true });

        image.addEventListener('error', () => {
            image.hidden = true;
            imagePlaceholder.hidden = false;
        }, { once: true });

        image.src = getFeaturedImageUrl(post.featured_image);
    }

    const title = post.title || '—';
    const category = post.display_category || '—';
    const publishDate = formatDate(post.published_at);
    const expirationDate = formatDate(post.expires_at);

    for (const [field, value] of Object.entries({ title, category, publishDate, expirationDate })) {
        const cell = row.querySelector(`[data-field="${field}"]`);
        cell.textContent = value;
        cell.title = value;
    }

    const badge = row.querySelector('[data-field="status"]');
    const status = String(post.display_status).toLowerCase();
    badge.classList.add(`status-${status}`);
    badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
    badge.title = badge.textContent;

    row.querySelectorAll('[data-action]').forEach(button => {
        button.dataset.postId = String(post.id);
        const actionLabel = {
            preview: 'Preview',
            edit: 'Edit',
            archive: 'Archive'
        }[button.dataset.action];
        button.setAttribute('aria-label', `${actionLabel} ${post.title}`);
    });

    return row;
}

function renderPagination(currentPage, totalPages) {
    pagination.hidden = totalPages <= 1;
    prevBtn.disabled = currentPage <= 1;
    nextBtn.disabled = currentPage >= totalPages;
    pageNumbers.replaceChildren();
    pageTotal.textContent = `${totalPages} ${totalPages === 1 ? 'page' : 'pages'}`;

    const start = Math.max(1, currentPage - 2);
    const end = Math.min(totalPages, currentPage + 2);
    for (let page = start; page <= end; page += 1) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `page-btn${page === currentPage ? ' active' : ''}`;
        button.textContent = String(page);
        button.setAttribute('aria-label', `Page ${page}`);

        if (page === currentPage) button.setAttribute('aria-current', 'page');

        button.addEventListener('click', () => loadPosts(page));
        pageNumbers.append(button);
    }
}

async function loadPosts(page = 1) {
    controller?.abort();
    controller = new AbortController();
    currentPage = page;

    const params = new URLSearchParams({
        search: searchInput.value.trim(),
        filter: statusFilter.value,
        sort: sortSelect.value,
        page: String(page)
    });

    try {
        const response = await fetch(`../../api/posts/filter.php?${params}`, {
            signal: controller.signal
        });
        const result = await response.json();

        if (!response.ok) {
            if (Object.hasOwn(result.data ?? {}, 'redirect')) {
                allowNavigationWithoutPrompt = true;
                window.location.href = result.data.redirect;
                return;
            }

            await showAlert(false, result.message);
        }

        const { items, current_page: returnedPage, total_posts: totalPosts, total_pages: totalPages } = result.data;

        tableBody.replaceChildren();

        if (items.length === 0) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 7;
            cell.className = 'empty-row';
            cell.textContent = 'No posts found.';
            row.append(cell);
            tableBody.append(row);
        } else {
            tableBody.append(...items.map(renderPostRow));
            window.lucide?.createIcons({ root: tableBody });
        }

        itemCount.textContent = `${totalPosts} ${totalPosts === 1 ? 'post' : 'posts'}`;
        renderPagination(Number(returnedPage), Number(totalPages));
        currentPage = Number(returnedPage);
    } catch (error) {
        if (error.name === 'AbortError') return;

        tableBody.replaceChildren();
        const row = document.createElement('tr');
        const cell = document.createElement('td');
        cell.colSpan = 7;
        cell.className = 'empty-row';
        cell.textContent = 'Could not load posts. Please try again.';
        row.append(cell);
        tableBody.append(row);

        await showUnexpectedError(error);
    }
}

searchInput.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(() => loadPosts(1), 300);
});
tableBody.addEventListener('click', async (event) => {
    const previewButton = event.target.closest('button[data-action="preview"]');
    if (previewButton) {
        await showSavedItem(previewButton.dataset.postId);
        return;
    }

    const editButton = event.target.closest('button[data-action="edit"]');
    if (editButton) {
        await editSavedItem(editButton.dataset.postId);
        return
    }

    const archiveButton = event.target.closest('button[data-action="archive"]');
    if (archiveButton) {
        await archiveSavedItem(archiveButton.dataset.postId);
        return;
    }
});
statusFilter.addEventListener('change', () => loadPosts(1));
sortSelect.addEventListener('change', () => loadPosts(1));
prevBtn.addEventListener('click', () => {
    if (currentPage > 1) loadPosts(currentPage - 1);
});
nextBtn.addEventListener('click', () => loadPosts(currentPage + 1));
