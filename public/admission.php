<?php

require_once __DIR__ . '/../app/Repositories/PublicAdmissionRepository.php';

use Core\Database;
use Repo\PublicAdmissionRepository;

$admissionRepository = new PublicAdmissionRepository(Database::connect());
$admissionPosts = $admissionRepository->getAdmissionPosts();

//Turns a datetime into "a day ago", "3 months ago", "2 years ago", etc.
if (!function_exists('timeAgo')) {
	function timeAgo(?string $datetime): string
	{
		if (empty($datetime)) {
			return 'recently';
		}

		try {
			$then = new DateTimeImmutable($datetime);
		} catch (Exception $e) {
			return 'recently';
		}

		$now = new DateTimeImmutable('now');

		if ($then > $now) {
			return 'just now';
		}

		$diff = $then->diff($now);

		if ($diff->y > 0) {
			return $diff->y === 1 ? 'a year ago' : $diff->y . ' years ago';
		}
		if ($diff->m > 0) {
			return $diff->m === 1 ? 'a month ago' : $diff->m . ' months ago';
		}
		if ($diff->d > 0) {
			return $diff->d === 1 ? 'a day ago' : $diff->d . ' days ago';
		}
		if ($diff->h > 0) {
			return $diff->h === 1 ? 'an hour ago' : $diff->h . ' hours ago';
		}
		if ($diff->i > 0) {
			return $diff->i === 1 ? 'a minute ago' : $diff->i . ' minutes ago';
		}

		return 'just now';
	}
}
function admissionImagePath(?string $filename): string
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
	<title>UCC | Admission</title>
	<link rel="icon" type="image/png" href="images/UCC2.png">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="assets/utility.css">
	<link rel="stylesheet" href="assets/utility-bar.css">
	<link rel="stylesheet" href="assets/navbar.css">
	<link rel="stylesheet" href="assets/footer.css">
	<style>
		html, body { min-height: 100%; }
		body { min-height: 100vh; display: flex; flex-direction: column; }
		.admission-news-page { flex: 1 0 auto; min-height: 0; }
	</style>
