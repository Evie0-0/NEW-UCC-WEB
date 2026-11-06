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
function regentEscape(?string $value): string
{
	return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
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
	<main class="executive-page board-page">
		<!-- PAGE HERO -->
		<section class="executive-hero board-hero">
			<div class="executive-hero-content board-hero-content">
				<div class="executive-eyebrow">UCC LEADERSHIP</div>
				<h1>BOARD OF REGENTS</h1>
				<p>
					Meet the individuals responsible for the governance
					and leadership of the University of Caloocan City.
				</p>
			</div>
		</section>
		<!-- CHAIRMAN FEATURED CARD -->
		<?php if ($chairman !== null): ?>
		<section class="executive-president-section chairman-section">
			<div class="container">
				<article class="executive-president-card chairman-card">
					<div class="executive-president-photo chairman-photo">
						<img
							src="<?= regentEscape(regentImagePath($chairman['featured_image'] ?? null)) ?>"
							alt="<?= regentEscape($chairman['name'] ?? 'University President') ?>"
						>
					</div>
					<div class="executive-president-content chairman-content">
						<div class="executive-president-copy chairman-copy">
							<div class="executive-card-label">
								<span class="executive-label-dot"></span>
								UNIVERSITY LEADERSHIP
							</div>
							<h2><?= regentEscape($chairman['name'] ?? '') ?></h2>
							<div class="executive-gold-line"></div>
							<p class="executive-position chairman-position">
								<?= regentEscape($chairman['position'] ?? 'CHAIRPERSON, BOARD OF REGENTS') ?>
							</p>
							<span class="president-caption">UNIVERSITY OF CALOOCAN CITY</span>
							<a
								href="#"
								class="chairman-button"
								data-modal-trigger="chairman-corner"
							>
								VIEW PRESIDENT'S PROFILE
								<span>→</span>
							</a>
						</div>
						<div class="executive-decoration chairman-decoration"></div>
					</div>
				</article>
			</div>
		</section>
		<?php endif; ?>
		<!-- BOARD MEMBERS -->
		<section class="executive-officials-section regents-section">
			<div class="container">
				<div class="regents-heading">
					<div class="regents-heading-line"></div>
					<h2>MEMBERS OF THE BOARD OF REGENTS</h2>
					<div class="regents-heading-line"></div>
				</div>
				<div class="regents-grid">
					<?php if (!empty($membersOfBOR)): ?>
						<?php foreach ($membersOfBOR as $index => $member): ?>
							<article class="regent-card">
								<div class="regent-photo">
									<img
										src="<?= regentEscape(regentImagePath($member['featured_image'] ?? null)) ?>"
										alt="<?= regentEscape($member['name'] ?? 'Board Member') ?>"
									>
								</div>
								<div class="regent-number">
									<?= sprintf('%02d', (int) ($member['order_number'] ?? ($index + 2))) ?>
								</div>
								<div class="regent-role">
									BOARD OF REGENTS
								</div>
								<div class="regent-info">
									<h3><?= regentEscape($member['name'] ?? '') ?></h3>
									<small><?= regentEscape($member['position'] ?? 'Member, Board of Regents') ?></small>
									<?php if (!empty($member['biography'])): ?>
										<small><?= nl2br(regentEscape($member['biography'])) ?></small>
									<?php else: ?>
										<small>
											Serves as a member of the Board of Regents,
											contributing to the governance and development
											of the University of Caloocan City.
										</small>
									<?php endif; ?>
								</div>
							</article>
						<?php endforeach; ?>
					<?php else: ?>
						<div class="no-executive-officials">
							<span>✦</span>
							<h3>No Board Members Available</h3>
							<p>
								Board of Regents members are currently
								unavailable. Please check again later.
							</p>
						</div>
					<?php endif; ?>
				</div>
				<div class="executive-bottom-decoration">
					<span></span>
					<p>UNIVERSITY OF CALOOCAN CITY</p>
					<span></span>
				</div>
			</div>
		</section>
	</main>
	<!-- UNIVERSITY PRESIDENT PROFILE MODAL -->
	<?php if ($chairman !== null): ?>
	<div class="student-modal" id="chairmanModal" aria-hidden="true">
		<div class="student-modal-backdrop"></div>
		<div
			class="student-modal-dialog"
			role="dialog"
			aria-modal="true"
			aria-labelledby="chairmanModalTitle"
		>
			<div class="student-modal-header">
				<div class="student-modal-heading">
					<h2 id="chairmanModalTitle">UNIVERSITY PRESIDENT PROFILE</h2>
				</div>
				<button
					type="button"
					class="student-modal-close"
					data-modal-close
					aria-label="Close modal"
				>×</button>
			</div>
			<div class="student-modal-body">
				<div class="student-modal-accent"></div>
				<div class="chairman-modal-profile">
					<div class="chairman-modal-image">
						<img
							src="<?= regentEscape(regentImagePath($chairman['featured_image'] ?? null)) ?>"
							alt="<?= regentEscape($chairman['name'] ?? 'University President') ?>"
						>
					</div>
					<div class="chairman-modal-content">
						<h3><?= regentEscape($chairman['name'] ?? '') ?></h3>
						<span class="chairman-modal-position">
							<?= regentEscape($chairman['position'] ?? '') ?>
						</span>
						<?php if (!empty($chairman['biography'])): ?>
							<?php
							$bioParagraphs = preg_split('/\R+/', trim($chairman['biography']));
							foreach ($bioParagraphs as $paragraph):
								if (trim($paragraph) === '') {
									continue;
								}
							?>
								<p><?= regentEscape(trim($paragraph)) ?></p>
							<?php endforeach; ?>
						<?php else: ?>
							<p>No biography available.</p>
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
		if (window.lucide) {
			lucide.createIcons();
		}
		const chairmanModal = document.getElementById("chairmanModal");
		function openChairmanModal() {
			if (!chairmanModal) return;
			chairmanModal.classList.add("active");
			chairmanModal.setAttribute("aria-hidden", "false");
			document.body.classList.add("student-modal-open");
		}
		function closeChairmanModal() {
			if (!chairmanModal) return;
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
		if (chairmanModal) {
			chairmanModal.addEventListener("click", function(event) {
				if (
					event.target.closest("[data-modal-close]") ||
					event.target.classList.contains("student-modal-backdrop")
				) {
					closeChairmanModal();
				}
			});
		}
		document.addEventListener("keydown", function(event) {
			if (
				event.key === "Escape" &&
				chairmanModal &&
				chairmanModal.classList.contains("active")
			) {
				closeChairmanModal();
			}
		});
	</script>
</body>
</html>
