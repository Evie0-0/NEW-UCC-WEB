document.addEventListener("DOMContentLoaded", function() {
	if (window.lucide) {
		lucide.createIcons();
	}
	const modal = document.getElementById("programModal");
	const modalTitle = document.getElementById("programModalTitle");
	const modalVision = document.getElementById("programModalVision");
	const modalMission = document.getElementById("programModalMission");
	const modalNumber = document.getElementById("programModalNumber");
	const programLinks = document.querySelectorAll(".program-read-more");
	if (!modal) {
		return;
	}
	function openProgramModal(card) {
		const title = card.querySelector("h3");
		const number = card.querySelector(".cba-program-number");
		if (modalTitle) {
			modalTitle.textContent = title ? title.textContent.trim() : "Academic Program";
		}
		if (modalVision) {
			modalVision.textContent = card.dataset.vision || "Program vision information has not been provided yet.";
		}
		if (modalMission) {
			modalMission.textContent = card.dataset.mission || "Program mission information has not been provided yet.";
		}
		if (modalNumber) {
			modalNumber.textContent = number ? number.textContent.trim() : "";
		}
		modal.classList.add("show");
		modal.setAttribute("aria-hidden", "false");
		document.body.classList.add("program-modal-open");
	}
	function closeProgramModal() {
		modal.classList.remove("show");
		modal.setAttribute("aria-hidden", "true");
		document.body.classList.remove("program-modal-open");
	}
	programLinks.forEach(function(link) {
		link.addEventListener("click", function(event) {
			event.preventDefault();
			const card = link.closest(".cba-program-card");
			if (card) {
				openProgramModal(card);
			}
		});
	});
	modal.querySelectorAll("[data-modal-close]").forEach(function(button) {
		button.addEventListener("click", closeProgramModal);
	});
	document.addEventListener("keydown", function(event) {
		if (event.key === "Escape" && modal.classList.contains("show")) {
			closeProgramModal();
		}
	});
});


