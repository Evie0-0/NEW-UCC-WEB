(function () {
	const {fillMeta, $, esc, mapsUrl, isUrl, toast, openModal, closeModal, confirmDelete, list, icons, initMenu} = UCC;
	const API = window.CONTACT_API;
	const items = [];
	let editingId = null;
	let saving = false;
	let deleting = false;
	const formModal = $("contactFormModal");
	const previewModal = $("contactPreviewModal");
	const form = $("contactForm");
	const submitButton = form.querySelector('button[type="submit"]');
	const statusClass = status => ({
		Active: "contact-status-active",
		Hidden: "contact-status-hidden"
	}[status] || "contact-status-hidden");
	function parseDate(value) {
		if (!value) return null;
		const date = new Date(String(value).replace(" ", "T"));
		return Number.isNaN(date.getTime()) ? null : date.getTime();
	}
	function normalizeContact(row) {
		return {
			id: Number(row.id),
			name: row.name || "",
			email: row.email || "",
			number: row.phone_number || "",
			category: row.category || "Offices",
			status: String(row.status).toLowerCase() === "active" ? "Active" : "Hidden",
			location: row.location || "",
			createdBy: row.author_username || (row.author_id ? "User #" + row.author_id : "—"),
			updatedBy: row.editor_username || (row.editor_id ? "User #" + row.editor_id : ""),
			createdAt: parseDate(row.created_at),
			updatedAt: parseDate(row.updated_at)
		};
	}
	async function request(url, options = {}) {
		const response = await fetch(url, {
			credentials: "same-origin",
			cache: "no-store",
			...options
		});
		const responseText = await response.text();
		let result;
		try {
			result = JSON.parse(responseText);
		} catch (error) {
			throw new Error("The server returned an invalid response. Check the PHP error log.");
		}
		if (!response.ok || result.success === false || result.status === "error") {
			throw new Error(result.message || "The request could not be completed.");
		}
		return result;
	}
	function extractRecords(result) {
		if (Array.isArray(result)) return result;
		if (Array.isArray(result.data)) return result.data;
		if (Array.isArray(result.data?.contacts)) return result.data.contacts;
		if (Array.isArray(result.contacts)) return result.contacts;
		return null;
	}
	async function loadContacts() {
		try {
			const result = await request(API.fetch);
			const records = extractRecords(result);
			if (!records) {
				throw new Error("Unexpected contacts response format.");
			}
			items.splice(0, items.length, ...records.map(normalizeContact));
			table.reset();
		} catch (error) {
			console.error("Contacts fetch error:", error);
			toast(error.message);
		}
	}
	function toggleLocation() {
		$("campusLocationField").style.display = $("contactCategory").value === "Campus" ? "" : "none";
	}
	const table = list({
		items: () => items,
		tbody: $("contactTableBody"),
		search: $("contactSearch"),
		filter: $("statusFilter"),
		count: $("contactCountLabel"),
		pageSize: 10,
		pager: {
			nav: $("contactsPagination"),
			prev: $("contactsPrevPage"),
			next: $("contactsNextPage"),
			numbers: $("contactsPageNumbers"),
			total: $("contactsPageTotal")
		},
		cols: 6,
		emptyText: "No contacts found.",
		countLabel: n => n + (n === 1 ? " Contact" : " Contacts"),
		match: (it, q) => {
			const category = $("categoryFilter").value;
			return (!category || it.category === category) &&
				(it.name + " " + it.email + " " + it.number + " " + it.category + " " + it.location).toLowerCase().includes(q);
		},
		filterFn: (it, filter) => !filter || it.status === filter,
		defaultSort: (a, b) => b.id - a.id,
		row: it => `
			<tr data-id="${it.id}">
				<td><span class="contact-cell-ellipsis contact-name" title="${esc(it.name)}">${esc(it.name)}</span></td>
				<td>${esc(it.email)}</td>
				<td>${esc(it.number)}</td>
				<td><span class="contact-category">${esc(it.category)}</span></td>
				<td><span class="contact-status ${statusClass(it.status)}">${esc(it.status)}</span></td>
				<td>
					<div class="contact-actions">
						<button type="button" class="contact-action-btn" data-action="preview" title="View contact" aria-label="View ${esc(it.name)}"><i data-lucide="eye"></i></button>
						<button type="button" class="contact-action-btn contact-action-edit" data-action="edit" title="Edit contact" aria-label="Edit ${esc(it.name)}"><i data-lucide="pencil"></i></button>
						<button type="button" class="contact-action-btn contact-action-delete" data-action="delete" title="Delete contact" aria-label="Delete ${esc(it.name)}"><i data-lucide="trash-2"></i></button>
					</div>
				</td>
			</tr>`,
		onAction: async (action, id) => {
			const it = items.find(item => item.id === Number(id));
			if (!it) return;
			if (action === "preview") {
				showPreview(it);
			} else if (action === "edit") {
				openForm(it);
			} else if (action === "delete" && !deleting) {
				const confirmed = await confirmDelete("Delete this contact?", it.name);
				if (confirmed) {
					await deleteContact(it.id);
				}
			}
		}
	});
	$("categoryFilter").addEventListener("change", () => table.reset());
	function openForm(it) {
		editingId = it ? it.id : null;
		$("contactFormTitle").textContent = it ? "Edit Contact" : "Add Contact";
		$("saveContactLabel").textContent = it ? "Save Changes" : "Save Contact";
		$("contactCategory").value = it ? it.category : "Campus";
		$("contactStatus").value = it ? it.status : "Active";
		$("contactName").value = it ? it.name : "";
		$("contactEmail").value = it ? it.email : "";
		$("contactNumber").value = it ? it.number : "";
		$("contactLocation").value = it ? it.location : "";
		toggleLocation();
		openModal(formModal);
		$("contactName").focus();
	}
	function closeForm() {
		if (saving) return;
		closeModal(formModal);
		editingId = null;
		form.reset();
		toggleLocation();
	}
	function showPreview(it) {
		$("previewContactName").textContent = it.name || "Contact name";
		const shown = it.status;
		const badge = $("previewContactStatus");
		badge.textContent = shown;
		badge.className = "contact-status " + statusClass(shown);
		$("previewContactCategory").textContent = it.category;
		$("previewContactEmail").textContent = it.email || "—";
		$("previewContactNumber").textContent = it.number || "—";
		const section = $("previewLocationSection");
		if (it.category === "Campus" && it.location) {
			section.hidden = false;
			$("previewContactLocation").textContent = it.location;
			const location = it.location.trim();
			const validHttpsUrl = /^https:\/\//i.test(location) && isUrl(location);
			$("previewContactMapLink").href = validHttpsUrl ? location : mapsUrl(location);
			$("previewMapFrame").innerHTML = validHttpsUrl ? "" :
				'<iframe title="Campus map" width="100%" height="220" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://www.google.com/maps?q=' + encodeURIComponent(location) + '&output=embed"></iframe>';
		} else {
			section.hidden = true;
			$("previewContactMapLink").removeAttribute("href");
			$("previewMapFrame").innerHTML = "";
		}
		fillMeta(it);
		openModal(previewModal);
	}
	const readForm = () => ({
		category: $("contactCategory").value,
		status: $("contactStatus").value,
		name: $("contactName").value.trim(),
		email: $("contactEmail").value.trim(),
		number: $("contactNumber").value.trim(),
		location: $("contactLocation").value.trim()
	});
	async function saveContact(event) {
		event.preventDefault();
		if (saving || !form.reportValidity()) return;
		const data = readForm();
		if (!data.name || !data.email || !data.number) {
			toast("Please complete all required fields.");
			return;
		}
		if (data.category !== "Campus") {
			data.location = "";
		}
		const body = new FormData();
		body.append("id", editingId === null ? "0" : String(editingId));
		body.append("name", data.name);
		body.append("email", data.email);
		body.append("phone_number", data.number);
		body.append("category", data.category);
		body.append("location", data.location);
		body.append("status", data.status === "Active" ? "active" : "inactive");
		body.append(API.csrfKey, API.csrfToken);
		const wasEditing = editingId !== null;
		try {
			saving = true;
			submitButton.disabled = true;
			await request(API.save, {
				method: "POST",
				body
			});
			saving = false;
			closeForm();
			await loadContacts();
			toast(wasEditing ? "Contact successfully updated." : "Contact successfully added.");
		} catch (error) {
			console.error("Save contact error:", error);
			toast(error.message);
		} finally {
			saving = false;
			submitButton.disabled = false;
		}
	}
	async function deleteContact(id) {
		if (deleting) return;
		const body = new FormData();
		body.append("id", String(id));
		body.append(API.csrfKey, API.csrfToken);
		try {
			deleting = true;
			await request(API.delete, {
				method: "POST",
				body
			});
			await loadContacts();
			toast("Contact successfully deleted.");
		} catch (error) {
			console.error("Delete contact error:", error);
			toast(error.message);
		} finally {
			deleting = false;
		}
	}
	$("openContactForm").addEventListener("click", () => openForm(null));
	$("contactCategory").addEventListener("change", toggleLocation);
	$("previewContactBtn").addEventListener("click", () => {
		const existing = editingId !== null ? items.find(item => item.id === editingId) : null;
		const data = readForm();
		if (data.category !== "Campus") data.location = "";
		showPreview({
			id: editingId,
			createdBy: existing ? existing.createdBy : "Admin",
			createdAt: existing ? existing.createdAt : Date.now(),
			updatedBy: existing ? existing.updatedBy : "",
			updatedAt: existing ? existing.updatedAt : null,
			...data
		});
	});
	form.addEventListener("submit", saveContact);
	document.querySelectorAll("[data-close-form]").forEach(button => {
		button.addEventListener("click", closeForm);
	});
	document.querySelectorAll("[data-close-preview]").forEach(button => {
		button.addEventListener("click", () => closeModal(previewModal));
	});
	document.addEventListener("keydown", event => {
		if (event.key !== "Escape") return;
		if (previewModal.classList.contains("open")) {
			closeModal(previewModal);
		} else if (formModal.classList.contains("open")) {
			closeForm();
		}
	});
	toggleLocation();
	table.render();
	initMenu();
	icons();
	loadContacts();
})();