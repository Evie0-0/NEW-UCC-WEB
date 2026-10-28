import { showConfirm } from "./common.js";

if (window.lucide) {
    window.lucide.createIcons();
}

// Get form snapshot
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

// Check before unload
let allowNavigationWithoutPrompt = false;

window.addEventListener("beforeunload", event => {
    if (!isDirty() || allowNavigationWithoutPrompt) return;

    event.preventDefault();
});

// Open modal
const modal = document.getElementById('modal');
document.querySelector('button[data-action="add"]').addEventListener('click', () => {
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
    clearFeaturedImage();

    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    originalForm = getFormSnapshot();
}

const closeModalBtn = document.querySelector('button[data-action="closeModal"]');
closeModalBtn.addEventListener('click', async () => {
    await closeForm();
});

const cancelBtn = document.querySelector('button[data-action="cancel"]');
cancelBtn.addEventListener('click', async () => {
    await closeForm();
});

// Show image
const imageInput = document.querySelector('input[name="featuredImage"]');
const imagePlaceholder = document.getElementById('uploadPlaceholder');
const uploadImageBtn = document.querySelector('button[data-action="uploadFeaturedImage"]');
const removeImageBtn = document.querySelector('button[data-action="removeFeaturedImage"]');
let imageObjectUrl = null;

function showImage(src) {
    if (imageObjectUrl) {
        URL.revokeObjectURL(imageObjectUrl);
    }

    imageObjectUrl = src;

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

    showImage(URL.createObjectURL(file));
});

// clearImage
function clearImage() {
    if (imageObjectUrl?.startsWith('blob:')) {
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

removeImageBtn.addEventListener('click', clearImage);

// Get category
async function getCategories() {

}
