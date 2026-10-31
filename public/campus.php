<?php
use Core\Database;
use Repo\PublicCampusRepository;

require_once __DIR__ . '/../app/Repositories/PublicCampusRepository.php';

$campusRepository = new PublicCampusRepository(Database::connect());
$campuses = $campusRepository->getCampus();
$campusCount = $campusRepository->countActiveCampuses();

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="University of Caloocan City campus directory">
	<title>UCC | Campus</title>
	<link rel="icon" type="image/png" href="images/UCC2.png">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="assets/campus.css">
	<link rel="stylesheet" href="assets/utility-bar.css">
	<link rel="stylesheet" href="assets/navbar.css">
	<link rel="stylesheet" href="assets/footer.css">
	<style>
		html, body { min-height: 100%; }
		body { min-height: 100vh; display: flex; flex-direction: column; }
		#campuses { flex: 1 0 auto; min-height: 0; }
	</style>
</head>

<body>

	<?php require_once __DIR__ . '/includes/utility-bar.php'; ?>
	<?php require_once __DIR__ . '/includes/navbar.php'; ?>
	
	<main id="campuses">
		<section class="campus-hero">
			<div class="campus-brand">
				<span class="brand-icon">♜</span>
				<span>UCC CAMPUS DIRECTORY</span>
			</div>
			<div class="container">
				<h1>Find your UCC Campus</h1>
				<p>Explore our campuses located across Caloocan City.</p>
				<div class="campus-community-badge">
					<i data-lucide="users"></i>
					<span>
						<?= $campusCount ?> <?= $campusCount === 1 ? 'CAMPUS' : 'CAMPUSES' ?>
						<small>ONE COMMUNITY</small>
					</span>
				</div>
			</div>
		</section>


		<!-- CAMPUSES CARDS -->
		<section class="campus-directory container">
			<div class="campus-grid" id="all-campuses">
				<?php foreach ($campuses as $index => $campus): ?>
					<a class="campus-card featured"
					id="<?= $index === 0 ? 'main-campus' : 'campus-' . ($index + 1) ?>"
					href="<?= htmlspecialchars($campus['google_maps_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
					target="_blank" rel="noopener noreferrer">

						<div class="campus-image-wrap">
							<img
								src="<?= htmlspecialchars((!empty($campus['featured_image']) ? 'image.php?f=' . rawurlencode((string)$campus['featured_image']) : 'images/UCC2.png'), ENT_QUOTES, 'UTF-8') ?>"
								alt="<?= htmlspecialchars($campus['name'] ?? 'Campus', ENT_QUOTES, 'UTF-8') ?>"
							>
							<?php if ($index === 0): ?>
								<span class="campus-badge">★ MAIN CAMPUS</span>
							<?php endif; ?>
						</div>

						<div class="campus-card-body">
							<h2><?= htmlspecialchars($campus['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>

							<div class="campus-detail">
								<i data-lucide="map-pin"></i>
								<span><?= htmlspecialchars($campus['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
							</div>

							<div class="campus-detail">
								<i data-lucide="phone"></i>
								<span>Contact: <?= htmlspecialchars($campus['contacts'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
							</div>

							<div class="campus-detail">
								<i data-lucide="mail"></i>
								<span><?= htmlspecialchars($campus['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
							</div>

							<div class="campus-link">
								<span>SHOW LOCATION ON GOOGLE MAPS</span>
								<i data-lucide="map"></i>
							</div>
						</div>
					</a>
				<?php endforeach; ?>

				<?php if (empty($campuses)): ?>
					<p>No campuses are currently available.</p>
				<?php endif; ?>

			</div>
		</section>


		<section class="glance-section">
			<div class="container">
				<div class="section-title">
					<div class="section-eyebrow">UCC CAMPUS DIRECTORY <span></span></div>
					<div class="section-heading-row">
						<span></span>
						<h2>CAMPUS AT A GLANCE</h2>
						<span></span>
					</div>
				</div>
				<div class="glance-grid">
					<div class="glance-card">
						<div class="glance-icon">
							<i data-lucide="building-2"></i>
						</div>
						<div class="glance-text">
							<strong>
								<?= $campusCount ?> <?= $campusCount === 1 ? 'CAMPUS' : 'CAMPUSES' ?>
							</strong>
							<span>CAMPUSES</span>
						</div>
					</div>
					<div class="glance-card">
						<div class="glance-icon">
							<i data-lucide="map-pin"></i>
						</div>
						<div class="glance-text">
							<strong>CITY-WIDE</strong>
							<span>ACCESS</span>
						</div>
					</div>
					<div class="glance-card">
						<div class="glance-icon">
							<i data-lucide="users"></i>
						</div>
						<div class="glance-text">
							<strong>STUDENT-</strong>
							<span>CENTERED SPACES</span>
						</div>
					</div>
					<div class="glance-card">
						<div class="glance-icon">
							<i data-lucide="heart-handshake"></i>
						</div>
						<div class="glance-text">
							<strong>ONE UCC</strong>
							<span>COMMUNITY</span>
						</div>
					</div>
				</div>
			</div>
		</section>
		<section class="visit-callout container">
			<div>
				<h2>Planning a visit?</h2>
				<p>See contact details and for colleges and offices.</p>
			</div>
			<a href="contacts.php" class="gold-button">
				CONTACT UCC
				<i data-lucide="arrow-right"></i>
			</a>
		</section>
	</main>

	<?php require_once __DIR__ . '/includes/footer.php'; ?>


	<script src="https://unpkg.com/lucide@latest"></script>
	<script>
		lucide.createIcons();
		const backTop=document.getElementById("backTop");
		if(backTop){
			window.addEventListener("scroll",()=>{
				if(window.scrollY>300){
					backTop.classList.add("show");
				}else{
					backTop.classList.remove("show");
				}
			});
			backTop.addEventListener("click",()=>{
				window.scrollTo({
					top:0,
					behavior:"smooth"
				});
			});
		}
		const menuToggle=document.querySelector(".menu-toggle");
		const mainNav=document.querySelector(".main-nav");
		if(menuToggle&&mainNav){
			menuToggle.addEventListener("click",()=>{
				mainNav.classList.toggle("open");
			});
		}
	</script>
</body>
</html>

