import { showAlert, showLoading, showUnexpectedError } from "./common.js";

if (window.lucide) {
    window.lucide.createIcons();
}

// Logout
const csrfKeyInput = document.querySelector('input[name="csrf_key"]');

async function logout() {
    try {
        const formData = new FormData();
        formData.append('csrf_key', csrfKeyInput.value);

        const response = await fetch('../../api/users/logout.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (!response.ok) {
            await showAlert(false, result.message);
            return;
        }

        window.location.href = result.data.redirect;
    } catch (error) {
        await showUnexpectedError(error);
    }
}

const backgroundPage = document.getElementById("backgroundPage");
const confirmLogout = document.getElementById("confirmLogout");
const cancelLogout = document.getElementById("cancelLogout");
const referrer = document.referrer ? new URL(document.referrer) : null;
const returnPage = referrer
    && referrer.origin === window.location.origin
    && referrer.pathname.split("/").pop().toLowerCase() !== "logout.php"
    ? referrer.href
    : 'index.php';

if (backgroundPage) {
    backgroundPage.src = returnPage;
}

cancelLogout.addEventListener("click", () => {
	window.location.href = returnPage;
});

confirmLogout.addEventListener("click", async () => {
    await logout();
});
