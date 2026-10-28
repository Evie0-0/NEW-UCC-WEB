import { showAlert, showUnexpectedError } from "./common.js";

if (window.lucide) {
    window.lucide.createIcons();
}

// Unauthenticated or unauthorized error handling
async function userErrorHandler() {
    const params = new URLSearchParams(window.location.search);
    const errorValue = params.get('error');

    const errorMessages = {
        401: 'Plese log in to your account to continue.',
        403: 'Your account does not have permission to access this page. Please use an authorized account or contact your administrator.'
    };

    const errorMessage = errorMessages[errorValue];

    if (errorMessage) {
        await showAlert(false, errorMessage);
        history.replaceState(null, '', window.location.pathname);
    }
}

// Auto select when page loads
document.querySelector('input[name="username"]').select();

// Login
const form = document.getElementById('loginForm');
const usernameInput = document.querySelector('input[name="username"]');
const passwordInput = document.querySelector('input[name="password"]');
const loginBtn = document.querySelector('button[data-action="login"]');

function resetForm() {
    usernameInput.value = '';
    passwordInput.value = '';
}

async function login(formData) {

    loginBtn.disabled = true;

    try {
        const response = await fetch(`../../api/users/login.php`, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (!response.ok) {
            if (Object.hasOwn(result.data, 'redo_password')) {
                passwordInput.value = '';
            }

            await showAlert(false, result.message);
            return;
        }

        await showAlert(true, result.message);
        resetForm();

        window.location.href = result.data.redirect;
    } catch (error) {
        await showUnexpectedError(error);
    } finally {
        loginBtn.disabled = false;
    }
}

form.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (!form.reportValidity()) return;

    await login(new FormData(form));
});

await userErrorHandler();
