<?php

use Core\Database;
use Repo\PublicRegentsRepository;

require_once __DIR__ . '/../app/Repositories/PublicRegentsRepository.php';

$regentsRepository = new PublicRegentsRepository(Database::connect());

$chairman = $regentsRepository->getChairman();
$membersOfBOR = $regentsRepository->getMembersOfBOR();

function regentImagePath(?string $filename): string
{
	return !empty($filename)
		? 'image.php?f=' . rawurlencode($filename)
		: 'images/UCC2.png';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>UCC | Board of Regents</title>
	<link rel="icon" type="image/png" href="images/UCC2.png">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="assets/About.css">
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
	
	<!-- BOARD OF REGENTS PAGE -->
	<main class="board-page">
		<section class="board-hero">
			<div class="container board-hero-content">
				<div class="board-eyebrow">
					<span>UCC LEADERSHIP</span>
					<i></i>
				</div>
				<h1>CHAIRMAN OF THE BOARD OF REGENTS</h1>
			</div>
		</section>
		

		<!-- CHAIRMAN OF THE BOARD -->
		<?php if ($chairman !== null): ?>
			<section class="chairman-section">
				<div class="container">
					<div class="chairman-card">

						<div class="chairman-photo">
							<img
								src="<?= htmlspecialchars(regentImagePath($chairman['featured_image'] ?? null), ENT_QUOTES, 'UTF-8') ?>"
								alt="<?= htmlspecialchars($chairman['name'] ?? 'Chairman of the Board', ENT_QUOTES, 'UTF-8') ?>"
							>
						</div>

						<div class="chairman-content">
							<div class="chairman-copy">

								<h2>
									<?= htmlspecialchars($chairman['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
								</h2>

								<div class="chairman-line"></div>

								<p class="chairman-position">
									<?= htmlspecialchars($chairman['position'] ?? '', ENT_QUOTES, 'UTF-8') ?>
								</p>

								<p>
									
								</p>

								<a href="#" class="chairman-button"
								data-modal-trigger="chairman-corner">
									CHAIRMAN'S CORNER
									<span>→</span>
								</a>

							</div>

							<div class="chairman-decoration"></div>
						</div>

					</div>
				</div>
			</section>
		<?php endif; ?>


		<!-- MEMBERS OF THE BOARD OF REGENTS -->
		<section class="regents-section">
			<div class="container">

				<div class="regents-heading">
					<div class="regents-heading-line"></div>
					<h2>MEMBERS OF THE BOARD OF REGENTS</h2>
					<div class="regents-heading-line"></div>
				</div>

				<div class="regents-grid">

					<?php foreach ($membersOfBOR as $member): ?>
						<article class="regent-card">

							<div class="regent-photo">
								<img
									src="<?= htmlspecialchars(regentImagePath($member['featured_image'] ?? null), ENT_QUOTES, 'UTF-8') ?>"
									alt="<?= htmlspecialchars($member['name'] ?? 'Board Member', ENT_QUOTES, 'UTF-8') ?>"
								>

								<span class="regent-number">
									<?= (int) ($member['order_number'] ?? 0) ?>
								</span>
							</div>

							<div class="regent-role">
								<?= htmlspecialchars($member['position'] ?? 'MEMBER', ENT_QUOTES, 'UTF-8') ?>
							</div>

							<div class="regent-info">
								<h3>
									<?= htmlspecialchars($member['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
								</h3>

								<?php if (!empty($member['biography'])): ?>
									<small>
										<?= htmlspecialchars($member['biography'], ENT_QUOTES, 'UTF-8') ?>
									</small>
								<?php endif; ?>

								<span>BOARD MEMBER</span>
							</div>

						</article>
					<?php endforeach; ?>

					<?php if (empty($membersOfBOR)): ?>
						<p>No Board of Regents members are currently available.</p>
					<?php endif; ?>

				</div>
			</div>
		</section>
	</main>


	<!-- CHAIRMAN'S CORNER MODAL -->
	<?php if ($chairman !== null): ?>
	<div class="student-modal" id="chairmanModal" aria-hidden="true">
		<div class="student-modal-backdrop"></div>
		<div class="student-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="chairmanModalTitle">
			<div class="student-modal-header">
				<div class="student-modal-heading">
					<span class="student-modal-label">UCC LEADERSHIP</span>
					<h2 id="chairmanModalTitle">THE UNIVERSITY CHAIRMAN</h2>
				</div>
				<button type="button" class="student-modal-close" data-modal-close aria-label="Close modal">×</button>
			</div>
			<div class="student-modal-body">
				<div class="student-modal-accent"></div>
				<div class="chairman-modal-profile">
					<div class="chairman-modal-image">
						<img
							src="<?= htmlspecialchars(regentImagePath($chairman['featured_image'] ?? null), ENT_QUOTES, 'UTF-8') ?>"
							alt="<?= htmlspecialchars($chairman['name'] ?? 'Chairman of the Board', ENT_QUOTES, 'UTF-8') ?>"
						>
					</div>
					<div class="chairman-modal-content">
						<h3><?= htmlspecialchars($chairman['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
						<span class="chairman-modal-position">
							<?= htmlspecialchars($chairman['position'] ?? '', ENT_QUOTES, 'UTF-8') ?>
						</span>
						
						<?php if (!empty($chairman['biography'])): ?>
							<?php
							$bioParagraphs = preg_split('/\R+/', trim($chairman['biography']));
							foreach ($bioParagraphs as $paragraph):
								if (trim($paragraph) === '') continue;
							?>
								<p><?= htmlspecialchars(trim($paragraph), ENT_QUOTES, 'UTF-8') ?></p>
							<?php endforeach; ?>
						<?php else: ?>
							<p>No biography</p>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<div class="student-modal-footer"></div>
		</div>
	</div>
	<?php endif; ?>


	
	<?php require_once __DIR__ . '/includes/footer.php'; ?>



	<button class="back-top" id="backTop" aria-label="Back to top">↑</button>
	<script src="js/script.js"></script>
	<script src="https://unpkg.com/lucide@latest"></script>
	<script>
		lucide.createIcons();
		const chairmanModal = document.getElementById("chairmanModal");

		function openChairmanModal() {
			chairmanModal.classList.add("active");
			chairmanModal.setAttribute("aria-hidden", "false");
			document.body.classList.add("student-modal-open");
		}


		function closeChairmanModal() {
			chairmanModal.classList.remove("active");
			chairmanModal.setAttribute("aria-hidden", "true");
			document.body.classList.remove("student-modal-open");
		}
		document.querySelectorAll('[data-modal-trigger="chairman-corner"]').forEach(button => {
			button.addEventListener("click", function(event) {
				event.preventDefault();
				event.stopPropagation();
				openChairmanModal();
			});
		});
		chairmanModal.addEventListener("click", function(event) {
			if (event.target.closest("[data-modal-close]")) {
				closeChairmanModal();
			}
		});
		document.addEventListener("keydown", function(event) {
			if (event.key === "Escape" && chairmanModal.classList.contains("active")) {
				closeChairmanModal();
			}
		});

	</script>
</body>
</html>
