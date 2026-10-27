if (window.lucide) {
    window.lucide.createIcons();
}

const backgroundPage = document.getElementById("backgroundPage");
const logoutOverlay = document.getElementById("logoutOverlay");
const logoutSuccess = document.getElementById("logoutSuccess");
const confirmLogout = document.getElementById("confirmLogout");
const cancelLogout = document.getElementById("cancelLogout");
const currentPage = document.referrer;

if (currentPage && !currentPage.endsWith("/logout.php")) {
	backgroundPage.src = currentPage;
} else {
	backgroundPage.src = "index.php";
}

cancelLogout.addEventListener("click", () => {
	window.location.href = "index.php";
});

confirmLogout.addEventListener("click", () => {
	logoutOverlay.style.display = "none";
    logoutSuccess.classList.add("show");

	fetch("Logout.php?action=logout", { credentials: "same-origin" })
		.catch(() => {})
		.finally(() => {
			setTimeout(() => {
				window.location.href = "login.php";
			}, 1200);
		});
});
