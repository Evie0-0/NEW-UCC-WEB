<?php
declare(strict_types=1);

use Serv\UserServices;

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Support/helper.php';

if (UserServices::isAuthenticated()) {
    header('Location: index.php');
}

$pageTitle = "Login";
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
	<script type="module" src="assets/js/login.js"></script>
	<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
	<div class="login-grid">
		<div class="login-brand" style="background: url('assets/images/green-bg.png') no-repeat;">
			<div class="brand-content">
				<img src="assets/images/ucc-LOGO.png" alt="UCC Logo">
				<div class="brand-line"></div>
				<h1> UCC CMS </h1>
				<p> University of Caloocan City </p>
				<div class="brand-divider"></div>
				<span> PUBLIC SERVICE THROUGH QUALITY EDUCATION </span>
			</div>
		</div>
		<div class="login-side" style="background: url('assets/images/white-bg.png') center center/100% 100% no-repeat;">
			<div class="top-slogan">
				A BRIGHTER CALOOCAN
				<br>
				THROUGH HIGHER LEARNING
				<div class="slogan-line"></div>
			</div>
			<div class="login-card">
				<div class="login-header">
					<div class="login-top-icon">
						<i data-lucide="shield-check"></i>
					</div>
					<h2> CMS Login </h2>
				</div>
				<p> Sign in to access the UCC Website CMS. </p>
				<form class="login-form" id="loginForm">
					<div class="input-group">
						<label for="username"> Username </label>
						<div class="input-box">
							<i data-lucide="user-round"></i>
							<input type="text" name="username" placeholder="Enter Username" autofocus tabindex="0">
						</div>
					</div>
					<div class="input-group">
						<label for="password"> Password </label>
						<div class="input-box">
							<i data-lucide="lock"></i>
							<input type="password" name="password" placeholder="Enter Password" tabindex="0">
						</div>
					</div>
					<button type="submit" data-action="login" class="login-btn" tabindex="0"> Login
						<i data-lucide="arrow-right"></i>
					</button>
				</form>
				<div class="login-footer">
					<div class="footer-line"></div>
					<span>
						Secure Access • UCC Content Management System
					</span>
				</div>
			</div>
		</div>
	</div>
</body>
</html>
