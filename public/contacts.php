<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="Contact the University of Caloocan City and find campus, college, and office contact information.">
	<title>UCC | Contacts</title>
	<link rel="icon" type="image/png" href="images/UCC2.png">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="assets/contacts.css">
	<link rel="stylesheet" href="assets/utility.css">
	<link rel="stylesheet" href="assets/utility-bar.css">
	<link rel="stylesheet" href="assets/navbar.css">
	<link rel="stylesheet" href="assets/footer.css">
</head>
<body>
	<div class="page-dots" aria-hidden="true">
		<span class="dot-pattern dot-1"></span>
		<span class="dot-pattern dot-2"></span>
		<span class="dot-pattern dot-3"></span>
		<span class="dot-pattern dot-4"></span>
	</div>

	<?php require_once __DIR__ . '/includes/utility-bar.php'; ?>
	<?php require_once __DIR__ . '/includes/navbar.php'; ?>

	<main class="contacts-page">
		<section class="contacts-hero">
			<div class="container contacts-hero-inner">
				<div class="contacts-hero-copy">
					<span class="contacts-eyebrow">UNIVERSITY OF CALOOCAN CITY</span>
					<h1>CONNECT WITH UCC</h1>
					<p>Find the right campus, college, or office<br>for your questions.</p>
					<div class="contacts-hero-buttons">
						<a href="campus.php" class="contact-gold-btn">VIEW CAMPUS DIRECTORY<i data-lucide="arrow-right"></i></a>
						<a href="#office-directory" class="contact-outline-btn">SEND AN INQUIRY<i data-lucide="mail"></i></a>
					</div>
				</div>
				<div class="contacts-help-badge">
					<i data-lucide="mail"></i>
					<strong>WE'RE HERE</strong>
					<span>TO HELP</span>
				</div>
			</div>
		</section>
		<section class="quick-contact-section">
			<div class="container">
				<div class="quick-contact-grid">
					<div class="quick-contact-card">
						<div class="quick-contact-icon"><i data-lucide="phone"></i></div>
						<div><span>CALL UCC</span><strong>8528-4654</strong></div>
					</div>
					<div class="quick-contact-card">
						<div class="quick-contact-icon"><i data-lucide="mail"></i></div>
						<div><span>EMAIL US</span><strong>admin@ucc-caloocan.edu.ph</strong></div>
					</div>
					<div class="quick-contact-card">
						<div class="quick-contact-icon"><i data-lucide="map-pin"></i></div>
						<div><span>VISIT MAIN CAMPUS</span><strong>Biglang Awa Street</strong></div>
					</div>
					<div class="quick-contact-card">
						<div class="quick-contact-icon"><i data-lucide="clock-3"></i></div>
						<div><span>OFFICE HOURS</span><strong>Monday–Friday</strong></div>
					</div>
				</div>
			</div>
		</section>
		<section class="offices-section" id="office-directory">
			<div class="container offices-layout">
				<div class="office-search-panel">
					<div class="office-search-heading">
						<h2>Need a specific office?</h2>
						<p>Search colleges, offices, or email addresses.</p>
					</div>
					<div class="office-search-box">
						<input type="text" id="officeSearch" placeholder="Search college, office, or email address" aria-label="Search colleges, offices, or email addresses">
						<button type="button" id="officeSearchButton" aria-label="Search"><i data-lucide="search"></i></button>
					</div>
					<div class="office-tabs" role="group" aria-label="Choose a contact list">
						<button type="button" class="office-tab active" data-filter="colleges" aria-pressed="true">COLLEGES</button>
						<button type="button" class="office-tab" data-filter="inquiry" aria-pressed="false">INQUIRY FORMS</button>
					</div>
					<div class="office-list" id="collegeList">
						<a href="mailto:uccgradschool@ucc-caloocan.edu.ph"><span>Graduate School</span><small>uccgradschool@ucc-caloocan.edu.ph</small></a>
						<a href="mailto:coed@ucc-caloocan.edu.ph"><span>College of Education</span><small>coed@ucc-caloocan.edu.ph</small></a>
						<a href="mailto:clas@ucc-caloocan.edu.ph"><span>College of Liberal Arts and Sciences</span><small>clas@ucc-caloocan.edu.ph</small></a>
						<a href="mailto:collegeoflaw@ucc-caloocan.edu.ph"><span>College of Law</span><small>collegeoflaw@ucc-caloocan.edu.ph</small></a>
						<a href="mailto:engineering@ucc-caloocan.edu.ph"><span>College of Engineering</span><small>engineering@ucc-caloocan.edu.ph</small></a>
						<a href="mailto:ccit@ucc-caloocan.edu.ph"><span>College of Computing and Information Technology</span><small>ccit@ucc-caloocan.edu.ph</small></a>
						<a href="mailto:nursing@ucc-caloocan.edu.ph"><span>College of Nursing</span><small>nursing@ucc-caloocan.edu.ph</small></a>
						<a href="mailto:cba@ucc-caloocan.edu.ph"><span>College of Business and Accountancy</span><small>cba@ucc-caloocan.edu.ph</small></a>
					</div>
					<div class="office-list" id="inquiryList" style="display:none;">
						<a href="mailto:admin@ucc-caloocan.edu.ph?subject=Student%20Affairs%20Inquiry"><span>Student Affairs</span><small>Send an inquiry to UCC</small></a>
						<a href="mailto:admin@ucc-caloocan.edu.ph?subject=Academic%20Office%20Inquiry"><span>Academic Office</span><small>Send an inquiry to UCC</small></a>
						<a href="mailto:accounting@ucc-caloocan.edu.ph?subject=Accounting%20Inquiry"><span>Accounting Matters</span><small>Send an inquiry to Accounting</small></a>
						<a href="mailto:admin@ucc-caloocan.edu.ph?subject=Alumni%20Association%20Inquiry"><span>Alumni Association</span><small>Send an inquiry to UCC</small></a>
						<a href="mailto:admin@ucc-caloocan.edu.ph?subject=Consultation%20Inquiry"><span>Daily Consultation</span><small>Send an inquiry to UCC</small></a>
						<a href="mailto:uccgradschool@ucc-caloocan.edu.ph?subject=Graduate%20School%20Inquiry"><span>Graduate School</span><small>Send an inquiry to the Graduate School</small></a>
					</div>
					<p id="noOfficeResults" style="display:none;" role="status">No matching results found.</p>
				</div>
				<aside class="office-directory-panel">
					<h2>Offices Directory</h2>
					<div class="office-directory-list">
						<a href="mailto:registrar@ucc-caloocan.edu.ph"><i data-lucide="user-round"></i><span><strong>Office of the Registrar</strong>registrar@ucc-caloocan.edu.ph</span></a>
						<a href="mailto:mis@ucc-caloocan.edu.ph"><i data-lucide="graduation-cap"></i><span><strong>MIS</strong>mis@ucc-caloocan.edu.ph</span></a>
						<a href="mailto:library@ucc-caloocan.edu.ph"><i data-lucide="book-open"></i><span><strong>Library</strong>library@ucc-caloocan.edu.ph</span></a>
						<a href="mailto:gcc.uccsouthcampus@gmail.com"><i data-lucide="heart-handshake"></i><span><strong>Guidance and Counselling</strong>gcc.uccsouthcampus@gmail.com</span></a>
						<a href="mailto:ucccampusclinic@gmail.com"><i data-lucide="cross"></i><span><strong>Clinic</strong>ucccampusclinic@gmail.com</span></a>
						<a href="mailto:accounting@ucc-caloocan.edu.ph"><i data-lucide="calculator"></i><span><strong>Accounting</strong>accounting@ucc-caloocan.edu.ph</span></a>
					</div>
				</aside>
			</div>
		</section>
	</main>

	<?php require_once __DIR__ . '/includes/footer.php'; ?>

	<script src="js/script.js"></script>
	<script src="https://unpkg.com/lucide@latest"></script>
	<script>
	document.addEventListener("DOMContentLoaded", function () {
		const officeTabs = document.querySelectorAll(".office-tab");
		const collegeList = document.getElementById("collegeList");
		const inquiryList = document.getElementById("inquiryList");
		const officeSearch = document.getElementById("officeSearch");
		const officeSearchButton = document.getElementById("officeSearchButton");
		const noOfficeResults = document.getElementById("noOfficeResults");
		let activeFilter = "colleges";
		function updateOfficeList() {
			const searchTerm = officeSearch.value.trim().toLowerCase();
			const activeList = activeFilter === "inquiry" ? inquiryList : collegeList;
			const inactiveList = activeFilter === "inquiry" ? collegeList : inquiryList;
			activeList.style.display = "grid";
			inactiveList.style.display = "none";
			let visibleCount = 0;
			activeList.querySelectorAll("a").forEach(item => {
				const matches = item.textContent.toLowerCase().includes(searchTerm);
				item.style.display = matches ? "" : "none";
				if (matches) visibleCount++;
			});
			noOfficeResults.style.display = visibleCount === 0 ? "block" : "none";
		}
		officeTabs.forEach(tab => {
			tab.addEventListener("click", function () {
				activeFilter = this.dataset.filter;
				officeTabs.forEach(item => {
					const isActive = item === this;
					item.classList.toggle("active", isActive);
					item.setAttribute("aria-pressed", String(isActive));
				});
				updateOfficeList();
			});
		});
		officeSearch.addEventListener("input", updateOfficeList);
		officeSearchButton.addEventListener("click", updateOfficeList);
		officeSearch.addEventListener("keydown", function (event) {
			if (event.key === "Enter") {
				event.preventDefault();
				updateOfficeList();
			}
		});
		updateOfficeList();
		if (window.lucide) lucide.createIcons();
	});
	</script>
</body>
</html>
