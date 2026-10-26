(function () {
	const $ = id => document.getElementById(id);
	const day = 86400000;
	const dayStr = offset => { const d = new Date(Date.now() + offset * day); return d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0") + "-" + String(d.getDate()).padStart(2, "0"); };

	const news = [
		{id:1, unit:"College of Arts and Sciences", title:"CAS Holds Annual Research Congress", excerpt:"Faculty and students presented their latest research projects during the college's annual congress.", image:"", publishDate:dayStr(-5), expiryDate:dayStr(-1), status:"Published", createdBy:"Admin", updatedBy:"Admin", createdAt:Date.now() - 6 * day},
		{id:2, unit:"College of Business and Accountancy", title:"CBA Students Win Regional Business Plan Competition", excerpt:"A team of business students took first place at the regional business plan competition.", image:"", publishDate:dayStr(-3), expiryDate:dayStr(30), status:"Published", createdBy:"Admin", updatedBy:"Admin", createdAt:Date.now() - 4 * day},
		{id:3, unit:"College of Education", title:"Education Faculty Launches Teaching Workshop Series", excerpt:"A series of workshops on modern teaching strategies will begin next month for all education students.", image:"", publishDate:dayStr(4), expiryDate:null, status:"Draft", createdBy:"Admin", updatedBy:"Admin", createdAt:Date.now() - 2 * day}
	];
	let nextId = 4, editingId = null, imageData = "", page = 1;
	const pageSize = 10;

	const tbody = $("newsTableBody"), search = $("newsSearch"), unitFilter = $("newsUnitFilter"), statusFilter = $("newsStatusFilter"), sortSel = $("newsSort");
	const formModal = $("newsFormModal"), previewModal = $("newsPreviewModal"), form = $("newsForm"), imageFile = $("newsImageFile");
	const dateInput = $("newsPublishDate"), expiryInput = $("newsExpiryDate");

	const esc = v => String(v).replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;").replaceAll('"', "&quot;").replaceAll("'", "&#039;");
	const fmtDate = ts => ts ? new Date(ts).toLocaleDateString("en-US", {year:"numeric", month:"short", day:"numeric"}) : "—";
	const fmtDay = d => d ? new Date(d + "T00:00:00").toLocaleDateString("en-US", {year:"numeric", month:"short", day:"numeric"}) : "—";
	const fmtExpiry = d => d ? fmtDay(d) : "No expiry";
	const icons = () => { if (window.lucide) lucide.createIcons(); };

	// A published post whose expiry date has passed is shown as "Expired".
	const effectiveStatus = n => (n.status === "Published" && n.expiryDate && n.expiryDate < dayStr(0)) ? "Expired" : n.status;
	// Keep the expiry picker from allowing a date before the publish date.
	function syncExpiryMin() { if (expiryInput) expiryInput.min = (dateInput && dateInput.value) || ""; }
	if (dateInput) dateInput.addEventListener("change", syncExpiryMin);

	const pager = {
		nav: $("newsPagination"), prev: $("newsPrevPage"), next: $("newsNextPage"),
		numbers: $("newsPageNumbers"), total: $("newsPageTotal")
	};

	function pageList(cur, last) {
		if (last <= 7) return Array.from({length: last}, (_, i) => i + 1);
		const out = [1], s = Math.max(2, cur - 1), e = Math.min(last - 1, cur + 1);
		if (s > 2) out.push("...");
		for (let i = s; i <= e; i++) out.push(i);
		if (e < last - 1) out.push("...");
		out.push(last);
		return out;
	}

	function renderPager(totalRows) {
		const last = Math.max(1, Math.ceil(totalRows / pageSize));
		pager.nav.hidden = totalRows === 0;
		pager.numbers.innerHTML = "";
		pageList(page, last).forEach(p => {
			if (p === "...") {
				const s = document.createElement("span");
				s.className = "unitnews-page-ellipsis"; s.textContent = "…";
				pager.numbers.appendChild(s);
				return;
			}
			const b = document.createElement("button");
			b.type = "button";
			b.className = "unitnews-page-btn" + (p === page ? " active" : "");
			b.textContent = p;
			b.setAttribute("aria-label", "Page " + p);
			if (p === page) b.setAttribute("aria-current", "page");
			b.addEventListener("click", () => { page = p; render(); });
			pager.numbers.appendChild(b);
		});
		pager.prev.disabled = page <= 1;
		pager.next.disabled = page >= last;
		pager.total.textContent = last + (last === 1 ? " page" : " pages");
	}

	const todayStr = () => dayStr(0);
	const statusClass = s => s === "Published" ? "unitnews-status-published" : s === "Expired" ? "unitnews-status-expired" : "unitnews-status-draft";

	function toast(message) {
		const t = $("newsToast"); t.textContent = message; t.classList.add("show");
		clearTimeout(toast.timer); toast.timer = setTimeout(() => t.classList.remove("show"), 2400);
	}

	function render() {
		const q = search.value.trim().toLowerCase(), un = unitFilter.value, st = statusFilter.value, mode = sortSel.value;
		const list = news.filter(n =>
			(n.title + " " + n.unit + " " + n.excerpt).toLowerCase().includes(q) &&
			(un === "All" || n.unit === un) &&
			(st === "All" || effectiveStatus(n) === st)
		);
		list.sort((a, b) => {
			if (mode === "oldest") return a.createdAt - b.createdAt;
			if (mode === "title-asc") return a.title.localeCompare(b.title);
			if (mode === "title-desc") return b.title.localeCompare(a.title);
			// Posts with no expiry date always go last.
			if (mode === "expiry-asc") return (a.expiryDate || "9999-12-31").localeCompare(b.expiryDate || "9999-12-31");
			if (mode === "expiry-desc") return (b.expiryDate || "0000-01-01").localeCompare(a.expiryDate || "0000-01-01");
			return b.createdAt - a.createdAt;
		});
		const last = Math.max(1, Math.ceil(list.length / pageSize));
		if (page > last) page = last;
		const visible = list.slice((page - 1) * pageSize, page * pageSize);

		tbody.innerHTML = visible.map(n => {
			const shownStatus = effectiveStatus(n);
			return `
			<tr data-id="${n.id}">
				<td><div class="unitnews-thumb">${n.image ? `<img src="${n.image}" alt="">` : `<i data-lucide="image" aria-hidden="true"></i>`}</div></td>
				<td><span class="unitnews-cell-ellipsis unitnews-title-text" title="${esc(n.title)}">${esc(n.title)}</span></td>
				<td><span class="unitnews-cell-ellipsis" title="${esc(n.unit)}">${esc(n.unit)}</span></td>
				<td><span class="unitnews-excerpt" title="${esc(n.excerpt)}">${esc(n.excerpt)}</span></td>
				<td><span class="unitnews-cell-ellipsis">${esc(fmtDay(n.publishDate))}</span></td>
				<td><span class="unitnews-cell-ellipsis">${esc(fmtExpiry(n.expiryDate))}</span></td>
				<td><span class="unitnews-status ${statusClass(shownStatus)}">${esc(shownStatus)}</span></td>
				<td><div class="unitnews-actions">
					<button class="unitnews-action-btn" data-action="preview" type="button" title="Preview" aria-label="Preview ${esc(n.title)}"><i data-lucide="eye"></i></button>
					<button class="unitnews-action-btn unitnews-action-edit" data-action="edit" type="button" title="Edit" aria-label="Edit ${esc(n.title)}"><i data-lucide="pencil"></i></button>
					<button class="unitnews-action-btn unitnews-action-delete" data-action="delete" type="button" title="Delete" aria-label="Delete ${esc(n.title)}"><i data-lucide="trash-2"></i></button>
				</div></td>
			</tr>`;
		}).join("");

		$("newsCount").textContent = list.length + (list.length === 1 ? " item" : " items");
		$("newsEmpty").hidden = list.length !== 0;
		renderPager(list.length);
		icons();
	}

	function setImage(src) {
		imageData = src || "";
		const prev = $("newsImagePreview"), ph = $("newsImagePlaceholder"), rm = $("removeNewsImage");
		if (src) {
			prev.src = src; prev.hidden = false; prev.style.display = "block";
			ph.hidden = true; ph.style.display = "none"; rm.hidden = false;
		} else {
			prev.removeAttribute("src"); prev.hidden = true; prev.style.display = "none";
			ph.hidden = false; ph.style.display = "flex"; rm.hidden = true;
		}
	}
	function readImage() {
		const f = imageFile.files && imageFile.files[0]; if (!f) return;
		if (!f.type.startsWith("image/")) { toast("Please choose an image file."); imageFile.value = ""; return; }
		const r = new FileReader(); r.onload = e => setImage(e.target.result); r.readAsDataURL(f);
	}

	function openForm(n) {
		editingId = n ? n.id : null;
		$("newsFormTitle").textContent = n ? "Edit Unit Posts" : "Add Unit Posts";
		$("newsUnit").value = n ? n.unit : "";
		$("newsTitle").value = n ? n.title : "";
		$("newsExcerpt").value = n ? n.excerpt : "";
		$("newsPublishDate").value = n ? n.publishDate : todayStr();
		if (expiryInput) expiryInput.value = n && n.expiryDate ? n.expiryDate : "";
		syncExpiryMin();
		$("newsStatus").value = n ? n.status : "Draft";
		setImage(n ? n.image : "");
		formModal.classList.add("open"); formModal.setAttribute("aria-hidden", "false");
		icons(); $("newsUnit").focus();
	}
	function closeForm() {
		formModal.classList.remove("open"); formModal.setAttribute("aria-hidden", "true");
		editingId = null; form.reset(); imageFile.value = ""; setImage("");
		syncExpiryMin();
	}

	function openPreview(n) {
		$("previewNewsUnit").textContent = n.unit || "Unit name";
		$("previewNewsTitle").textContent = n.title || "News title";
		const shown = effectiveStatus(n), badge = $("previewNewsStatus");
		badge.textContent = shown; badge.className = "unitnews-status " + statusClass(shown);
		$("previewNewsDate").textContent = n.publishDate ? "Publish date: " + fmtDay(n.publishDate) : "—";
		const expiryEl = $("previewNewsExpiry");
		if (expiryEl) expiryEl.textContent = n.expiryDate ? "Expiry date: " + fmtDay(n.expiryDate) : "No expiry date";
		$("previewNewsExcerpt").textContent = n.excerpt || "";

		const img = $("previewNewsImage"), ph = $("previewNewsImagePlaceholder");
		if (n.image) { img.src = n.image; img.hidden = false; ph.hidden = true; }
		else { img.removeAttribute("src"); img.hidden = true; ph.hidden = false; }

		$("newsCreatedBy").textContent = n.createdBy || "Admin";
		$("newsDateCreated").textContent = fmtDate(n.createdAt);
		$("newsLastUpdatedBy").textContent = n.updatedBy || n.createdBy || "—";
		previewModal.classList.add("open"); previewModal.setAttribute("aria-hidden", "false"); icons();
	}
	function closePreview() { previewModal.classList.remove("open"); previewModal.setAttribute("aria-hidden", "true"); }

	async function deleteNews(n) {
		let confirmed;
		if (window.Swal) {
			const res = await Swal.fire({title:"Delete this news item?", text:n.title, icon:"warning", showCancelButton:true, confirmButtonText:"Yes, delete", confirmButtonColor:"#b42318"});
			confirmed = res.isConfirmed;
		} else {
			confirmed = confirm("Delete \"" + n.title + "\"?");
		}
		if (!confirmed) return;
		news.splice(news.findIndex(x => x.id === n.id), 1);
		render(); toast("Unit news deleted.");
	}

	$("addNewsBtn").addEventListener("click", () => openForm(null));
	search.addEventListener("input", () => { page = 1; render(); });
	[unitFilter, statusFilter, sortSel].forEach(el => el.addEventListener("change", () => { page = 1; render(); }));
	pager.prev.addEventListener("click", () => { if (page > 1) { page--; render(); } });
	pager.next.addEventListener("click", () => { page++; render(); });

	tbody.addEventListener("click", e => {
		const btn = e.target.closest("[data-action]"); if (!btn) return;
		const row = btn.closest("tr[data-id]"); if (!row) return;
		const n = news.find(x => x.id === Number(row.dataset.id)); if (!n) return;
		if (btn.dataset.action === "preview") openPreview(n);
		if (btn.dataset.action === "edit") openForm(n);
		if (btn.dataset.action === "delete") deleteNews(n);
	});

	document.querySelectorAll("[data-close-news-form]").forEach(b => b.addEventListener("click", closeForm));
	document.querySelectorAll("[data-close-news-preview]").forEach(b => b.addEventListener("click", closePreview));
	$("cancelNewsBtn").addEventListener("click", closeForm);

	$("chooseNewsImageBtn").addEventListener("click", () => imageFile.click());
	$("removeNewsImage").addEventListener("click", () => { setImage(""); imageFile.value = ""; });
	imageFile.addEventListener("change", readImage);

	$("previewNewsBtn").addEventListener("click", () => {
		const existing = editingId !== null ? news.find(x => x.id === editingId) : null;
		openPreview({
			unit: $("newsUnit").value, title: $("newsTitle").value.trim(), excerpt: $("newsExcerpt").value.trim(),
			publishDate: $("newsPublishDate").value, expiryDate: (expiryInput && expiryInput.value) || null,
			status: $("newsStatus").value, image: imageData,
			createdBy: existing ? existing.createdBy : "Admin", createdAt: existing ? existing.createdAt : Date.now(),
			updatedBy: existing ? existing.updatedBy : "Admin"
		});
	});

	form.addEventListener("submit", e => {
		e.preventDefault();
		const data = {
			unit: $("newsUnit").value, title: $("newsTitle").value.trim(), excerpt: $("newsExcerpt").value.trim(),
			publishDate: $("newsPublishDate").value, expiryDate: (expiryInput && expiryInput.value) || null,
			status: $("newsStatus").value, image: imageData
		};
		if (!data.unit || !data.title || !data.excerpt || !data.publishDate) return;
		if (data.expiryDate && data.expiryDate < data.publishDate) {
			toast("Expiry date cannot be earlier than the publish date.");
			if (expiryInput) expiryInput.focus();
			return;
		}
		if (editingId !== null) {
			const n = news.find(x => x.id === editingId);
			if (n) Object.assign(n, data, {updatedBy: "Admin"});
			toast("Unit news updated.");
		} else {
			news.push({id: nextId++, createdBy: "Admin", updatedBy: "Admin", createdAt: Date.now(), ...data});
			toast("Unit news added.");
		}
		page = 1; render(); closeForm();
	});

	document.addEventListener("keydown", e => {
		if (e.key !== "Escape") return;
		if (previewModal.classList.contains("open")) closePreview();
		else if (formModal.classList.contains("open")) closeForm();
	});

	render();
})();

loadSharedLayout();
if (window.lucide) { lucide.createIcons(); }