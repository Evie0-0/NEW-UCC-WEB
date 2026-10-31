<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Repositories/PublicStudentLifeRepository.php'; 

use Core\Database;
use Repo\PublicStudentLifeRepository;

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

if (!function_exists('escStudent')) {
	function escStudent(?string $value): string
	{
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}
}

$posts = [];

try {
	$repo = new PublicStudentLifeRepository(Database::connect());
	$posts = array_map(function ($row) {
		$excerpt = trim((string)($row['excerpt'] ?? ''));
		$content = strip_tags((string)($row['content'] ?? ''), '<p><br><strong><em><ul><ol><li><a><h3><h4>');
		$plain   = trim((string)preg_replace('/\s+/', ' ', strip_tags($content)));

		
		if ($excerpt === '') {
			$excerpt = mb_strimwidth($plain, 0, 160, '...', 'UTF-8');
		}

		
		if ($plain === '' && $excerpt !== '') {
			$content = '<p>' . htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8') . '</p>';
		}

		return [
			'key'      => 'post-' . (int)$row['id'],
			'title'    => (string)$row['title'],
			'date'     => 'Published ' . timeAgo($row['published_at'] ?? null),
			'image'    => !empty($row['featured_image']) ? 'image.php?f=' . rawurlencode((string)$row['featured_image']) : '',
			'excerpt'  => $excerpt,
			'content'  => $content,
		];
	}, $repo->getAllPost());
} catch (Throwable $e) {
	error_log('Student Life page error: ' . $e->getMessage());
	$posts = [];
}

// First post = big card, the rest = small cards (2 per row)
$featured = $posts[0] ?? null;
$others   = array_slice($posts, 1);


