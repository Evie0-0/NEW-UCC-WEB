<?php
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
	session_start();
	$_SESSION = [];
	if (ini_get('session.use_cookies')) {
		$p = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
	}
	session_destroy();
	http_response_code(204);
	exit;
}
$pageTitle = "Logout";
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>UCC Admin | <?= htmlspecialchars($pageTitle) ?></title>
	<link rel="icon" type="image/png" href="assets/images/ucc-LOGO.png">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Libre+Baskerville:wght@700&display=swap" rel="stylesheet">
	<script src="https://unpkg.com/lucide@latest"></script>
	<style>
		* {
			box-sizing: border-box;
		}
		html, body {
			width: 100%;
			height: 100%;
			margin: 0;
			overflow: hidden;
			font-family: "DM Sans", sans-serif;
		}
		#backgroundPage {
			position: fixed;
			inset: 0;
			width: 100%;
			height: 100%;
			border: 0;
			background: #f7f5ed;
			z-index: 1;
		}
		.logout-overlay {
			position: fixed;
			inset: 0;
			z-index: 10;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 24px;
			background: rgba(0, 0, 0, .38);
			backdrop-filter: blur(2px);
		}
		.logout-card {
			width: 100%;
			max-width: 460px;
			padding: 38px 34px;
			background: rgba(255, 255, 255, .98);
			border: 1px solid rgba(0, 0, 0, .08);
			border-radius: 24px;
			box-shadow: 0 20px 60px rgba(0, 0, 0, .22);
			text-align: center;
		}
		.logout-icon {
			width: 68px;
			height: 68px;
			margin: 0 auto 20px;
			display: flex;
			align-items: center;
			justify-content: center;
			border-radius: 20px;
			background: rgba(181, 59, 59, .10);
			color: #b53b3b;
		}
		.logout-icon svg {
			width: 30px;
			height: 30px;
		}
		.logout-card h2 {
			margin: 0 0 9px;
			font-family: "Segoe UI", sans-serif;
			font-size: 24px;
			color: #164734;
		}
		.logout-card p {
			margin: 0 auto;
			max-width: 390px;
			font-size: 14px;
			line-height: 1.6;
			color: #707070;
		}
		.logout-actions {
			display: flex;
			justify-content: center;
			gap: 12px;
			margin-top: 28px;
		}
		.logout-btn {
			min-width: 130px;
			height: 46px;
			padding: 0 20px;
			border-radius: 12px;
			font-family: "DM Sans", sans-serif;
			font-size: 14px;
			font-weight: 700;
			cursor: pointer;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			gap: 8px;
			transition: .2s ease;
		}
		.logout-btn svg {
			width: 17px;
			height: 17px;
		}
		.logout-btn.cancel {
			border: 1px solid #d7d7d7;
			background: #fff;
			color: #555;
		}
		.logout-btn.cancel:hover {
			background: #f6f6f6;
		}
		.logout-btn.confirm {
			border: 1px solid #b53b3b;
			background: #b53b3b;
			color: #fff;
		}
		.logout-btn.confirm:hover {
			background: #963131;
		}
		.logout-success {
			position: fixed;
			inset: 0;
			z-index: 20;
			display: none;
			align-items: center;
			justify-content: center;
			flex-direction: column;
			text-align: center;
			background: rgba(255, 255, 255, .98);
		}
		.logout-success.show {
			display: flex;
		}
		.success-icon {
			width: 72px;
			height: 72px;
			margin-bottom: 18px;
			display: flex;
			align-items: center;
			justify-content: center;
			border-radius: 50%;
			background: rgba(22, 71, 52, .10);
			color: #164734;
		}
		.success-icon svg {
			width: 36px;
			height: 36px;
		}
		.logout-success h2 {
			margin: 0 0 8px;
			font-family: "Segoe UI", sans-serif;
			font-size: 24px;
			color: #164734;
		}
		.logout-success p {
			margin: 0;
			font-size: 14px;
			color: #707070;
		}
		@media (max-width: 600px) {
			.logout-card {
				padding: 30px 22px;
			}
			.logout-actions {
				flex-direction: column-reverse;
			}
			.logout-btn {
				width: 100%;
			}
		}
	</style>
</head>
<body>
	<iframe id="backgroundPage" title="Admin page behind logout confirmation"></iframe>
	<div class="logout-overlay" id="logoutOverlay">
		<div class="logout-card">
			<div class="logout-icon">
				<i data-lucide="log-out"></i>
			</div>
			<h2>Logout?</h2>
			<p>Are you sure you want to logout of the UCC Admin Panel?</p>
			<div class="logout-actions">
				<button type="button" class="logout-btn cancel" id="cancelLogout">Cancel</button>
				<button type="button" class="logout-btn confirm" id="confirmLogout">
					<i data-lucide="log-out"></i>
					Logout
				</button>
			</div>
		</div>
	</div>
	<div class="logout-success" id="logoutSuccess">
		<div class="success-icon">
			<i data-lucide="circle-check"></i>
		</div>
		<h2>Logout Successful</h2>
		<p>You have been logged out successfully.</p>
	</div>
	<script>
		if (window.lucide) {
			window.lucide.createIcons();
		}
		const backgroundPage = document.getElementById("backgroundPage");
		const logoutOverlay = document.getElementById("logoutOverlay");
		const logoutSuccess = document.getElementById("logoutSuccess");
		const confirmLogout = document.getElementById("confirmLogout");
		const cancelLogout = document.getElementById("cancelLogout");
		const currentPage = document.referrer;
		if (currentPage && !currentPage.endsWith("/logout.php") && !currentPage.endsWith("/Logout_Final.php")) {
			backgroundPage.src = currentPage;
		} else {
			backgroundPage.src = "dashboard.php";
		}
		cancelLogout.addEventListener("click", () => {
			if (window.history.length > 1) {
				window.history.back();
			} else {
				window.location.href = "dashboard.php";
			}
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
	</script>
</body>
</html>