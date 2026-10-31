<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Repositories/PublicUniversityNewsRepository.php'; 

use Core\Database;
use Repo\PublicUniversityNewsRepository;

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

$newsPayload = [];

try {
	$repo = new PublicUniversityNewsRepository(Database::connect());
	$newsPayload = array_map(fn($row) => [
		'id'       => (int)$row['id'],
		'title'    => (string)$row['title'],
		'category' => strtoupper((string)$row['category']),
		'date'     => 'Published ' . timeAgo($row['published_at'] ?? null),
		'image'    => !empty($row['featured_image'])
			? 'image.php?f=' . rawurlencode((string)$row['featured_image'])
			: 'images/UCC2.png',
		'excerpt'  => (string)($row['excerpt'] ?? ''),
		'content'  => strip_tags((string)$row['content'], '<p><br><strong><em><ul><ol><li><a><h3><h4>'),
	], $repo->getAllNews());
} catch (Throwable $e) {
	error_log('News page error: ' . $e->getMessage());
	$newsPayload = [];
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>UCC | News</title>
	<link rel="icon" type="image/png" href="images/UCC2.png">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="assets/news.css">
	<link rel="stylesheet" href="assets/utility.css">
	<link rel="stylesheet" href="assets/utility-bar.css">
	<link rel="stylesheet" href="assets/navbar.css">
	<link rel="stylesheet" href="assets/footer.css">

</head>
<body>
	<?php include __DIR__ . '/includes/utility-bar.php'; ?>
	<?php include __DIR__ . '/includes/navbar.php'; ?>

	<!-- PAGE DOTS -->
	<div class="page-dots" aria-hidden="true">
		<span class="dot-pattern dot-1"></span>
		<span class="dot-pattern dot-2"></span>
		<span class="dot-pattern dot-3"></span>
		<span class="dot-pattern dot-4"></span>
	</div>

	<!-- NEWS MAIN CONTENT -->
	<main class="news-page">
		<div class="news-decor news-decor-top" aria-hidden="true"></div>
		<div class="news-decor news-decor-right" aria-hidden="true"></div>
		<div class="news-decor news-decor-bottom" aria-hidden="true"></div>

		<section class="news-container">
			<div class="news-heading">
				<div>
					<h1>NEWS</h1>
					<div class="news-heading-line">
						<span></span>
						<i></i>
						<span></span>
					</div>
					<p>Stay updated with the latest news, announcements, achievements, and academic developments at the University of Caloocan City.</p>
				</div>
			</div>

			<section class="news-featured" id="featuredNews"></section>

			<div class="news-section-title" id="newsSectionTitle">
				<div>
					<span>MORE NEWS</span>
					<div class="news-mini-line">
						<i></i>
						<i></i>
						<i></i>
					</div>
				</div>
			</div>

			<section class="news-grid-wrapper">
				<button type="button" class="news-slider-arrow news-slider-prev" id="newsPrev" aria-label="Previous news">‹</button>
				<div class="news-grid" id="newsGrid"></div>
				<button type="button" class="news-slider-arrow news-slider-next" id="newsNext" aria-label="Next news">›</button>
			</section>

			<div class="news-pagination" id="newsPagination"></div>

			<button type="button" class="news-view-all" id="viewAllNews">
				<span>▤</span>
				VIEW ALL NEWS
			</button>
		</section>
	</main>

	<!-- NEWS MODAL -->
	<div class="news-modal" id="newsModal" aria-hidden="true">
		<div class="news-modal-backdrop"></div>
		<div class="news-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="newsModalTitle">
			<div class="news-modal-header">
				<div class="news-modal-heading">
					<span class="news-modal-label" id="newsModalCategory">NEWS</span>
					<h2 id="newsModalTitle">News Title</h2>
				</div>
				<button type="button" class="news-modal-close" data-news-modal-close aria-label="Close news popup">×</button>
			</div>
			<div class="news-modal-body">
				<figure class="news-modal-image">
					<a href="#" id="newsModalImageLink" aria-label="View full-size news image">
						<img id="newsModalImage" src="" alt="">
					</a>
				</figure>
				<div class="news-modal-meta">
					<span class="news-modal-category" id="newsModalCategoryBadge">NEWS</span>
					<span class="news-modal-date" id="newsModalDate">Published</span>
				</div>
				<div class="news-modal-accent"></div>
				<div class="news-modal-article" id="newsModalContent"></div>
			</div>
			<div class="news-modal-footer">
				University of Caloocan City • News
			</div>
		</div>
	</div>

	<!-- FULL-SIZE NEWS IMAGE LIGHTBOX -->
	<div class="news-image-lightbox" id="newsImageLightbox" aria-hidden="true">
		<div class="news-image-lightbox-backdrop" data-news-image-close></div>
		<div class="news-image-lightbox-content">
			<button type="button" class="news-image-lightbox-close" data-news-image-close aria-label="Close full-size image">×</button>
			<img id="newsLightboxImage" src="" alt="">
		</div>
	</div>

	<!-- NEWS SCRIPT -->
	<script>
		const newsData = <?= json_encode($newsPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?>;

		function escapeHtml(str){
			return String(str).replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c]));
		}

		let currentPage=1;
		let showAllNews=false;
		const itemsPerPage=10;
		const featuredNews=document.getElementById("featuredNews");
		const newsSectionTitle=document.getElementById("newsSectionTitle");
		const newsGrid=document.getElementById("newsGrid");
		const newsPagination=document.getElementById("newsPagination");
		const newsPrev=document.getElementById("newsPrev");
		const newsNext=document.getElementById("newsNext");
		const viewAllNews=document.getElementById("viewAllNews");
		const newsModal=document.getElementById("newsModal");
		const newsModalTitle=document.getElementById("newsModalTitle");
		const newsModalCategory=document.getElementById("newsModalCategory");
		const newsModalCategoryBadge=document.getElementById("newsModalCategoryBadge");
		const newsModalDate=document.getElementById("newsModalDate");
		const newsModalImage=document.getElementById("newsModalImage");
		const newsModalImageLink=document.getElementById("newsModalImageLink");
		const newsModalContent=document.getElementById("newsModalContent");
		const newsImageLightbox=document.getElementById("newsImageLightbox");
		const newsLightboxImage=document.getElementById("newsLightboxImage");

		function getTotalPages(){
			return Math.max(1,Math.ceil(newsData.length/itemsPerPage));
		}

		// Max 10 news per page. When "View All" is on, everything is returned.
		function getPageItems(){
			if(showAllNews){
				return newsData;
			}
			const start=(currentPage-1)*itemsPerPage;
			return newsData.slice(start,start+itemsPerPage);
		}

		function getExcerpt(title){
			if(title.length<=105){
				return title;
			}
			return title.substring(0,105).trim()+"...";
		}

		function createNewsCard(news){
			const newsIndex=newsData.indexOf(news);
			return `
				<article class="news-card">
					<div class="news-card-image">
						${news.image?`<img src="${escapeHtml(news.image)}" alt="${escapeHtml(news.title)}" loading="lazy">`:""}
					</div>
					<div class="news-card-content">
						<h3>${escapeHtml(news.title)}</h3>
						<div class="news-card-date">
							<span>▣</span>
							${escapeHtml(news.date)}
						</div>
						<div class="news-card-line"></div>
						<a href="#" class="news-card-button" data-news-index="${newsIndex}">
							READ MORE <span>→</span>
						</a>
					</div>
				</article>
			`;
		}

		function renderFeatured(news){
			if(!news){
				featuredNews.innerHTML=`
					<div class="news-featured-content" style="grid-column:1/-1;text-align:center;">
						<h2>No news found</h2>
						<p>There are currently no news articles available.</p>
					</div>
				`;
				return;
			}
			const newsIndex=newsData.indexOf(news);
			featuredNews.innerHTML=`
				<div class="news-featured-image">
					${news.image?`<img src="${escapeHtml(news.image)}" alt="${escapeHtml(news.title)}">`:""}
				</div>
				<div class="news-featured-content">
					<div class="news-featured-glow"></div>
					<span class="news-badge">${escapeHtml(news.category)}</span>
					<h2>${escapeHtml(news.title)}</h2>
					<div class="news-meta">
						<span>▣</span>
						<strong>${escapeHtml(news.date)}</strong>
					</div>
					<div class="news-gold-divider"></div>
					<p>${escapeHtml(news.excerpt||getExcerpt(news.title))}</p>
					<a href="#" class="news-read-more news-featured-read-more" data-news-index="${newsIndex}">
						READ MORE <span>→</span>
					</a>
				</div>
			`;
		}

		function renderPagination(){
			const totalPages=getTotalPages();
			newsPagination.innerHTML="";
			if(showAllNews||totalPages<=1){
				return;
			}

			const previous=document.createElement("button");
			previous.type="button";
			previous.className="news-page-arrow";
			previous.innerHTML="‹ PREVIOUS";
			previous.disabled=currentPage===1;
			previous.addEventListener("click",()=>goToPage(currentPage-1));
			newsPagination.appendChild(previous);

			for(let i=1;i<=totalPages;i++){
				const button=document.createElement("button");
				button.type="button";
				button.className="news-page-number";
				if(i===currentPage){
					button.classList.add("active");
				}
				button.textContent=i;
				button.addEventListener("click",()=>goToPage(i));
				newsPagination.appendChild(button);
			}

			const next=document.createElement("button");
			next.type="button";
			next.className="news-page-arrow";
			next.innerHTML="NEXT ›";
			next.disabled=currentPage===totalPages;
			next.addEventListener("click",()=>goToPage(currentPage+1));
			newsPagination.appendChild(next);
		}

		function updateArrowButtons(){
			const totalPages=getTotalPages();
			if(showAllNews||totalPages<=1){
				newsPrev.style.display="none";
				newsNext.style.display="none";
				return;
			}
			newsPrev.style.display="";
			newsNext.style.display="";
			newsPrev.disabled=currentPage===1;
			newsNext.disabled=currentPage===totalPages;
		}

		function updateViewAllButton(){
			// Hide the button when there is nothing more than one page to expand
			viewAllNews.style.display=newsData.length>itemsPerPage?"":"none";
			if(showAllNews){
				viewAllNews.innerHTML="<span>▤</span> SHOW LESS NEWS";
			}else{
				viewAllNews.innerHTML="<span>▤</span> VIEW ALL NEWS";
			}
		}

		function renderNews(){
			const items=getPageItems();
			const cardItems=items.slice(1);

			renderFeatured(items[0]);

			if(cardItems.length){
				newsSectionTitle.style.display="";
				newsGrid.innerHTML=cardItems.map(news=>createNewsCard(news)).join("");
			}else{
				newsSectionTitle.style.display="none";
				newsGrid.innerHTML="";
			}

			renderPagination();
			updateArrowButtons();
			updateViewAllButton();
		}

		function goToPage(page){
			const totalPages=getTotalPages();
			if(showAllNews||page<1||page>totalPages){
				return;
			}
			currentPage=page;
			renderNews();
			scrollToNewsPage();
		}

		function scrollToNewsPage(){
			const newsPage=document.querySelector(".news-page");
			if(!newsPage){
				return;
			}
			const top=newsPage.getBoundingClientRect().top+window.pageYOffset-20;
			window.scrollTo({
				top:top,
				behavior:"smooth"
			});
		}

		function openNewsModal(index){
			const news=newsData[index];
			if(!news){
				return;
			}
			newsModalTitle.textContent=news.title;
			newsModalCategory.textContent=news.category;
			newsModalCategoryBadge.textContent=news.category;
			newsModalDate.textContent=news.date;
			if(news.image){
				newsModalImage.src=news.image;
				newsModalImage.alt=news.title;
				newsModalImageLink.href=news.image;
				newsModalImageLink.dataset.image=news.image;
				newsModalImageLink.parentElement.style.display="";
			}else{
				newsModalImage.removeAttribute("src");
				newsModalImageLink.dataset.image="";
				newsModalImageLink.parentElement.style.display="none";
			}
			newsModalContent.innerHTML=news.content||"<p>No article content available.</p>";
			newsModal.classList.add("active");
			newsModal.setAttribute("aria-hidden","false");
			document.body.classList.add("news-modal-open");
		}

		function closeNewsModal(){
			closeNewsImageLightbox();
			newsModal.classList.remove("active");
			newsModal.setAttribute("aria-hidden","true");
			document.body.classList.remove("news-modal-open");
		}

		function openNewsImageLightbox(){
			const image=newsModalImageLink.dataset.image;
			if(!image){
				return;
			}
			newsLightboxImage.src=image;
			newsLightboxImage.alt=newsModalImage.alt;
			newsImageLightbox.classList.add("active");
			newsImageLightbox.setAttribute("aria-hidden","false");
		}

		function closeNewsImageLightbox(){
			newsImageLightbox.classList.remove("active");
			newsImageLightbox.setAttribute("aria-hidden","true");
		}

		// One click listener handles every READ MORE button, even after re-rendering
		document.addEventListener("click",event=>{
			const trigger=event.target.closest("[data-news-index]");
			if(!trigger){
				return;
			}
			event.preventDefault();
			event.stopPropagation();
			openNewsModal(Number(trigger.dataset.newsIndex));
		});

		newsModalImageLink.addEventListener("click",event=>{
			event.preventDefault();
			event.stopPropagation();
			openNewsImageLightbox();
		});

		newsImageLightbox.addEventListener("click",event=>{
			if(event.target.closest("[data-news-image-close]")){
				closeNewsImageLightbox();
			}
		});

		newsPrev.addEventListener("click",()=>goToPage(currentPage-1));
		newsNext.addEventListener("click",()=>goToPage(currentPage+1));

		viewAllNews.addEventListener("click",()=>{
			showAllNews=!showAllNews;
			currentPage=1;
			renderNews();
			scrollToNewsPage();
		});

		newsModal.addEventListener("click",event=>{
			if(event.target.closest("[data-news-modal-close]")){
				closeNewsModal();
			}
		});

		document.addEventListener("keydown",event=>{
			if(event.key==="Escape"){
				if(newsImageLightbox.classList.contains("active")){
					closeNewsImageLightbox();
					return;
				}
				if(newsModal.classList.contains("active")){
					closeNewsModal();
				}
			}
		});

		renderNews();
	</script>

	<!-- SHARED FOOTER -->
	<?php include __DIR__ . '/includes/footer.php'; ?>

	<button class="back-top" id="backTop" aria-label="Back to top">↑</button>

	<!-- SCRIPTS -->
	<script src="js/script.js"></script>
	<script src="https://unpkg.com/lucide@latest"></script>
	<script>
		lucide.createIcons();
	</script>
</body>
</html>
