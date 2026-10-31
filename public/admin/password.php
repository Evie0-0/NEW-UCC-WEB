<?php
$pageTitle = "Password";
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
	<link rel="stylesheet" href="assets/css/style.css">
	<style>
		.password-page {
			padding: 30px 36px 40px;
			display: flex;
			justify-content: center;
		}
		.password-card {
			width: 100%;
			max-width: 760px;
			background: rgba(255, 255, 255, .78);
			border: 1px solid rgba(0, 0, 0, .06);
			border-radius: 28px;
			padding: 32px;
			box-shadow: 0 12px 35px rgba(0, 0, 0, .08);
			backdrop-filter: blur(12px);
		}
		.password-card-header {
			display: flex;
			align-items: center;
			gap: 16px;
			padding-bottom: 24px;
			margin-bottom: 26px;
			border-bottom: 1px solid rgba(0, 0, 0, .08);
		}
		.password-icon {
			width: 52px;
			height: 52px;
			flex: 0 0 52px;
			display: flex;
			align-items: center;
			justify-content: center;
			border-radius: 16px;
			background: rgba(191, 151, 63, .14);
			color: #8d6b20;
		}
		.password-icon svg {
			width: 25px;
			height: 25px;
		}
		.password-card-header h2 {
			margin: 0 0 5px;
			font-family: "Segoe UI", sans-serif;
			font-size: 22px;
			font-weight: 700;
			color: #164734;
		}
		.password-card-header p {
			margin: 0;
			font-family: "DM Sans", sans-serif;
			color: #707070;
			font-size: 14px;
		}
		.password-form {
			display: flex;
			flex-direction: column;
			gap: 20px;
		}
		.form-group {
			display: flex;
			flex-direction: column;
			gap: 8px;
		}
		.form-group label {
			font-family: "DM Sans", sans-serif;
			font-size: 14px;
			font-weight: 700;
			color: #303030;
		}
		.password-input {
			position: relative;
			display: flex;
			align-items: center;
			height: 50px;
		}
		.password-input > svg {
			position: absolute;
			left: 15px;
			width: 18px;
			height: 18px;
			color: #777;
			pointer-events: none;
		}
		.password-input input {
			width: 100%;
			height: 50px;
			box-sizing: border-box;
			border: 1px solid #d8d8d8;
			border-radius: 13px;
			background: #fff;
			padding: 0 48px 0 45px;
			font-family: "DM Sans", sans-serif;
			font-size: 14px;
			outline: none;
			transition: .2s ease;
		}
		.password-input input:focus {
			border-color: #164734;
			box-shadow: 0 0 0 3px rgba(22, 71, 52, .08);
		}
		.password-input input.invalid {
			border-color: #b53b3b;
		}
		.toggle-password {
			position: absolute;
			right: 7px;
			top: 7px;
			width: 36px;
			height: 36px;
			border: 0;
			background: transparent;
			color: #777;
			display: flex;
			align-items: center;
			justify-content: center;
			cursor: pointer;
			border-radius: 9px;
		}
		.toggle-password:hover {
			background: #f4f4f4;
			color: #164734;
		}
		.toggle-password svg {
			width: 18px;
			height: 18px;
		}
		.password-strength {
			display: flex;
			align-items: center;
			gap: 10px;
		}
		.strength-bar {
			flex: 1;
			height: 5px;
			border-radius: 10px;
			background: #e7e7e7;
			overflow: hidden;
		}
		.strength-bar span {
			display: block;
			width: 0;
			height: 100%;
			border-radius: inherit;
			background: #164734;
			transition: width .2s ease;
		}
		#strengthText {
			min-width: 105px;
			font-family: "DM Sans", sans-serif;
			font-size: 12px;
			color: #777;
		}
		.match-message {
			font-family: "DM Sans", sans-serif;
			font-size: 12px;
			min-height: 15px;
		}
		.password-requirements {
			padding: 18px 20px;
			border-radius: 16px;
			background: rgba(247, 245, 237, .9);
			border: 1px solid rgba(191, 151, 63, .18);
		}
		.password-requirements h3 {
			margin: 0 0 13px;
			font-family: "Segoe UI", sans-serif;
			font-size: 14px;
			color: #164734;
		}
		.requirement {
			display: flex;
			align-items: center;
			gap: 9px;
			margin: 9px 0;
			color: #777;
			font-family: "DM Sans", sans-serif;
			font-size: 13px;
		}
		.requirement svg {
			width: 16px;
			height: 16px;
		}
		.requirement.valid {
			color: #164734;
		}
		.requirement.valid svg {
			color: #164734;
		}
		.password-actions {
			display: flex;
			justify-content: flex-end;
			gap: 12px;
			padding-top: 4px;
		}
		.password-btn {
			height: 46px;
			padding: 0 20px;
			border-radius: 12px;
			border: 1px solid transparent;
			font-family: "DM Sans", sans-serif;
			font-size: 14px;
			font-weight: 700;
			cursor: pointer;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			gap: 8px;
		}
		.password-btn.primary {
			background: #164734;
			color: #fff;
		}
		.password-btn.primary:hover {
			background: #0f3828;
		}
		.password-btn.secondary {
			background: #fff;
			color: #555;
			border-color: #d7d7d7;
		}
		.password-btn.secondary:hover {
			background: #f6f6f6;
		}
		.password-btn svg {
			width: 17px;
			height: 17px;
		}
		.password-toast {
			position: fixed;
			right: 30px;
			bottom: 30px;
			display: flex;
			align-items: center;
			gap: 9px;
			padding: 13px 17px;
			border-radius: 13px;
			background: #164734;
			color: #fff;
			font-family: "DM Sans", sans-serif;
			font-size: 13px;
			box-shadow: 0 10px 25px rgba(0, 0, 0, .15);
			opacity: 0;
			transform: translateY(12px);
			pointer-events: none;
			transition: .25s ease;
		}
		.password-toast.show {
			opacity: 1;
			transform: translateY(0);
		}
		.password-toast svg {
			width: 18px;
			height: 18px;
		}
		@media (max-width: 700px) {
			.password-page {
				padding: 22px 18px 30px;
			}
			.password-card {
				padding: 24px 20px;
				border-radius: 22px;
			}
			.password-actions {
				flex-direction: column-reverse;
			}
			.password-btn {
				width: 100%;
			}
		}
	</style>
