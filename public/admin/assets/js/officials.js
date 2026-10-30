import { formatTimestamp, showConfirm } from './common.js';

window.lucide?.createIcons();

// Get form snapshot
const form = document.getElementById('officialForm');
const imagePreview = document.getElementById('officialUploadImage');

function getFormSnapshot() {
    return JSON.stringify({
        fields: [...form.elements]
            .filter(element => element.id !== 'officialPhoto')
            .map(element => element.value),
        imagePreview: imagePreview?.src ?? ''
    });
}

let originalForm = getFormSnapshot();
const isDirty = () => getFormSnapshot() !== originalForm;

// Open modal
const modal = document.getElementById('officialModal');
const officialFormTitle = document.getElementById('modalTitle');
const officialFormDescription = document.getElementById('officialFormDescription');
let officialId = 0;
let removeImage = false;
let currentOfficial = null;

function updateFormHeading() {
    const isNewOfficial = officialId === 0;
    officialFormTitle.textContent = isNewOfficial ? 'Add Official' : 'Edit Official';
    officialFormDescription.textContent = isNewOfficial
        ? 'Add an official profile for the university website.'
        : 'Update this official profile for the university website.';
}

document.getElementById('addOfficialBtn').addEventListener('click', () => {
    form.reset();
    officialId = 0;
    updateFormHeading();
    currentOfficial = null;
    removeImage = false;
    clearImage();
    originalForm = getFormSnapshot();
    modal.setAttribute('aria-hidden', 'false');
    modal.classList.add('open');
});

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
    officialId = 0;
    currentOfficial = null;
    removeImage = false;
    clearImage();

    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    originalForm = getFormSnapshot();
}

modal.querySelector('button[data-close-modal]').addEventListener('click', async () => {
    await closeForm();
});

document.getElementById('cancelBtn').addEventListener('click', async () => {
    await closeForm();
});

// Show image
const imageInput = document.getElementById('officialPhoto');
const imagePlaceholder = document.getElementById('officialUploadInitials');
const uploadImageBtn = document.getElementById('chooseOfficialPhotoBtn');
const removeImageBtn = document.getElementById('removeOfficialPhoto');
let imageObjectUrl = null;

function showImage(src) {
    if (imageObjectUrl) {
        URL.revokeObjectURL(imageObjectUrl);
    }

    imageObjectUrl = src.startsWith('blob:') ? src : null;
    imagePreview.alt = `${document.getElementById('name').value.trim()} photo`;
    imagePreview.src = src;
    imagePreview.onerror = () => {
        imagePreview.alt = 'Could not load the image';
        imagePreview.onerror = null;
    };
    imagePreview.onload = () => { imagePreview.onerror = null; };
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

    if (!['image/png', 'image/jpeg', 'image/webp'].includes(file.type)) {
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
    const hasSavedImage = officialId > 0 && Boolean(currentOfficial?.featured_image);
    removeImage = hasSavedImage;
    clearImage();
});

// Show preview
const previewModal = document.getElementById('officialPreviewModal');
const previewName = document.getElementById('officialPreviewName');
const previewPosition = document.getElementById('officialPreviewPosition');
const previewBio = document.getElementById('officialPreviewBio');
const previewImage = document.getElementById('officialPreviewImage');
const previewImagePlaceholder = document.getElementById('officialPreviewImagePlaceholder');
const previewMetadataCreatedBy = document.getElementById('previewMetadataCreatedBy');
const previewMetadataDateCreated = document.getElementById('previewMetadataDateCreated');
const previewMetadataLastUpdatedBy = document.getElementById('previewMetadataLastUpdatedBy');
const previewMetadataUpdatedAt = document.getElementById('previewMetadataExpiry');

function showPreviewMetadata(id = officialId, official = currentOfficial) {
    const values = [
        [previewMetadataCreatedBy, id === 0 ? '-' : official?.author_username || '-'],
        [previewMetadataDateCreated, id === 0 ? '-' : official?.created_at ? formatTimestamp(official.created_at) : '-'],
        [previewMetadataLastUpdatedBy, id === 0 ? '-' : official?.editor_username || '-'],
        [previewMetadataUpdatedAt, id === 0 ? '-' : official?.updated_at ? formatTimestamp(official.updated_at) : '-']
    ];

    for (const [element, value] of values) {
        element.textContent = value;
        element.title = value;
    }
}

function openPreviewModal() {
    previewModal.classList.add('open');
    previewModal.setAttribute('aria-hidden', 'false');
}

function setPreviewImage(src) {
    previewImage.onerror = null;
    if (src) {
        previewImage.alt = "Officials' photo";
        previewImage.src = src;
        previewImage.onerror = () => {
            previewImage.alt = 'Could not load the image';
            previewImage.hidden = true;
            previewImagePlaceholder.hidden = false;
            previewImage.onerror = null;
        };
        previewImage.hidden = false;
        previewImagePlaceholder.hidden = true;
        return;
    }

    previewImage.removeAttribute('src');
    previewImage.hidden = true;
    previewImagePlaceholder.hidden = false;
}

function showPreview() {
    const name = document.getElementById('name').value.trim();
    previewName.textContent = name || 'No name';
    previewPosition.textContent = document.getElementById('position').value.trim() || 'No position';
    previewBio.textContent = document.getElementById('bio').value.trim() || 'No biography';

    const imageSrc = imagePreview.getAttribute('src');
    setPreviewImage(imageSrc && !imagePreview.hidden ? imageSrc : '');
    showPreviewMetadata();
    openPreviewModal();
}

document.getElementById('previewOfficialFormBtn').addEventListener('click', showPreview);

previewModal.querySelector('button[data-close-preview]').addEventListener('click', () => {
    previewModal.classList.remove('open');
    previewModal.setAttribute('aria-hidden', 'true');
});
