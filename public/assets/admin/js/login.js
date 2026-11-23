import { showResult, showUnexpectedError } from "./helper.js";

const form = document.querySelector('#form');
const message = document.querySelector('#message');
const signinBtn = form.elements.namedItem('sign-in');
const password = form.elements.namedItem('password');

window.addEventListener('beforeunload', (event) => {
    const fields = new FormData(form);
    fields.delete('csrf_token');

    const dirty = Array.from(fields.values()).some((value) => value !== '');

    if (dirty) {
        event.preventDefault();
    }
});

async function signin() {
    const fields = new FormData(form);
    message.textContent = '';
    signinBtn.disabled = true;

    let retry = false;

    try {
        const response = await fetch('/api/user/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Csrf-Token': fields.get('csrf_token')
            },
            body: JSON.stringify({
                username: fields.get('username'),
                password: fields.get('password'),
            }),
        });

        const { result, additional } = await response.json();

        if (!result.success) {
            if (additional.redo_password != null) {
                password.value = '';
            }

            await showResult(result, 'OK');
            return;
        }

        await showResult(result, 'OK', 5000);

        if (additional.redirect != null) {
            form.reset();
            window.location.href = additional.redirect;
        }
    } catch {
        retry = await showUnexpectedError();
    } finally {
        signinBtn.disabled = false;
    }

    if (retry) {
        form.requestSubmit();
    }
}

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    signin();
});
