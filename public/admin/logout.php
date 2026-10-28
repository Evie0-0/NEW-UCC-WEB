<?php
declare(strict_types=1);

use Serv\UserServices;

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Support/helper.php';

if (!UserServices::isAuthenticated()) {
    header('Location: login.php?error=401');
    exit;
}

if (!UserServices::isAuthorized()) {
    header('Location: login.php?error=403');
    exit;
}

$pageTitle = "Logout";
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>UCC Admin | <?= e($pageTitle) ?></title>
	<link rel="icon" type="image/png" href="assets/images/ucc-LOGO.png">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Libre+Baskerville:wght@700&display=swap" rel="stylesheet">
	<script src="https://unpkg.com/lucide@latest"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script type="module" src="assets/js/logout.js"></script>
	<link rel="stylesheet" href="assets/css/logout.css">
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
				<button type="button" class="logout-btn cancel" data-action="cancel">Cancel</button>
				<button type="button" class="logout-btn confirm" data-action="logout">
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
</body>
</html>
