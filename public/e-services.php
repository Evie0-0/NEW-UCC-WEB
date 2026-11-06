<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/Repositories/PublicEServicesRepository.php';
use Core\Database;
use Repo\PublicEServicesRepository;
$repo = new PublicEServicesRepository(Database::connect());
$services = $repo->getAllEServices();
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>UCC | E-Services</title>
	<link rel="icon" type="image/png" href="images/UCC2.png">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="assets/utility.css">
	<link rel="stylesheet" href="assets/utility-bar.css">
	<link rel="stylesheet" href="assets/navbar.css">
	<link rel="stylesheet" href="assets/footer.css">
</head>
<body class="eservices-page">
	<?php include __DIR__ . '/includes/utility-bar.php'; ?>
	<?php include __DIR__ . '/includes/navbar.php'; ?>

	<div class="page-dots" aria-hidden="true">
		<span class="dot-pattern dot-1"></span>
		<span class="dot-pattern dot-2"></span>
		<span class="dot-pattern dot-3"></span>
		<span class="dot-pattern dot-4"></span>
	</div>

	<main>
		<!-- E-SERVICES HERO -->
		<section class="eservices-hero">
			<div class="eservices-hero-pattern"></div>
			<div class="eservices-hero-inner">
				<div class="eservices-hero-copy">
					<span class="eservices-eyebrow">
						<span></span>
						UNIVERSITY OF CALOOCAN CITY
					</span>
					<h1>E-Services</h1>
					<p>Access the University's online platforms and digital services in one convenient place. Explore the available services and connect to the resources you need.</p>
					<div class="eservices-hero-note">
						<i data-lucide="shield-check"></i>
						<span>Convenient access to UCC online platforms</span>
					</div>
				</div>
			</div>
		</section>

		<!-- SEARCH SERVICES -->
		<section class="eservices-search-section" aria-label="Search online services">
			<div class="eservices-search-card">
				<div class="search-card-heading">
					<div class="search-icon-wrap">
						<i data-lucide="search"></i>
					</div>
					<div>
						<span>FIND WHAT YOU NEED</span>
						<h2>Search Online Services</h2>
					</div>
				</div>
				<div class="eservices-search-controls">
					<div class="service-search-input">
						<i data-lucide="search"></i>
						<input type="search" id="serviceSearch" placeholder="Search for a service or platform..." autocomplete="off" aria-label="Search services">
					</div>
					<button type="button" class="service-search-button" id="serviceSearchButton">
						<span>Search</span>
						<i data-lucide="arrow-right"></i>
					</button>
				</div>
				<p class="search-hint">Enter a service name or keyword to find a platform.</p>
			</div>
		</section>

		<!-- ONLINE SERVICES -->
		<section class="eservices-section" id="services">
			<div class="eservices-section-heading">
				<div>
					<span class="section-eyebrow">DIGITAL ACCESS</span>
					<h2>Online Services <span>&amp; Platforms</span></h2>
					<p>Explore the digital platforms and services available through the University.</p>
				</div>
				<a href="#eservices-list" class="eservices-explore-link">
					Explore services
					<i data-lucide="arrow-down"></i>
				</a>
			</div>

			<?php if (!empty($services)): ?>
				<div class="eservices-grid" id="eservices-list">
					<?php foreach ($services as $index => $service): ?>
						<?php
							$serviceName = $service['name'] ?? 'Online Service';
							$serviceDescription = $service['description'] ?? '';
							$serviceUrl = $service['url'] ?? '#';
							$featuredImage = !empty($service['featured_image'])
								? 'image.php?f=' . rawurlencode(basename((string) $service['featured_image']))
								: 'images/UCC2.png';
							$searchText = strtolower($serviceName . ' ' . $serviceDescription);
						?>
						<a
							href="<?= e($serviceUrl) ?>"
							class="eservice-card"
							data-search="<?= e($searchText) ?>"
							target="_blank"
							rel="noopener noreferrer"
						>
							<div class="eservice-card-visual">
								<span class="eservice-card-number"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
								<div class="eservice-logo-art">
									<div class="eservice-logo-orbit"></div>
									<img
										src="<?= e($featuredImage) ?>"
										alt="<?= e($serviceName) ?> logo"
										onerror="this.onerror=null;this.src='images/UCC2.png';"
										loading="lazy"
										decoding="async"
									>
								</div>
								<span class="eservice-visual-label">UCC ONLINE</span>
							</div>

							<div class="eservice-card-content">
								<span class="eservice-card-category">UNIVERSITY SERVICE</span>
								<h3><?= e($serviceName) ?></h3>
								<p><?= e($serviceDescription) ?></p>
								<div class="eservice-card-footer">
									<span>Access Service</span>
									<span class="eservice-card-arrow">
										<i data-lucide="arrow-up-right"></i>
									</span>
								</div>
							</div>
						</a>
					<?php endforeach; ?>
				</div>

				<div class="eservices-no-results" id="noServiceResults" hidden>
					<div class="no-results-icon">
						<i data-lucide="search-x"></i>
					</div>
					<h3>No services found</h3>
					<p>We couldn't find a service matching your search. Try another keyword.</p>
					<button type="button" id="resetServiceSearch">Clear Search</button>
				</div>
			<?php else: ?>
				<div class="no-eservices">
					<div class="no-results-icon">
						<i data-lucide="monitor-off"></i>
					</div>
					<h3>No online services available</h3>
					<p>There are currently no services to display. Please check again later.</p>
				</div>
			<?php endif; ?>
		</section>

		<!-- HELP SECTION -->
		<section class="eservices-help">
			<div class="eservices-help-icon">
				<i data-lucide="info"></i>
			</div>
			<div class="eservices-help-content">
				<span>NEED ASSISTANCE?</span>
				<h2>Having trouble accessing a service?</h2>
				<p>For questions or concerns regarding UCC online services, you may reach out through the University's official contact channels.</p>
			</div>
			<a href="contacts.php" class="eservices-help-link">
				Contact UCC
				<i data-lucide="arrow-up-right"></i>
			</a>
		</section>
	</main>

	<?php include __DIR__ . '/includes/footer.php'; ?>

	<button class="back-top" id="backTop" aria-label="Back to top">
		<i data-lucide="arrow-up"></i>
	</button>

	<script src="js/script.js"></script>
	<script src="https://unpkg.com/lucide@latest"></script>
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			if (window.lucide) {
				lucide.createIcons();
			}
			const searchInput = document.getElementById('serviceSearch');
			const clearButton = document.getElementById('clearServiceSearch');
			const searchButton = document.getElementById('serviceSearchButton');
			const resetButton = document.getElementById('resetServiceSearch');
			const resultsContainer = document.getElementById('eservices-list');
			const noResults = document.getElementById('noServiceResults');
			if (!searchInput || !resultsContainer) return;
			const cards = Array.from(resultsContainer.querySelectorAll('.eservice-card'));
			function filterServices() {
				const query = searchInput.value.trim().toLowerCase();
				let visibleCount = 0;
				cards.forEach(function (card) {
					const searchableText = (card.dataset.search || '').toLowerCase();
					const isMatch = searchableText.includes(query);
					card.hidden = !isMatch;
					if (isMatch) visibleCount++;
				});
				if (noResults) {
					noResults.hidden = visibleCount !== 0;
				}
				resultsContainer.classList.toggle('has-no-results', visibleCount === 0);
			}
			function clearSearch() {
				searchInput.value = '';
				filterServices();
				searchInput.focus();
			}
			searchInput.addEventListener('input', filterServices);
			searchInput.addEventListener('keydown', function (event) {
				if (event.key === 'Enter') {
					filterServices();
					document.getElementById('services').scrollIntoView({ behavior: 'smooth' });
				}
				if (event.key === 'Escape') {
					clearSearch();
				}
			});
			if (clearButton) clearButton.addEventListener('click', clearSearch);
			if (searchButton) {
				searchButton.addEventListener('click', function () {
					filterServices();
					document.getElementById('services').scrollIntoView({ behavior: 'smooth' });
				});
			}
			if (resetButton) resetButton.addEventListener('click', clearSearch);
		});
	</script>
</body>
</html>
