import { showResult, showUnexpectedError } from "./helper.js";

const form = document.querySelector('#form');
const message = document.querySelector('#message');
const loginBtn = form.elements.namedItem('login_btn');
const password = form.elements.namedItem('password');

window.addEventListener('beforeunload', (event) => {
    const fields = new FormData(form);
    fields.delete('csrf_token');

    const dirty = Array.from(fields.values()).some((value) => value !== '');

    if (dirty) {
        event.preventDefault();
    }
});

async function login() {
    const fields = new FormData(form);
    message.textContent = '';
    loginBtn.disabled = true;

    let retry = false;

    try {
        const response = await fetch('/api/user/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Csrf-Token': fields.get('csrf_token')
            },
            body: JSON.stringify({
                username: fields.get('username'),
                password: fields.get('password'),
            }),
        });

        const { result, redo_password: redoPassword, redirect } = await response.json();

        if (!result.success) {
            if (redoPassword === true) {
                password.value = '';
            }

            await showResult(result, 'OK');
            return;
        }

        await showResult(result, 'OK', 5000);

        if (typeof redirect === 'string') {
            form.reset();
            window.location.href = redirect;
        }
    } catch {
        retry = await showUnexpectedError();
    } finally {
        loginBtn.disabled = false;
    }

    if (retry) {
        form.requestSubmit();
    }
}

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    login();
});
