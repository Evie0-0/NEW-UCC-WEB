
document.addEventListener("DOMContentLoaded", function () {
	/* LUCIDE ICONS */
	if (window.lucide) {
		window.lucide.createIcons();
	}
	/* REVEAL ELEMENTS ON SCROLL */
	if ("IntersectionObserver" in window) {
		const revealObserver = new IntersectionObserver(function (entries, observer) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting) return;
				entry.target.classList.add("show");
				observer.unobserve(entry.target);
			});
		}, { threshold: 0.12 });
		document.querySelectorAll(".reveal").forEach(function (el) {
			revealObserver.observe(el);
		});
	} else {
		document.querySelectorAll(".reveal").forEach(function (el) {
			el.classList.add("show");
		});
	}


	/* PROGRAM FILTERS */
	const tabs = document.querySelectorAll(".program-tab");
	const filterCards = document.querySelectorAll(".program-card");
	tabs.forEach(function (tab) {
		tab.addEventListener("click", function () {
			tabs.forEach(function (item) {
				item.classList.remove("active");
			});
			tab.classList.add("active");
			const filter = tab.dataset.filter;
			filterCards.forEach(function (card) {
				const matches = filter === "all" || card.dataset.category === filter;
				card.style.display = matches ? "" : "none";
			});
		});
	});

	
	/* ANIMATED STATISTICS */
	const stats = document.querySelectorAll("[data-count]");
	if ("IntersectionObserver" in window) {
		const statObserver = new IntersectionObserver(function (entries, observer) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting) return;
				const target = Number(entry.target.dataset.count);
				const duration = target > 1000 ? 1300 : 900;
				const start = performance.now();
				function animate(now) {
					const progress = Math.min((now - start) / duration, 1);
					const eased = 1 - Math.pow(1 - progress, 3);
					const value = Math.floor(target * eased);
					entry.target.textContent = target === 1971 ? String(value) : value.toLocaleString();
					if (progress < 1) {
						requestAnimationFrame(animate);
					}
				}
				requestAnimationFrame(animate);
				observer.unobserve(entry.target);
			});
		}, { threshold: 0.6 });
		stats.forEach(function (stat) {
			statObserver.observe(stat);
		});
	} else {
		stats.forEach(function (stat) {
			const target = Number(stat.dataset.count);
			stat.textContent = target === 1971 ? String(target) : target.toLocaleString();
		});
	}
	/* BACK TO TOP */
	const backTop = document.getElementById("backTop");
	if (backTop) {
		window.addEventListener("scroll", function () {
			backTop.classList.toggle("show", window.scrollY > 500);
		});
		backTop.addEventListener("click", function () {
			window.scrollTo({ top: 0, behavior: "smooth" });
		});
	}
	/* MOBILE NAVIGATION */
	const nav = document.getElementById("mainNav");
	const menuToggle = document.querySelector(".menu-toggle");
	if (menuToggle && nav) {
		menuToggle.addEventListener("click", function () {
			nav.classList.toggle("open");
		});
	}
	/* MOBILE DROPDOWNS */
	document.querySelectorAll(".nav-dropdown > button").forEach(function (button) {
		button.addEventListener("click", function () {
			if (window.innerWidth <= 760) {
				button.parentElement.classList.toggle("open");
			}
		});
	});

	





/* PROGRAM EVENTS CAROUSEL */
const programWrapper = document.querySelector(".program-events-wrapper");
const programGrid = document.getElementById("programEvents");
const programPrev = document.getElementById("programPrev");
const programNext = document.getElementById("programNext");
const programDots = document.getElementById("programEventDots");
if (programWrapper && programGrid && programPrev && programNext && programDots) {
	const programCards = Array.from(programGrid.querySelectorAll(".feature-card"));
	let currentIndex = 0;
	let cardsPerPage = 4;
	let resizeTimeout;
	function getCardsPerPage() {
		if (window.innerWidth <= 650) return 1;
		if (window.innerWidth <= 1100) return 2;
		return 4;
	}
	function updateCarousel() {
		if (!programCards.length) {
			programPrev.disabled = true;
			programNext.disabled = true;
			programDots.innerHTML = "";
			return;
		}
		cardsPerPage = getCardsPerPage();
		const maxIndex = Math.max(0, programCards.length - cardsPerPage);
		currentIndex = Math.max(0, Math.min(currentIndex, maxIndex));
		const firstCard = programCards[0];
		const cardStyle = window.getComputedStyle(programGrid);
		const gap = parseFloat(cardStyle.columnGap) || 0;
		const cardWidth = firstCard.getBoundingClientRect().width;
		const offset = currentIndex * (cardWidth + gap);
		programGrid.style.transform = `translateX(-${offset}px)`;
		programDots.innerHTML = "";
		for (let index = 0; index <= maxIndex; index++) {
			const dot = document.createElement("button");
			dot.type = "button";
			dot.className = "program-event-dot" + (index === currentIndex ? " active" : "");
			dot.setAttribute("aria-label", `Show program cards starting from card ${index + 1}`);
			dot.setAttribute("aria-current", index === currentIndex ? "true" : "false");
			dot.addEventListener("click", function() {
				currentIndex = index;
				updateCarousel();
			});
			programDots.appendChild(dot);
		}
		programPrev.disabled = currentIndex === 0;
		programNext.disabled = currentIndex >= maxIndex;
		programGrid.setAttribute("aria-label", `Program cards starting from card ${currentIndex + 1}`);
	}
	programPrev.addEventListener("click", function() {
		if (currentIndex > 0) {
			currentIndex--;
			updateCarousel();
		}
	});
	programNext.addEventListener("click", function() {
		const maxIndex = Math.max(0, programCards.length - getCardsPerPage());
		if (currentIndex < maxIndex) {
			currentIndex++;
			updateCarousel();
		}
	});
	window.addEventListener("resize", function() {
		clearTimeout(resizeTimeout);
		resizeTimeout = setTimeout(updateCarousel, 100);
	});
	updateCarousel();
}



});
