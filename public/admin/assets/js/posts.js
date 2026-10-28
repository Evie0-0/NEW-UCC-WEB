import { showConfirm } from "./common.js";

if (window.lucide) {
    window.lucide.createIcons();
}

// Get form snapshot
const form = document.getElementById('form');
const featuredImagePreview = document.getElementById('featuredImagePreview');

function getFormSnapshot() {
    return JSON.stringify({
        fields: [...form.elements]
            .filter(element => element.id !== 'featuredImage')
            .map(element => element.value),
        featuredImagePreview: featuredImagePreview?.src ?? ''
    });
}

let originalForm = getFormSnapshot();
const isDirty = () => getFormSnapshot() !== originalForm;

// Check before unload
window.addEventListener("beforeunload", event => {
    if (!isDirty()) return;

    event.preventDefault();
    event.returnValue = '';
});

// Open modal
const modal = document.getElementById('modal');
const featuredImageInput = document.getElementById('featuredImage');
const uploadPlaceholder = document.getElementById('uploadPlaceholder');
const removeImageButton = document.getElementById('removeImageBtn');

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
    if (featuredImageInput) featuredImageInput.value = '';
    if (featuredImagePreview) {
        featuredImagePreview.removeAttribute('src');
        featuredImagePreview.hidden = true;
    }
    if (uploadPlaceholder) uploadPlaceholder.hidden = false;
    if (removeImageButton) removeImageButton.hidden = true;

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