</head>
<body>
	<!-- PAGE DOTS -->
	<div class="page-dots" aria-hidden="true">
		<span class="dot-pattern dot-1"></span>
		<span class="dot-pattern dot-2"></span>
		<span class="dot-pattern dot-3"></span>
		<span class="dot-pattern dot-4"></span>
	</div>
	<?php include __DIR__ . '/includes/utility-bar.php'; ?>
	<?php include __DIR__ . '/includes/navbar.php'; ?>
	
	<!-- MAIN CONTENT -->
	<main class="admission-news-page">
		<div class="admission-news-container">
			<div class="admission-news-heading">
				<h1>ADMISSION NEWS</h1>
				<div class="admission-heading-line"></div>
				<div class="admission-heading-dots">
					<span></span>
					<span></span>
					<span></span>
				</div>
			</div>

		<!-- ADMISSION PROCESS -->
		<?php if (empty($admissionPosts)): ?>
			<p class="admission-empty">No post available for Admission</p>
		<?php else: ?>
		<?php foreach ($admissionPosts as $index => $post): ?>
			<div
				class="admission-post admission-modal-trigger"
				data-article="<?= $index ?>"
			>
				<div class="admission-post-image">
					<img
						src="<?= htmlspecialchars(
							admissionImagePath($post['featured_image'] ?? null),
							ENT_QUOTES,
							'UTF-8'
						) ?>"
						alt="<?= htmlspecialchars(
							$post['title'] ?? '',
							ENT_QUOTES,
							'UTF-8'
						) ?>"
					>
				</div>

				<div class="admission-post-content">
					<span class="admission-category">
						<?= htmlspecialchars(
							$post['name'] ?? 'UCC UPDATE',
							ENT_QUOTES,
							'UTF-8'
						) ?>
					</span>

					<h2>
						<?= htmlspecialchars(
							$post['title'] ?? '',
							ENT_QUOTES,
							'UTF-8'
						) ?>
					</h2>

					<div class="admission-meta">
						<i data-lucide="calendar-days"></i>
						<span
							title="<?= !empty($post['published_at'])
								? htmlspecialchars(date('F j, Y', strtotime($post['published_at'])), ENT_QUOTES, 'UTF-8')
								: '' ?>"
						>
							Published <?= htmlspecialchars(timeAgo($post['published_at'] ?? null), ENT_QUOTES, 'UTF-8') ?>
						</span>
					</div>

					<div class="admission-title-line"></div>

					<p>
						<?= htmlspecialchars(
							$post['excerpt'] ?? '',
							ENT_QUOTES,
							'UTF-8'
						) ?>
					</p>

					<a
						href="#"
						class="admission-read-more"
						data-modal-trigger="<?= $index ?>"
					>
						READ MORE

						<span class="admission-arrow">
							<i data-lucide="arrow-right"></i>
						</span>
					</a>
				</div>
			</div>

			<?php if ($index < count($admissionPosts) - 1): ?>
				<div class="admission-divider"></div>
			<?php endif; ?>
		<?php endforeach; ?>
		<?php endif; ?>
			
		<div class="admission-location-decoration" aria-hidden="true">
			<div class="admission-location-pin">
				<i data-lucide="map-pin"></i>
			</div>
			<div class="admission-dotted-path"></div>
		</div>
		<div class="admission-bg-shape admission-bg-shape-1"></div>
		<div class="admission-bg-shape admission-bg-shape-2"></div>
		</div>
	</main>



	<!-- ADMISSION ARTICLE MODAL -->
	<div class="student-modal" id="admissionModal" aria-hidden="true">
		<div class="student-modal-backdrop"></div>
		<div class="student-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="admissionModalTitle">
			<div class="student-modal-header">
				<div class="student-modal-heading">
					<span class="student-modal-label" id="admissionModalCategory">ADMISSION</span>
				</div>
				<button class="student-modal-close" type="button" aria-label="Close" data-modal-close>×</button>
			</div>
			<div class="student-modal-body">
				<div class="student-modal-accent"></div>
				<h2 id="admissionModalTitle"></h2>
				<div id="admissionModalContent"></div>
			</div>
			<div class="student-modal-footer">
				<span>UNIVERSITY OF CALOOCAN CITY</span>
			</div>
		</div>
	</div>

	<!-- FULL IMAGE LIGHTBOX -->
	<div class="policy-lightbox" id="policyLightbox" aria-hidden="true">
		<div class="policy-lightbox-backdrop" data-policy-close></div>
		<div class="policy-lightbox-content">
			<button type="button" class="policy-lightbox-close" aria-label="Close image" data-policy-close>
				<i data-lucide="x"></i>
			</button>
			<img id="policyLightboxImage" src="" alt="Admission Image">
		</div>
	</div>

	<!-- SHARED FOOTER -->
	<?php include __DIR__ . '/includes/footer.php'; ?>
	<button class="back-top" id="backTop" aria-label="Back to top">↑</button>



	<!-- JAVASCRIPT -->
	<script>
	const admissionArticles = <?= json_encode(
		array_values($admissionPosts),
		JSON_HEX_TAG
			| JSON_HEX_APOS
			| JSON_HEX_QUOT
			| JSON_HEX_AMP
			| JSON_THROW_ON_ERROR
	) ?>;
	</script>

	<script src="https://unpkg.com/lucide@latest"></script>
	<script src="js/script.js"></script>
	<script>
		
		document.addEventListener("DOMContentLoaded",function(){
			if(typeof lucide!=="undefined"){
				lucide.createIcons();
			}
			const admissionModal=document.getElementById("admissionModal");
			const admissionModalCategory=document.getElementById("admissionModalCategory");
			const admissionModalTitle=document.getElementById("admissionModalTitle");
			const admissionModalContent=document.getElementById("admissionModalContent");
			const policyLightbox=document.getElementById("policyLightbox");
			const policyLightboxImage=document.getElementById("policyLightboxImage");
			if(!admissionModal){
				return;
			}
			
			function openAdmissionModal(articleIndex) {
				const article = admissionArticles[Number(articleIndex)];

				if (!article) {
					return;
				}

				admissionModalCategory.textContent =
					article.name || "ADMISSION";

				admissionModalTitle.textContent =
					article.title || "";

				// The content field contains HTML stored in the database.
				admissionModalContent.innerHTML =
					article.content || "";

				admissionModal.classList.add("active");
				admissionModal.setAttribute("aria-hidden", "false");

				document.body.classList.add("student-modal-open");

				const modalBody = admissionModal.querySelector(
					".student-modal-body"
				);

				if (modalBody) {
					modalBody.scrollTop = 0;
				}

				// Re-render icons if the article content includes Lucide icons.
				if (typeof lucide !== "undefined") {
					lucide.createIcons();
				}
			}

			function closeAdmissionModal(){
				admissionModal.classList.remove("active");
				admissionModal.setAttribute("aria-hidden","true");
				document.body.classList.remove("student-modal-open");
			}

			function openPolicyLightbox(imageSrc){
				if(!policyLightbox||!policyLightboxImage){
					return;
				}
				policyLightboxImage.src=imageSrc;
				policyLightbox.classList.add("active");
				policyLightbox.setAttribute("aria-hidden","false");
			}
			
			function closePolicyLightbox(){
				if(!policyLightbox||!policyLightboxImage){
					return;
				}
				policyLightbox.classList.remove("active");
				policyLightbox.setAttribute("aria-hidden","true");
				policyLightboxImage.src="";
			}
			/* WHOLE CARD CLICK */
			document.querySelectorAll(".admission-modal-trigger").forEach(card=>{
				card.addEventListener("click",function(event){
					if(event.target.closest("a")){
						return;
					}
					openAdmissionModal(this.dataset.article);
				});
			});
			/* READ MORE */
			document.querySelectorAll("[data-modal-trigger]").forEach(button=>{
				button.addEventListener("click",function(event){
					event.preventDefault();
					event.stopPropagation();
					openAdmissionModal(this.dataset.modalTrigger);
				});
			});
			/* ALL ADMISSION IMAGES INSIDE MODALS */
			admissionModal.addEventListener("click",function(event){
				const imageTrigger=event.target.closest(".policy-image-trigger");
				if(!imageTrigger){
					return;
				}
				event.preventDefault();
				event.stopPropagation();
				const imageSrc=imageTrigger.dataset.policyImage||imageTrigger.getAttribute("href");
				openPolicyLightbox(imageSrc);
			});
			/* ADMISSION MODAL BACKDROP + CLOSE BUTTON */
			admissionModal.querySelectorAll("[data-modal-close]").forEach(button=>{
				button.addEventListener("click",function(event){
					event.preventDefault();
					event.stopPropagation();
					closeAdmissionModal();
				});
			});
			/* POLICY LIGHTBOX BACKDROP + CLOSE BUTTON */
			if(policyLightbox){
				policyLightbox.querySelectorAll("[data-policy-close]").forEach(button=>{
					button.addEventListener("click",function(event){
						event.preventDefault();
						event.stopPropagation();
						closePolicyLightbox();
					});
				});
			}
			/* ESCAPE KEY */
			document.addEventListener("keydown",function(event){
				if(event.key!=="Escape"){
					return;
				}
				if(policyLightbox&&policyLightbox.classList.contains("active")){
					closePolicyLightbox();
					return;
				}
				if(admissionModal.classList.contains("active")){
					closeAdmissionModal();
				}
			});
		});
	</script>
</body>
</html>



