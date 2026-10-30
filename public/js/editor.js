const quill = new Quill('#editor', {
    theme: 'snow'
});

const contentInput = document.querySelector('#content');
const form = document.querySelector('form');

if (contentInput.value !== '') {
    quill.clipboard.dangerouslyPasteHTML(contentInput.value);
}

// For cancel buttons
const cancelButton = document.querySelector('button[value="cancel"]');
let formChanged = false;

form.addEventListener('submit', function () {
    contentInput.value = quill.root.innerHTML;
});

form.addEventListener('input', function () {
    formChanged = true;
});

form.addEventListener('change', function () {
    formChanged = true;
});

quill.on('text-change', function () {
    formChanged = true;
});

cancelButton.addEventListener('click', function (event) {
    if (!formChanged) {
        return;
    }

    event.preventDefault();

    Swal.fire({
        title: 'Cancel editing?',
        text: 'Your unsaved changes will be lost.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, cancel',
        cancelButtonText: 'Keep editing'
    }).then(function (result) {
        if (result.isConfirmed) {
            const action = document.createElement('input');

            action.type = 'hidden';
            action.name = 'action';
            action.value = 'cancel';

            form.appendChild(action);
            form.submit();
        }
    });
});
