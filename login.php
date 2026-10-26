<?php
$pageTitle = "Login";
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta
		name="viewport"
		content="width=device-width, initial-scale=1.0"
	>
	<title>UCC Admin | <?= htmlspecialchars($pageTitle) ?></title>
	<link
		rel="icon"
		type="image/png"
		href="assets/images/ucc-LOGO.png"
	>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link
		rel="preconnect"
		href="https://fonts.gstatic.com"
		crossorigin
	>
	<link
		href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Libre+Baskerville:wght@700&display=swap"
		rel="stylesheet"
	>
	<script src="https://unpkg.com/lucide@latest"></script>

	<link rel="stylesheet" href="assets/css/style.css">

</head>
<body class="login-page">
	<div class="login-grid">

		<div class="login-brand">

			<div class="brand-content">

				<img
					src="assets/images/ucc-LOGO.png"
					alt="UCC Logo"
				>

				<div class="brand-line"></div>

				<h1>
					UCC CMS
				</h1>

				<p>
					University of Caloocan City
				</p>

				<div class="brand-divider"></div>

				<span>
					PUBLIC SERVICE THROUGH QUALITY EDUCATION
				</span>

			</div>

		</div>

		<div class="login-side">

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

					<h2>
						CMS Login
					</h2>

				</div>
				<p>
					Sign in to access the UCC Website CMS.
				</p>

				<form
					class="login-form"
					id="loginForm"
				>
					<div class="input-group">

						<label for="username">
							Username
						</label>

						<div class="input-box">

							<i data-lucide="user-round"></i>

							<input
								type="text"
								id="username"
								name="username"
								placeholder="Enter Username"
								autocomplete="username"
							>

						</div>

					</div>
					<div class="input-group">

						<label for="password">
							Password
						</label>

						<div class="input-box">

							<i data-lucide="lock"></i>

							<input
								type="password"
								id="password"
								name="password"
								placeholder="Enter Password"
								autocomplete="current-password"
							>

						</div>

					</div>
					<button
						type="submit"
						class="login-btn"
					>
						Login

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

	<script>
		lucide.createIcons();
		const loginForm =
			document.getElementById("loginForm");
		loginForm.addEventListener(
			"submit",
			function(event) {
				event.preventDefault();
				window.location.href =
					"Dashboard.php";
			}
		);

	</script>

</body>

</html>