document.addEventListener("DOMContentLoaded", function () {
	const programPage = document.getElementById("programEventsPage");
	const selectedProgram = (programPage?.dataset.programId || "").trim();

	let selectedEvents = [];

	try {
		selectedEvents = JSON.parse(
			document.getElementById("programEventsData").textContent
		) || [];
	} catch (error) {
		selectedEvents = [];
	}

	function escapeHTML(value) {
		return String(value ?? "").replace(/[&<>"']/g, character => ({
			"&": "&amp;",
			"<": "&lt;",
			">": "&gt;",
			'"': "&quot;",
			"'": "&#39;"
		})[character]);
	}

	const featuredEvent = document.getElementById("featuredEvent");
	const eventGrid = document.getElementById("eventGrid");
	const eventPagination = document.getElementById("eventPagination");
	const eventPrev = document.getElementById("eventPrev");
	const eventNext = document.getElementById("eventNext");
	const viewAllEvents = document.getElementById("viewAllEvents");
	const eventModal = document.getElementById("eventModal");
	const eventModalTitle = document.getElementById("eventModalTitle");
	const eventModalDate = document.getElementById("eventModalDate");
	const eventModalImage = document.getElementById("eventModalImage");
	const eventModalImageLink = document.getElementById("eventModalImageLink");
	const eventModalDescription = document.getElementById("eventModalDescription");
	const eventImageLightbox = document.getElementById("eventImageLightbox");
	const eventLightboxImage = document.getElementById("eventLightboxImage");
	const noProgramEvents = document.getElementById("noProgramEvents");
	const sectionTitle = document.querySelector(".news-section-title");
	const gridWrapper = document.querySelector(".news-grid-wrapper");

	const itemsPerPage = 6;
	let currentPage = 1;
	let showAllEvents = false;

	function getTotalPages() {
		return Math.ceil(Math.max(selectedEvents.length - 1, 0) / itemsPerPage);
	}

	function getExcerpt(description) {
		description = String(description || "");
		return description.length <= 180
			? description
			: description.substring(0, 180).trim() + "...";
	}

	function createEventCard(item, index) {
		return `
			<article class="news-card">
				<div class="news-card-image">
					<img src="${escapeHTML(item.image)}" alt="${escapeHTML(item.title)}" loading="lazy" onerror="this.onerror=null;this.src='images/UCC2.png';">
				</div>
				<div class="news-card-content">
					<h3>${escapeHTML(item.title)}</h3>
					<div class="news-card-date"><span>▣</span>${escapeHTML(item.date)}</div>
					<div class="news-card-line"></div>
					<a href="#" class="news-card-button" data-event-index="${index}">READ MORE <span>→</span></a>
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
				noProgramEvents.textContent = selectedProgram && selectedProgram !== "0"
					? "There are currently no events available for this program."
					: "No program was selected. Please select a program to view its events.";
			}

			if (sectionTitle) sectionTitle.style.display = "none";
			if (gridWrapper) gridWrapper.style.display = "none";

			eventPagination.innerHTML = "";
			viewAllEvents.style.display = "none";
			return;
		}

		featuredEvent.style.display = "";
		if (noProgramEvents) noProgramEvents.hidden = true;
		if (sectionTitle) sectionTitle.style.display = "";
		if (gridWrapper) gridWrapper.style.display = "";

		const item = selectedEvents[0];

		featuredEvent.innerHTML = `
			<div class="news-featured-image">
				<img src="${escapeHTML(item.image)}" alt="${escapeHTML(item.title)}" onerror="this.onerror=null;this.src='images/UCC2.png';">
			</div>
			<div class="news-featured-content">
				<div class="news-featured-glow"></div>
				<h2>${escapeHTML(item.title)}</h2>
				<div class="news-meta"><span>▣</span><strong>${escapeHTML(item.date)}</strong></div>
				<div class="news-gold-divider"></div>
				<p>${escapeHTML(getExcerpt(item.description))}</p>
				<a href="#" class="news-read-more news-featured-read-more" data-event-index="0">READ MORE <span>→</span></a>
			</div>
		`;
	}

	function renderCards() {
		if (!selectedEvents.length) {
			eventGrid.innerHTML = "";
			eventPagination.innerHTML = "";
			eventPrev.style.display = "none";
			eventNext.style.display = "none";
			return;
		}

		const cardItems = selectedEvents.slice(1);
		const totalPages = getTotalPages();

		if (currentPage > totalPages) {
			currentPage = totalPages || 1;
		}

		const visibleItems = showAllEvents
			? cardItems
			: cardItems.slice((currentPage - 1) * itemsPerPage, currentPage * itemsPerPage);

		eventGrid.innerHTML = visibleItems.length
			? visibleItems.map(item => createEventCard(item, selectedEvents.indexOf(item))).join("")
			: `<div class="news-empty"><h3>No additional events</h3><p>There are no more events available for this program.</p></div>`;

		renderPagination(totalPages);
		updateArrowButtons(totalPages);
	}

	function renderPagination(totalPages) {
		eventPagination.innerHTML = "";

		if (showAllEvents || totalPages <= 1) return;

		const previous = document.createElement("button");
		previous.type = "button";
		previous.className = "news-page-arrow";
		previous.textContent = "‹ PREVIOUS";
		previous.disabled = currentPage === 1;
		previous.addEventListener("click", () => {
			if (currentPage > 1) {
				currentPage--;
				renderCards();
				scrollToEventsPage();
			}
		});
		eventPagination.appendChild(previous);

		for (let page = 1; page <= totalPages; page++) {
			const button = document.createElement("button");
			button.type = "button";
			button.className = "news-page-number" + (page === currentPage ? " active" : "");
			button.textContent = page;
			button.addEventListener("click", () => {
				currentPage = page;
				renderCards();
				scrollToEventsPage();
			});
			eventPagination.appendChild(button);
		}

		const next = document.createElement("button");
		next.type = "button";
		next.className = "news-page-arrow";
		next.textContent = "NEXT ›";
		next.disabled = currentPage === totalPages;
		next.addEventListener("click", () => {
			if (currentPage < totalPages) {
				currentPage++;
				renderCards();
				scrollToEventsPage();
			}
		});
		eventPagination.appendChild(next);
	}

	function updateArrowButtons(totalPages) {
		const hide = showAllEvents || totalPages <= 1;
		eventPrev.style.display = hide ? "none" : "";
		eventNext.style.display = hide ? "none" : "";
		eventPrev.disabled = currentPage === 1;
		eventNext.disabled = currentPage === totalPages;
	}

	function scrollToEventsPage() {
		const page = document.querySelector(".news-page");
		if (!page) return;

		window.scrollTo({
			top: page.getBoundingClientRect().top + window.pageYOffset - 20,
			behavior: "smooth"
		});
	}

	function updateViewAllButton() {
		viewAllEvents.innerHTML = showAllEvents
			? "<span>▤</span> SHOW LESS EVENTS"
			: "<span>▤</span> VIEW ALL EVENTS";

		viewAllEvents.style.display = getTotalPages() > 1 ? "" : "none";
	}

	function openEventModal(index) {
		const item = selectedEvents[index];
		if (!item) return;

		eventModalTitle.textContent = item.title;
		eventModalDate.textContent = item.date;
		eventModalImage.src = item.image;
		eventModalImage.alt = item.title;
		eventModalImageLink.href = item.image;
		eventModalImageLink.dataset.image = item.image;

		// The modal uses the full post content, not the card excerpt.
		eventModalDescription.textContent =
			item.content || "No full content is available for this event.";

		eventModal.classList.add("active");
		eventModal.setAttribute("aria-hidden", "false");
		document.body.classList.add("news-modal-open");
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
	}

	function closeEventImageLightbox() {
		eventImageLightbox.classList.remove("active");
		eventImageLightbox.setAttribute("aria-hidden", "true");
		eventLightboxImage.src = "";
	}

	document.addEventListener("click", function (event) {
		const trigger = event.target.closest("[data-event-index]");

		if (trigger) {
			event.preventDefault();
			openEventModal(Number(trigger.dataset.eventIndex));
		}

		if (event.target.closest("[data-event-modal-close]")) {
			closeEventModal();
		}

		if (event.target.closest("[data-event-image-close]")) {
			closeEventImageLightbox();
		}
	});

	eventModalImageLink.addEventListener("click", function (event) {
		event.preventDefault();
		openEventImageLightbox();
	});

	eventPrev.addEventListener("click", () => {
		if (currentPage > 1 && !showAllEvents) {
			currentPage--;
			renderCards();
			scrollToEventsPage();
		}
	});

	eventNext.addEventListener("click", () => {
		if (currentPage < getTotalPages() && !showAllEvents) {
			currentPage++;
			renderCards();
			scrollToEventsPage();
		}
	});

	viewAllEvents.addEventListener("click", () => {
		showAllEvents = !showAllEvents;
		currentPage = 1;
		updateViewAllButton();
		renderCards();

		if (!showAllEvents) scrollToEventsPage();
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
});