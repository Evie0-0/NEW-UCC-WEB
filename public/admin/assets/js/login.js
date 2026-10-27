import { showAlert, showUnexpectedError } from "./common.js";

lucide.createIcons();

// Auto select when page loads
const usernameInput = document.querySelector('[name="username"]');

usernameInput.select();

// Login
const loginForm = document.getElementById('loginForm');
const passwordInput = document.querySelector('[name="password"]');

function resetForm() {
    usernameInput.value = '';
    passwordInput.value = '';
}

async function login(formData) {
    try {
        const response = await fetch('../../api/users/login.php', {
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
    }
}

loginForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    const formData = new FormData(loginForm);
    await login(formData);
})
