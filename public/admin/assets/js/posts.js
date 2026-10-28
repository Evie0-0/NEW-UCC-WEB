import { formatTimestamp, showAlert, showConfirm, showUnexpectedError } from "./common.js";

if (window.lucide) {
    window.lucide.createIcons();
}

// Get form snapshot
const form = document.getElementById('form');
const imagePreview = document.getElementById('imagePreview');
const csrfToken = document.querySelector('input[name="csrf_key"]')?.value ?? '';

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

// Check before unload
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
    postId = 0;
    currentPostMetadata = null;
    removeImage = false;
    clearImage();
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
        showImage(post.featured_image);
    }
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

function showPreviewMetadata() {
    if (postId === 0) {
        previewMetadataCreatedBy.textContent = '-';
        previewMetadataDateCreated.textContent = '-';
        previewMetadataLastUpdatedBy.textContent = '—';
        previewMetadataUpdatedAt.textContent = '—';
        return;
    }

    const post = currentPostMetadata;
    previewMetadataCreatedBy.textContent = post?.author_username || '—';
    previewMetadataDateCreated.textContent = post?.created_at ? formatTimestamp(post.created_at) : '—';
    previewMetadataLastUpdatedBy.textContent = post?.editor_username || '—';
    previewMetadataUpdatedAt.textContent = post?.updated_at ? formatTimestamp(post.updated_at) : '—';
}

function showPreview() {
    previewTitle.textContent = document.getElementById('postTitle').value.trim() || 'No title';
    previewExcerpt.textContent = document.getElementById('postExcerpt').value.trim() || 'No excerpt';
    previewContent.textContent = document.getElementById('postContent').value.trim() || 'No content';
    showPreviewMetadata();

    const imageSrc = imagePreview.getAttribute('src');

    if (imageSrc && !imagePreview.hidden) {
        previewImage.src = imageSrc;
        previewImage.hidden = false;
        previewImageWrap.hidden = false;
    } else {
        previewImage.removeAttribute('src');
        previewImage.hidden = true;
        previewImageWrap.hidden = true;
    }

    previewModal.classList.add('open');
    previewModal.setAttribute('aria-hidden', 'false');
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