$featuredTitleMain = '';
$featuredTitleSub  = '';
if ($featured) {
	$featuredTitleMain = $featured['title'];
	$colon = strpos($featuredTitleMain, ':');
	if ($colon !== false) {
		$featuredTitleSub  = trim(substr($featuredTitleMain, $colon + 1));
		$featuredTitleMain = trim(substr($featuredTitleMain, 0, $colon + 1));
	}
	$featuredTitleMain = mb_strtoupper($featuredTitleMain, 'UTF-8');
	$featuredTitleSub  = mb_strtoupper($featuredTitleSub, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>UCC | Student Life</title>
	<link rel="icon" type="image/png" href="images/UCC2.png">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="assets/utility.css">
	<link rel="stylesheet" href="assets/student-life.css">
	<link rel="stylesheet" href="assets/utility-bar.css">
	<link rel="stylesheet" href="assets/navbar.css">
	<link rel="stylesheet" href="assets/footer.css">
	<style>
		/* Keep the lightbox close control hidden until the image lightbox opens. */
		.student-image-lightbox:not(.active) .student-image-lightbox-close {
			visibility: hidden;
			opacity: 0;
			pointer-events: none;
		}
		.student-image-lightbox.active .student-image-lightbox-close {
			visibility: visible;
			opacity: 1;
			pointer-events: auto;
		}
	</style>

</head>
<body>
<div class="page-dots" aria-hidden="true">
	<span class="dot-pattern dot-1"></span>
	<span class="dot-pattern dot-2"></span>
	<span class="dot-pattern dot-3"></span>
	<span class="dot-pattern dot-4"></span>
</div>

<?php include __DIR__ . '/includes/utility-bar.php'; ?>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- STUDENT LIFE MAIN CONTENT -->
<main class="student-life-page">
	<div class="student-life-container">
		<!-- PAGE HEADER -->
		<div class="student-life-heading reveal">
			<div>
				<span class="student-life-eyebrow">UCC COMMUNITY</span>
				<h1>STUDENT LIFE</h1>
			</div>
			<div class="student-life-heading-line"></div>
		</div>

		<?php if (!$featured): ?>
			<p class="student-life-empty">There are no student life posts available yet.</p>
		<?php else: ?>

		<!-- FIRST POST (BIG CARD) -->
		<article class="student-card student-card-wide reveal student-modal-trigger" data-article="<?= escStudent($featured['key']) ?>">
			<div class="student-wide-image">
				<?php if ($featured['image'] !== ''): ?>
					<img src="<?= escStudent($featured['image']) ?>" alt="<?= escStudent($featured['title']) ?>">
				<?php endif; ?>
			</div>
			<div class="student-wide-overlay"></div>
			<div class="student-wide-content">
				<h2>
					<?= escStudent($featuredTitleMain) ?>
					<?php if ($featuredTitleSub !== ''): ?>
						<span><?= escStudent($featuredTitleSub) ?></span>
					<?php endif; ?>
				</h2>
				<div class="student-meta student-meta-light">
					<i data-lucide="calendar-days"></i>
					<span><?= escStudent($featured['date']) ?></span>
				</div>
				<p><?= escStudent($featured['excerpt']) ?></p>
				<a href="#" class="student-read-more student-read-more-wide" data-modal-trigger="<?= escStudent($featured['key']) ?>">
					<span>READ MORE</span>
					<span class="student-arrow">→</span>
				</a>
			</div>
		</article>

		<?php if ($others): ?>
		<!-- OTHER POSTS  -->
		<section class="student-life-bottom-grid" id="studentBottomGrid">
			<?php foreach ($others as $post): ?>
				<article class="student-card student-card-bottom reveal student-modal-trigger" data-article="<?= escStudent($post['key']) ?>">
					<div class="student-bottom-content">
						<h2><?= escStudent($post['title']) ?></h2>
						<div class="student-meta student-meta-dark">
							<i data-lucide="calendar-days"></i>
							<span><?= escStudent($post['date']) ?></span>
						</div>
						<p><?= escStudent($post['excerpt']) ?></p>
						<a href="#" class="student-read-more student-read-more-dark" data-modal-trigger="<?= escStudent($post['key']) ?>">
							<span>READ MORE</span>
							<span class="student-arrow student-arrow-outline">→</span>
						</a>
					</div>
					<div class="student-bottom-image">
						<?php if ($post['image'] !== ''): ?>
							<img src="<?= escStudent($post['image']) ?>" alt="<?= escStudent($post['title']) ?>" loading="lazy">
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</section>
		<?php endif; ?>

		<?php endif; ?>
	</div>
</main>

<!-- STUDENT LIFE ARTICLE MODAL -->
<div class="student-modal" id="studentModal" aria-hidden="true">
	<div class="student-modal-backdrop" data-modal-close></div>
	<div class="student-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="studentModalTitle">
		<div class="student-modal-header">
			<div class="student-modal-heading">
				<span class="student-modal-label" id="studentModalCategory">STUDENT LIFE</span>
				<span class="student-modal-number" id="studentModalNumber"></span>
			</div>
			<button class="student-modal-close" type="button" aria-label="Close" data-modal-close>×</button>
		</div>
		<div class="student-modal-body">
			<div class="student-modal-accent"></div>
			<h2 id="studentModalTitle"></h2>
			<figure class="student-modal-image" id="studentModalImageWrap" style="display:none;">
				<a href="#" id="studentModalImageLink" aria-label="View full-size image">
					<img id="studentModalImage" src="" alt="">
				</a>
			</figure>
			<div class="student-modal-meta-row" id="studentModalMetaRow">
				<span class="student-modal-badge">STUDENT LIFE</span>
				<span class="student-modal-published" id="studentModalPublished"></span>
			</div>
			<div id="studentModalContent"></div>
		</div>
		<div class="student-modal-footer">
			<span>UNIVERSITY OF CALOOCAN CITY</span>
		</div>
	</div>
</div>

<!-- FULL-SIZE IMAGE LIGHTBOX -->
<div class="student-image-lightbox" id="studentImageLightbox" aria-hidden="true">
	<div class="student-image-lightbox-backdrop" data-student-image-close></div>
	<button type="button" class="student-image-lightbox-close" data-student-image-close aria-label="Close full-size image">×</button>
	<div class="student-image-lightbox-content">
		<img id="studentLightboxImage" src="" alt="">
	</div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<button class="back-top" id="backTop" aria-label="Back to top">↑</button>
<script src="js/script.js"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>

<script>
	const studentArticles = <?= json_encode(array_column($posts, null, 'key'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_FORCE_OBJECT) ?>;

	const studentModal=document.getElementById("studentModal");
	const studentModalCategory=document.getElementById("studentModalCategory");
	const studentModalNumber=document.getElementById("studentModalNumber");
	const studentModalTitle=document.getElementById("studentModalTitle");
	const studentModalContent=document.getElementById("studentModalContent");
	const studentModalImageWrap=document.getElementById("studentModalImageWrap");
	const studentModalImage=document.getElementById("studentModalImage");
	const studentModalImageLink=document.getElementById("studentModalImageLink");
	const studentModalPublished=document.getElementById("studentModalPublished");
	const studentImageLightbox=document.getElementById("studentImageLightbox");
	const studentLightboxImage=document.getElementById("studentLightboxImage");

	function openStudentModal(articleId){
		const article=studentArticles[articleId];
		if(!article)return;

		// "Published ..." row under the photo
		studentModalPublished.textContent=article.date||"";

		studentModalNumber.textContent="";
		studentModalTitle.textContent=article.title;
		studentModalContent.innerHTML=article.content||"<p>No article content available.</p>";

		// Image posts show the picture in the modal. Text-only posts stay as plain text.
		if(article.image){
			studentModalImage.src=article.image;
			studentModalImage.alt=article.title;
			studentModalImageLink.href=article.image;
			studentModalImageLink.dataset.image=article.image;
			studentModalImageWrap.style.display="";
		}else{
			studentModalImage.removeAttribute("src");
			studentModalImageLink.dataset.image="";
			studentModalImageWrap.style.display="none";
		}

		studentModal.classList.add("show");
		studentModal.setAttribute("aria-hidden","false");
		document.body.classList.add("student-modal-open");
		const modalBody=studentModal.querySelector(".student-modal-body");
		modalBody.scrollTop=0;
	}

	function closeStudentModal(){
		closeStudentLightbox();
		studentModal.classList.remove("show");
		studentModal.setAttribute("aria-hidden","true");
		document.body.classList.remove("student-modal-open");
	}

	function openStudentLightbox(){
		const image=studentModalImageLink.dataset.image;
		if(!image)return;
		studentLightboxImage.src=image;
		studentLightboxImage.alt=studentModalImage.alt;
		studentImageLightbox.classList.add("active");
		studentImageLightbox.setAttribute("aria-hidden","false");
	}

	function closeStudentLightbox(){
		studentImageLightbox.classList.remove("active");
		studentImageLightbox.setAttribute("aria-hidden","true");
		studentLightboxImage.removeAttribute("src");
	}

	document.querySelectorAll(".student-modal-trigger").forEach(card=>{
		card.addEventListener("click",function(event){
			if(event.target.closest("a"))return;
			openStudentModal(this.dataset.article);
		});
	});
	document.querySelectorAll("[data-modal-trigger]").forEach(button=>{
		button.addEventListener("click",function(event){
			event.preventDefault();
			event.stopPropagation();
			openStudentModal(this.dataset.modalTrigger);
		});
	});
	document.querySelectorAll("[data-modal-close]").forEach(button=>{
		button.addEventListener("click",closeStudentModal);
	});

	studentModalImageLink.addEventListener("click",function(event){
		event.preventDefault();
		event.stopPropagation();
		openStudentLightbox();
	});
	studentImageLightbox.addEventListener("click",function(event){
		if(event.target.closest("[data-student-image-close]")){
			closeStudentLightbox();
		}
	});

	document.addEventListener("keydown",function(event){
		if(event.key!=="Escape")return;
		if(studentImageLightbox.classList.contains("active")){
			closeStudentLightbox();
			return;
		}
		if(studentModal.classList.contains("show")){
			closeStudentModal();
		}
	});

	// If the small cards are an odd number, center the last one 
	function centerLastStudentCard(){
		const grid=document.getElementById("studentBottomGrid");
		if(!grid||!grid.lastElementChild)return;
		const last=grid.lastElementChild;
		last.style.gridColumn="";
		last.style.justifySelf="";
		last.style.width="";
		const styles=getComputedStyle(grid);
		const columns=styles.gridTemplateColumns.split(" ").length;
		if(columns===2&&grid.children.length%2===1){
			const gap=parseFloat(styles.columnGap)||0;
			last.style.gridColumn="1 / -1";
			last.style.justifySelf="center";
			last.style.width="calc(50% - "+(gap/2)+"px)";
		}
	}
	centerLastStudentCard();
	window.addEventListener("resize",centerLastStudentCard);
</script>
</body>
</html>

