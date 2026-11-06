document.addEventListener("DOMContentLoaded", function () {
	const programPage = document.getElementById("programEventsPage");
	if (!programPage) return;
	const selectedProgram = (programPage.dataset.programId || "").trim();
	const eventsElement = document.getElementById("programEventsData");
	let selectedEvents = [];
	try {
		selectedEvents = JSON.parse(eventsElement?.textContent || "[]") || [];
		if (!Array.isArray(selectedEvents)) selectedEvents = [];
	} catch (error) {
		console.error("Unable to load program events:", error);
		selectedEvents = [];
	}
	const featuredEvent = document.getElementById("featuredEvent");
	const eventGrid = document.getElementById("eventGrid");
	const eventGridWrapper = document.getElementById("eventGridWrapper");
	const eventPagination = document.getElementById("eventPagination");
	const eventPrev = document.getElementById("eventPrev");
	const eventNext = document.getElementById("eventNext");
	const viewAllEvents = document.getElementById("viewAllEvents");
	const eventsCount = document.getElementById("eventsCount");
	const sectionTitle = document.getElementById("moreEventsTitle");
	const noProgramEvents = document.getElementById("noProgramEvents");
	const eventModal = document.getElementById("eventModal");
	const eventModalTitle = document.getElementById("eventModalTitle");
	const eventModalCategory = document.getElementById("eventModalCategory");
	const eventModalDate = document.getElementById("eventModalDate");
	const eventModalImage = document.getElementById("eventModalImage");
	const eventModalImageLink = document.getElementById("eventModalImageLink");
	const eventModalDescription = document.getElementById("eventModalDescription");
	const eventReadMore = document.getElementById("eventReadMore");
	const eventImageLightbox = document.getElementById("eventImageLightbox");
	const eventLightboxImage = document.getElementById("eventLightboxImage");
	const itemsPerPage = 6;
	const fallbackImage = "images/UCC2.png";
	const eventLabel = "PROGRAM EVENTS";
	let currentPage = 1;
	let showAllEvents = false;
	if (
		!featuredEvent ||
		!eventGrid ||
		!eventGridWrapper ||
		!eventPagination ||
		!eventPrev ||
		!eventNext ||
		!viewAllEvents ||
		!eventsCount ||
		!sectionTitle ||
		!eventModal ||
		!eventModalTitle ||
		!eventModalImage ||
		!eventModalImageLink ||
		!eventModalDescription ||
		!eventImageLightbox ||
		!eventLightboxImage
	) {
		console.error("Some Program Events page elements are missing.");
		return;
	}
	function escapeHTML(value) {
		return String(value ?? "").replace(/[&<>"']/g, character => ({
			"&": "&amp;",
			"<": "&lt;",
			">": "&gt;",
			'"': "&quot;",
			"'": "&#039;"
		})[character]);
	}
	function getSafeImagePath(path) {
		if (typeof path !== "string" || path.includes("..")) {
			return fallbackImage;
		}
		if (
			path === fallbackImage ||
			/^image\.php\?f=[A-Za-z0-9._%~-]+$/.test(path) ||
			/^https?:\/\//i.test(path) ||
			path.startsWith("/")
		) {
			return path;
		}
		return fallbackImage;
	}
	function getArticleUrl(item) {
		if (!item || !item.id || !Number.isInteger(Number(item.id)) || Number(item.id) <= 0) {
			return "";
		}
		return `postArticle.php?type=program&id=${encodeURIComponent(Number(item.id))}`;
	}
	function getTotalPages() {
		return Math.ceil(Math.max(selectedEvents.length - 1, 0) / itemsPerPage);
	}
	function getExcerpt(value, limit = 125) {
		const description = String(value || "").trim();
		return description.length <= limit
			? description
			: description.substring(0, limit).trim() + "...";
	}
	function setImageFallbacks(container) {
		container.querySelectorAll("img").forEach(image => {
			image.addEventListener("error", () => {
				if (!image.dataset.fallbackApplied) {
					image.dataset.fallbackApplied = "true";
					image.src = fallbackImage;
				}
			}, { once: true });
		});
	}
	function createEventCard(item, index) {
		return `
			<article class="news-card program-event-card">
				<div class="news-card-image">
					<img
						src="${escapeHTML(getSafeImagePath(item.image))}"
						alt="${escapeHTML(item.title)}"
						loading="lazy"
					>
				</div>
				<div class="news-card-content">
					<h3>${escapeHTML(item.title)}</h3>
					<div class="news-card-date">
						<span>▣</span>
						${escapeHTML(item.date || "Date unavailable")}
					</div>
					<div class="news-card-line"></div>
					<button
						type="button"
						class="news-card-button"
						data-event-index="${index}"
					>
						READ MORE <span>→</span>
					</button>
				</div>
			</article>
		`;
	}
	function renderFeatured() {
		if (!selectedEvents.length) {
			featuredEvent.innerHTML = "";
			featuredEvent.style.display = "none";
			if (noProgramEvents) {
				noProgramEvents.hidden = false;
				const message = noProgramEvents.querySelector("p");
				if (message) {
					message.textContent = selectedProgram && selectedProgram !== "0"
						? "There are currently no published events for this academic program."
						: "No program was selected. Please select a program to view its events.";
				}
			}
			sectionTitle.style.display = "none";
			eventGridWrapper.style.display = "none";
			eventPagination.innerHTML = "";
			viewAllEvents.style.display = "none";
			eventsCount.textContent = "0 EVENTS";
			return;
		}
		featuredEvent.style.display = "";
		if (noProgramEvents) {
			noProgramEvents.hidden = true;
		}
		sectionTitle.style.display = "";
		eventGridWrapper.style.display = "";
		const item = selectedEvents[0];
		const articleUrl = getArticleUrl(item);
		featuredEvent.innerHTML = `
			<div class="news-featured-image">
				<img
					src="${escapeHTML(getSafeImagePath(item.image))}"
					alt="${escapeHTML(item.title)}"
				>
			</div>
			<div class="news-featured-content">
				<div class="news-featured-glow"></div>
				<span class="news-badge">${eventLabel}</span>
				<h2>${escapeHTML(item.title)}</h2>
				<div class="news-meta">
					<span>▣</span>
					<strong>${escapeHTML(item.date || "Date unavailable")}</strong>
				</div>
				<div class="news-gold-divider"></div>
				<p>${escapeHTML(getExcerpt(item.excerpt || item.description || item.title))}</p>
				${articleUrl ? `
					<a
						href="${escapeHTML(articleUrl)}"
						class="news-read-more news-featured-read-more"
					>
						READ MORE <span>→</span>
					</a>
				` : ""}
			</div>
		`;
		setImageFallbacks(featuredEvent);
	}
	function renderCards() {
		const cardItems = selectedEvents.slice(1);
		const totalPages = getTotalPages();
		eventsCount.textContent = `${selectedEvents.length} ${selectedEvents.length === 1 ? "EVENT" : "EVENTS"}`;
		if (!selectedEvents.length) {
			eventGrid.innerHTML = "";
			eventPagination.innerHTML = "";
			setArrowsVisible(false);
			return;
		}
		if (currentPage > totalPages) {
			currentPage = totalPages || 1;
		}
		const visibleItems = showAllEvents
			? cardItems
			: cardItems.slice((currentPage - 1) * itemsPerPage, currentPage * itemsPerPage);
		eventGrid.innerHTML = visibleItems.length
			? visibleItems.map((item, index) => createEventCard(item, selectedEvents.indexOf(item))).join("")
			: `
				<div class="news-empty">
					<h3>No additional events</h3>
					<p>The featured event is currently the only published event for this program.</p>
				</div>
			`;
		renderPagination(totalPages);
		updateArrowButtons(totalPages);
		setImageFallbacks(eventGrid);
	}
	function renderPagination(totalPages) {
		eventPagination.innerHTML = "";
		if (showAllEvents || totalPages <= 1) return;
		const previous = document.createElement("button");
		previous.type = "button";
		previous.className = "news-page-arrow";
		previous.textContent = "‹ PREVIOUS";
		previous.disabled = currentPage === 1;
		previous.addEventListener("click", () => changePage(currentPage - 1));
		eventPagination.appendChild(previous);
		for (let page = 1; page <= totalPages; page++) {
			const button = document.createElement("button");
			button.type = "button";
			button.className = "news-page-number" + (page === currentPage ? " active" : "");
			button.textContent = page;
			button.setAttribute("aria-label", `Go to page ${page}`);
			button.setAttribute("aria-current", page === currentPage ? "page" : "false");
			button.addEventListener("click", () => changePage(page));
			eventPagination.appendChild(button);
		}
		const next = document.createElement("button");
		next.type = "button";
		next.className = "news-page-arrow";
		next.textContent = "NEXT ›";
		next.disabled = currentPage === totalPages;
		next.addEventListener("click", () => changePage(currentPage + 1));
		eventPagination.appendChild(next);
	}
	function changePage(page) {
		const totalPages = getTotalPages();
		if (page < 1 || page > totalPages) return;
		currentPage = page;
		renderCards();
		scrollToEvents();
	}
	function setArrowsVisible(visible) {
		eventPrev.style.display = visible ? "" : "none";
		eventNext.style.display = visible ? "" : "none";
	}
	function updateArrowButtons(totalPages) {
		const visible = !showAllEvents && totalPages > 1;
		setArrowsVisible(visible);
		eventPrev.disabled = currentPage === 1;
		eventNext.disabled = currentPage === totalPages;
	}
	function scrollToEvents() {
		const section = document.querySelector(".program-events-content");
		if (!section) return;
		window.scrollTo({
			top: section.getBoundingClientRect().top + window.pageYOffset - 20,
			behavior: "smooth"
		});
	}
	function updateViewAllButton() {
		const hasMore = getTotalPages() > 1;
		viewAllEvents.style.display = hasMore ? "" : "none";
		viewAllEvents.innerHTML = showAllEvents
			? "<span>▤</span> SHOW LESS EVENTS"
			: "<span>▤</span> VIEW ALL EVENTS";
	}
	function openEventModal(index) {
		const item = selectedEvents[index];
		if (!item) return;
		const image = getSafeImagePath(item.image);
		eventModalTitle.textContent = item.title || "Untitled Event";
		if (eventModalCategory) {
			eventModalCategory.textContent = eventLabel;
		}
		if (eventModalDate) {
			eventModalDate.textContent = item.date || "Date unavailable";
		}
		eventModalImage.src = image;
		eventModalImage.alt = item.title || "Program event image";
		eventModalImageLink.href = image;
		eventModalImageLink.dataset.image = image;
		eventModalDescription.textContent =
			String(item.content || "").trim() ||
			"No full article content is available for this event.";
		eventModalDescription.style.whiteSpace = "pre-line";
		if (eventReadMore) {
			const articleUrl = getArticleUrl(item);
			if (articleUrl) {
				eventReadMore.href = articleUrl;
				eventReadMore.style.display = "inline-flex";
				eventReadMore.removeAttribute("aria-disabled");
			} else {
				eventReadMore.removeAttribute("href");
				eventReadMore.style.display = "none";
				eventReadMore.setAttribute("aria-disabled", "true");
			}
		}
		setImageFallbacks(eventModal);
		eventModal.classList.add("active");
		eventModal.setAttribute("aria-hidden", "false");
		document.body.classList.add("news-modal-open");
		if (window.lucide) window.lucide.createIcons();
		const closeButton = eventModal.querySelector(".news-modal-close");
		if (closeButton) closeButton.focus();
	}
	function closeEventModal() {
		closeEventImageLightbox();
		eventModal.classList.remove("active");
		eventModal.setAttribute("aria-hidden", "true");
		document.body.classList.remove("news-modal-open");
	}
	function openEventImageLightbox() {
		const image = eventModalImageLink.dataset.image;
		if (!image) return;
		eventLightboxImage.src = image;
		eventLightboxImage.alt = eventModalImage.alt;
		eventImageLightbox.classList.add("active");
		eventImageLightbox.setAttribute("aria-hidden", "false");
		const closeButton = eventImageLightbox.querySelector(".news-image-lightbox-close");
		if (closeButton) closeButton.focus();
	}
	function closeEventImageLightbox() {
		eventImageLightbox.classList.remove("active");
		eventImageLightbox.setAttribute("aria-hidden", "true");
		eventLightboxImage.removeAttribute("src");
	}
	featuredEvent.addEventListener("click", event => {
		const trigger = event.target.closest("[data-event-index]");
		if (trigger) {
			openEventModal(Number(trigger.dataset.eventIndex));
		}
	});
	eventGrid.addEventListener("click", event => {
		const trigger = event.target.closest("[data-event-index]");
		if (trigger) {
			openEventModal(Number(trigger.dataset.eventIndex));
		}
	});
	eventModalImageLink.addEventListener("click", event => {
		event.preventDefault();
		openEventImageLightbox();
	});
	eventImageLightbox.addEventListener("click", event => {
		if (event.target.closest("[data-event-image-close]")) {
			closeEventImageLightbox();
		}
	});
	eventModal.addEventListener("click", event => {
		if (event.target.closest("[data-event-modal-close]")) {
			closeEventModal();
		}
	});
	eventPrev.addEventListener("click", () => changePage(currentPage - 1));
	eventNext.addEventListener("click", () => changePage(currentPage + 1));
	viewAllEvents.addEventListener("click", () => {
		showAllEvents = !showAllEvents;
		currentPage = 1;
		updateViewAllButton();
		renderCards();
		if (!showAllEvents) {
			scrollToEvents();
		}
	});
	document.addEventListener("keydown", event => {
		if (event.key === "Escape") {
			if (eventImageLightbox.classList.contains("active")) {
				closeEventImageLightbox();
			} else if (eventModal.classList.contains("active")) {
				closeEventModal();
			}
		}
	});
	renderFeatured();
	renderCards();
	updateViewAllButton();
	if (window.lucide) {
		window.lucide.createIcons();
	}
});