</head>
<body data-page-title="<?= htmlspecialchars($pageTitle) ?>">
	<div class="dashboard-layout">
		<?php include __DIR__ . '/template/sidebar.php'; ?>
		<main class="main-content">
			<?php include __DIR__ . '/template/header.php'; ?>

			<section class="password-page">
				<div class="password-card">
					<div class="password-card-header">
						<div class="password-icon">
							<i data-lucide="lock-keyhole"></i>
						</div>
						<div>
							<h2>Change Password</h2>
							<p>Update your administrator account password.</p>
						</div>
					</div>
					<form id="passwordForm" class="password-form" novalidate>
						<div class="form-group">
							<label for="currentPassword">Current Password</label>
							<div class="password-input">
								<i data-lucide="lock"></i>
								<input type="password" id="currentPassword" placeholder="Enter current password" required>
								<button type="button" class="toggle-password" data-target="currentPassword" aria-label="Show password">
									<i data-lucide="eye"></i>
								</button>
							</div>
						</div>
						<div class="form-group">
							<label for="newPassword">New Password</label>
							<div class="password-input">
								<i data-lucide="key-round"></i>
								<input type="password" id="newPassword" placeholder="Enter new password" required>
								<button type="button" class="toggle-password" data-target="newPassword" aria-label="Show password">
									<i data-lucide="eye"></i>
								</button>
							</div>
							<div class="password-strength">
								<div class="strength-bar"><span id="strengthFill"></span></div>
								<span id="strengthText">Password strength</span>
							</div>
						</div>
						<div class="form-group">
							<label for="confirmPassword">Confirm New Password</label>
							<div class="password-input">
								<i data-lucide="shield-check"></i>
								<input type="password" id="confirmPassword" placeholder="Re-enter new password" required>
								<button type="button" class="toggle-password" data-target="confirmPassword" aria-label="Show password">
									<i data-lucide="eye"></i>
								</button>
							</div>
							<small id="matchMessage" class="match-message"></small>
						</div>
						<div class="password-requirements">
							<h3>Password Requirements</h3>
							<div class="requirement" data-rule="length">
								<i data-lucide="circle-check"></i>
								<span>At least 8 characters</span>
							</div>
							<div class="requirement" data-rule="uppercase">
								<i data-lucide="circle-check"></i>
								<span>At least one uppercase letter</span>
							</div>
							<div class="requirement" data-rule="number">
								<i data-lucide="circle-check"></i>
								<span>At least one number</span>
							</div>
							<div class="requirement" data-rule="special">
								<i data-lucide="circle-check"></i>
								<span>At least one special character</span>
							</div>
						</div>
						<div class="password-actions">
							<button type="button" class="password-btn secondary" id="cancelPassword">Cancel</button>
							<button type="submit" class="password-btn primary">
								<i data-lucide="save"></i>
								Update Password
							</button>
						</div>
					</form>
				</div>
			</section>
			<div class="password-toast" id="passwordToast" role="status" aria-live="polite">
				<i data-lucide="circle-check"></i>
				<span id="toastMessage">Password updated successfully.</span>
			</div>
		</main>
	</div>
	<script>
		"use strict";
		function initSidebarMenu() {
			document.querySelectorAll(".sidebar .menu-title").forEach(button => {
				const menuGroup = button.closest(".menu-group");
				button.addEventListener("click", () => {
					menuGroup.classList.toggle("open");
					button.setAttribute("aria-expanded", menuGroup.classList.contains("open") ? "true" : "false");
				});
			});
		}
		const passwordForm = document.getElementById("passwordForm");
		const currentPassword = document.getElementById("currentPassword");
		const newPassword = document.getElementById("newPassword");
		const confirmPassword = document.getElementById("confirmPassword");
		const strengthFill = document.getElementById("strengthFill");
		const strengthText = document.getElementById("strengthText");
		const matchMessage = document.getElementById("matchMessage");
		const toast = document.getElementById("passwordToast");
		const toastMessage = document.getElementById("toastMessage");
		document.querySelectorAll(".toggle-password").forEach(button => {
			button.addEventListener("click", () => {
				const input = document.getElementById(button.dataset.target);
				const showing = input.type === "password";
				input.type = showing ? "text" : "password";
				button.setAttribute("aria-label", showing ? "Hide password" : "Show password");
				const icon = button.querySelector("[data-lucide]");
				if (icon) {
					icon.setAttribute("data-lucide", showing ? "eye-off" : "eye");
				}
				if (window.lucide) {
					window.lucide.createIcons();
				}
			});
		});
		function updateRequirements() {
			const value = newPassword.value;
			const rules = {
				length: value.length >= 8,
				uppercase: /[A-Z]/.test(value),
				number: /[0-9]/.test(value),
				special: /[^A-Za-z0-9]/.test(value)
			};
			Object.entries(rules).forEach(([rule, valid]) => {
				const requirement = document.querySelector(`[data-rule="${rule}"]`);
				if (requirement) {
					requirement.classList.toggle("valid", valid);
				}
			});
			const score = Object.values(rules).filter(Boolean).length;
			const widths = ["0%", "25%", "50%", "75%", "100%"];
			const labels = ["Password strength", "Weak", "Fair", "Good", "Strong"];
			strengthFill.style.width = widths[score];
			strengthText.textContent = labels[score];
			checkMatch();
		}
		function checkMatch() {
			if (!confirmPassword.value) {
				matchMessage.textContent = "";
				return;
			}
			if (newPassword.value === confirmPassword.value) {
				matchMessage.textContent = "Passwords match.";
				matchMessage.style.color = "#164734";
			} else {
				matchMessage.textContent = "Passwords do not match.";
				matchMessage.style.color = "#b53b3b";
			}
		}
		function clearValidation() {
			document.querySelectorAll(".validation-message").forEach(message => message.remove());
			document.querySelectorAll(".password-input input").forEach(input => input.classList.remove("invalid"));
		}
		function showValidation(inputId, message) {
			const input = document.getElementById(inputId);
			const formGroup = input.closest(".form-group");
			input.classList.add("invalid");
			const messageElement = document.createElement("small");
			messageElement.className = "validation-message";
			messageElement.textContent = message;
			messageElement.style.color = "#b53b3b";
			messageElement.style.fontFamily = "DM Sans, sans-serif";
			messageElement.style.fontSize = "12px";
			messageElement.style.marginTop = "-3px";
			formGroup.appendChild(messageElement);
		}
		newPassword.addEventListener("input", () => {
			clearValidation();
			updateRequirements();
		});
		confirmPassword.addEventListener("input", () => {
			clearValidation();
			checkMatch();
		});
		currentPassword.addEventListener("input", clearValidation);
		passwordForm.addEventListener("submit", event => {
			event.preventDefault();
			clearValidation();
			const current = currentPassword.value;
			const newPass = newPassword.value;
			const confirm = confirmPassword.value;
			let hasError = false;
			if (!current) {
				showValidation("currentPassword", "Please enter your current password.");
				hasError = true;
			}
			if (!newPass) {
				showValidation("newPassword", "Please enter a new password.");
				hasError = true;
			} else if (newPass.length < 8) {
				showValidation("newPassword", "Password must be at least 8 characters.");
				hasError = true;
			} else if (!/[A-Z]/.test(newPass)) {
				showValidation("newPassword", "Password must contain at least one uppercase letter.");
				hasError = true;
			} else if (!/[0-9]/.test(newPass)) {
				showValidation("newPassword", "Password must contain at least one number.");
				hasError = true;
			} else if (!/[^A-Za-z0-9]/.test(newPass)) {
				showValidation("newPassword", "Password must contain at least one special character.");
				hasError = true;
			}
			if (!confirm) {
				showValidation("confirmPassword", "Please confirm your new password.");
				hasError = true;
			} else if (newPass !== confirm) {
				showValidation("confirmPassword", "Passwords do not match.");
				matchMessage.textContent = "Passwords do not match.";
				matchMessage.style.color = "#b53b3b";
				hasError = true;
			}
			if (hasError) {
				return;
			}
			showToast("Password updated successfully.");
			passwordForm.reset();
			clearValidation();
			updateRequirements();
			matchMessage.textContent = "";
		});
		function showToast(message) {
			toastMessage.textContent = message;
			toast.classList.add("show");
			setTimeout(() => toast.classList.remove("show"), 2500);
		}
		document.getElementById("cancelPassword").addEventListener("click", () => {
			passwordForm.reset();
			clearValidation();
			updateRequirements();
			matchMessage.textContent = "";
		});
		initSidebarMenu();
		if (window.lucide) {
			window.lucide.createIcons();
		}
		updateRequirements();
	</script>
</body>
</html>