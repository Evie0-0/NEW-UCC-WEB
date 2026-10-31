document.addEventListener("DOMContentLoaded", () => {
    const eventsElement = document.getElementById("collegeEventsData");
    if (!eventsElement) return;
    let eventsData = [];
    try {
        eventsData = JSON.parse(eventsElement.textContent || "[]");
    } catch (error) {
        console.error("Unable to load college events data:", error);
        return;
    }
    
    let currentPage = 1;
    let showAllEvents = false;
    const itemsPerPage = 6;
    const fallbackImage = "images/UCC2.png";
    const newsLabel = "COLLEGE NEWS";
    const featuredNews = document.getElementById("featuredNews");
    const newsGrid = document.getElementById("newsGrid");
    const newsPagination = document.getElementById("newsPagination");
    const newsPrev = document.getElementById("newsPrev");
    const newsNext = document.getElementById("newsNext");
    const viewAllEvents = document.getElementById("viewAllEvents");
    const eventsCount = document.getElementById("eventsCount");
    const moreEventsTitle = document.getElementById("moreEventsTitle");
    const newsModal = document.getElementById("newsModal");
    const newsModalTitle = document.getElementById("newsModalTitle");
    const newsModalCategory = document.getElementById("newsModalCategory");
    const newsModalCategoryBadge = document.getElementById("newsModalCategoryBadge");
    const newsModalDate = document.getElementById("newsModalDate");
    const newsModalImage = document.getElementById("newsModalImage");
    const newsModalImageLink = document.getElementById("newsModalImageLink");
    const newsModalDescription = document.getElementById("newsModalDescription");
    const newsImageLightbox = document.getElementById("newsImageLightbox");
    const newsLightboxImage = document.getElementById("newsLightboxImage");

    if (!featuredNews || !newsGrid || !newsPagination || !newsPrev || !newsNext || !viewAllEvents || !eventsCount || !moreEventsTitle || !newsModal || !newsModalTitle || !newsModalImage || !newsModalImageLink || !newsModalDescription || !newsImageLightbox || !newsLightboxImage) return;

    function getFilteredEvents() {
        return eventsData;
    }

    function getExcerpt(text, limit = 125) {
        const value = String(text || "").trim();
        return value.length <= limit ? value : value.substring(0, limit).trim() + "...";
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

    // Accept the storage image endpoint, the exact fallback, or an absolute HTTP(S) image URL.
    function getSafeImagePath(path) {
        if (typeof path !== "string" || path.includes("..")) return fallbackImage;
        if (path === fallbackImage || /^image\.php\?f=[A-Za-z0-9._%~-]+$/.test(path)) return path;
        if (/^https?:\/\//i.test(path)) return path;
        return fallbackImage;
    }

    function createEventCard(event) {
        const eventIndex = eventsData.indexOf(event);
        return `
            <article class="news-card college-event-card">
                <div class="news-card-image">
                    <img src="${escapeHTML(getSafeImagePath(event.image))}" alt="${escapeHTML(event.title)}" loading="lazy">
                </div>
                <div class="news-card-content">
                    <h3>${escapeHTML(event.title)}</h3>
                    <div class="news-card-date"><span>▣</span>${escapeHTML(event.date)}</div>
                    <div class="news-card-line"></div>
                    <button type="button" class="news-card-button" data-event-index="${eventIndex}">READ MORE <span>→</span></button>
                </div>
            </article>`;
    }

    function renderFeatured() {
        const items = getFilteredEvents();
        if (!items.length) {
            featuredNews.innerHTML = `
                <div class="news-empty college-events-empty">
                    <h3>No events found</h3>
                    <p>There are currently no published events or news for this selection.</p>
                </div>`;
            return;
        }
        const event = items[0];
        const eventIndex = eventsData.indexOf(event);
        featuredNews.innerHTML = `
            <div class="news-featured-image">
                <img src="${escapeHTML(getSafeImagePath(event.image))}" alt="${escapeHTML(event.title)}">
            </div>
            <div class="news-featured-content">
                <div class="news-featured-glow"></div>
                <span class="news-badge">${newsLabel}</span>
                <h2>${escapeHTML(event.title)}</h2>
                <div class="news-meta"><span>▣</span><strong>${escapeHTML(event.date)}</strong></div>
                <div class="news-gold-divider"></div>
                <p>${escapeHTML(getExcerpt(event.excerpt || event.title))}</p>
                <button type="button" class="news-read-more news-featured-read-more" data-event-index="${eventIndex}">READ MORE <span>→</span></button>
            </div>`;
        setImageFallbacks(featuredNews);
    }

    function renderCards() {
        const items = getFilteredEvents();
        const cardItems = items.slice(1);
        const totalPages = Math.ceil(cardItems.length / itemsPerPage);
        eventsCount.textContent = `${items.length} ${items.length === 1 ? "EVENT" : "EVENTS"}`;
        if (showAllEvents) {
            newsGrid.innerHTML = cardItems.length
                ? cardItems.map(createEventCard).join("")
                : `<div class="news-empty"><h3>No more events</h3><p>There are no additional events in this selection.</p></div>`;
            newsPagination.innerHTML = "";
            setArrowsVisible(false);
            setImageFallbacks(newsGrid);
            return;
        }
        if (currentPage > totalPages) currentPage = totalPages || 1;
        const start = (currentPage - 1) * itemsPerPage;
        const pageItems = cardItems.slice(start, start + itemsPerPage);
        newsGrid.innerHTML = pageItems.length
            ? pageItems.map(createEventCard).join("")
            : `<div class="news-empty"><h3>No more events</h3><p>The featured item is currently the only event for this selection.</p></div>`;
        renderPagination(totalPages);
        updateArrowButtons(totalPages);
        setImageFallbacks(newsGrid);
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

    function renderPagination(totalPages) {
        newsPagination.innerHTML = "";
        if (showAllEvents || totalPages <= 1) return;
        const previous = document.createElement("button");
        previous.type = "button";
        previous.className = "news-page-arrow";
        previous.textContent = "‹ PREVIOUS";
        previous.disabled = currentPage === 1;
        previous.addEventListener("click", () => changePage(currentPage - 1));
        newsPagination.appendChild(previous);
        for (let page = 1; page <= totalPages; page++) {
            const button = document.createElement("button");
            button.type = "button";
            button.className = "news-page-number" + (page === currentPage ? " active" : "");
            button.textContent = page;
            button.setAttribute("aria-label", `Go to page ${page}`);
            button.setAttribute("aria-current", page === currentPage ? "page" : "false");
            button.addEventListener("click", () => changePage(page));
            newsPagination.appendChild(button);
        }
        const next = document.createElement("button");
        next.type = "button";
        next.className = "news-page-arrow";
        next.textContent = "NEXT ›";
        next.disabled = currentPage === totalPages;
        next.addEventListener("click", () => changePage(currentPage + 1));
        newsPagination.appendChild(next);
    }

    function changePage(page) {
        const totalPages = Math.ceil(Math.max(0, getFilteredEvents().length - 1) / itemsPerPage);
        if (page < 1 || page > totalPages) return;
        currentPage = page;
        renderCards();
        scrollToEvents();
    }

    // style.display is used because the CSS display rule overrides the hidden attribute
    function setArrowsVisible(visible) {
        newsPrev.style.display = visible ? "" : "none";
        newsNext.style.display = visible ? "" : "none";
    }

    function updateArrowButtons(totalPages) {
        setArrowsVisible(!(showAllEvents || totalPages <= 1));
        newsPrev.disabled = currentPage === 1;
        newsNext.disabled = currentPage === totalPages;
    }

    function scrollToEvents() {
        const page = document.querySelector(".college-events-page");
        if (!page) return;
        window.scrollTo({
            top: page.getBoundingClientRect().top + window.pageYOffset - 20,
            behavior: "smooth"
        });
    }

    function updateViewAllButton() {
        // Hide the button when everything already fits on the first page
        const hasMore = getFilteredEvents().slice(1).length > itemsPerPage;
        viewAllEvents.style.display = (!showAllEvents && !hasMore) ? "none" : "";
        viewAllEvents.innerHTML = showAllEvents
            ? "<span>▤</span> SHOW LESS EVENTS"
            : "<span>▤</span> VIEW ALL EVENTS";
    }

    function openEventModal(index) {
        const event = eventsData[index];
        if (!event) return;
        const image = getSafeImagePath(event.image);
        newsModalTitle.textContent = event.title;
        newsModalCategory.textContent = newsLabel;
        newsModalCategoryBadge.textContent = newsLabel;
        newsModalDate.textContent = event.date;
        newsModalImage.src = image;
        newsModalImage.alt = event.title;
        newsModalImageLink.href = image;
        newsModalImageLink.dataset.image = image;
        // textContent keeps the post text safe; pre-line keeps its line breaks
        newsModalDescription.textContent = String(event.content || "").trim() || "No article content available.";
        newsModalDescription.style.whiteSpace = "pre-line";
        setImageFallbacks(newsModal);
        newsModal.classList.add("active");
        newsModal.setAttribute("aria-hidden", "false");
        document.body.classList.add("news-modal-open");
        const closeButton = newsModal.querySelector(".news-modal-close");
        if (closeButton) closeButton.focus();
    }

    function closeEventModal() {
        closeEventImageLightbox();
        newsModal.classList.remove("active");
        newsModal.setAttribute("aria-hidden", "true");
        document.body.classList.remove("news-modal-open");
    }

    function openEventImageLightbox() {
        const image = newsModalImageLink.dataset.image;
        if (!image) return;
        newsLightboxImage.src = image;
        newsLightboxImage.alt = newsModalImage.alt;
        newsImageLightbox.classList.add("active");
        newsImageLightbox.setAttribute("aria-hidden", "false");
        const closeButton = newsImageLightbox.querySelector(".news-image-lightbox-close");
        if (closeButton) closeButton.focus();
    }

    function closeEventImageLightbox() {
        newsImageLightbox.classList.remove("active");
        newsImageLightbox.setAttribute("aria-hidden", "true");
        newsLightboxImage.removeAttribute("src");
    }

    featuredNews.addEventListener("click", event => {
        const trigger = event.target.closest("[data-event-index]");
        if (trigger) openEventModal(Number(trigger.dataset.eventIndex));
    });
    newsGrid.addEventListener("click", event => {
        const trigger = event.target.closest("[data-event-index]");
        if (trigger) openEventModal(Number(trigger.dataset.eventIndex));
    });
    newsModalImageLink.addEventListener("click", event => {
        event.preventDefault();
        openEventImageLightbox();
    });
    newsImageLightbox.addEventListener("click", event => {
        if (event.target.closest("[data-news-image-close]")) closeEventImageLightbox();
    });
    newsPrev.addEventListener("click", () => changePage(currentPage - 1));
    newsNext.addEventListener("click", () => changePage(currentPage + 1));
    viewAllEvents.addEventListener("click", () => {
        if (showAllEvents) {
            showAllEvents = false;
            currentPage = 1;
        } else {
            currentPage = 1;
            showAllEvents = true;
        }
        updateViewAllButton();
        renderFeatured();
        renderCards();
        if (!showAllEvents) scrollToEvents();
    });
    newsModal.addEventListener("click", event => {
        if (event.target.closest("[data-news-modal-close]")) closeEventModal();
    });
    document.addEventListener("keydown", event => {
        if (event.key === "Escape") {
            if (newsImageLightbox.classList.contains("active")) {
                closeEventImageLightbox();
            } else if (newsModal.classList.contains("active")) {
                closeEventModal();
            }
        }
    });
    renderFeatured();
    renderCards();
    updateViewAllButton();
    if (window.lucide) window.lucide.createIcons();
});
