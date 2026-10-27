(function () {
	const $ = id => document.getElementById(id);
	const day = 86400000;
	const dayStr = offset => { const d = new Date(Date.now() + offset * day); return d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0") + "-" + String(d.getDate()).padStart(2, "0"); };
	const PROGRAMS = {
		BSIT: "Bachelor of Science in Information Technology",
		BSCS: "Bachelor of Science in Computer Science",
		BSA: "Bachelor of Science in Accountancy",
		BSED: "Bachelor of Secondary Education",
		BSHM: "Bachelor of Science in Hospitality Management"
	};

	const items = [
		{id:1, title:"BSIT Students Showcase Capstone Projects", excerpt:"Graduating BSIT students presented their capstone systems to faculty and industry guests.", program:"BSIT", image:"", publishDate:dayStr(-6), expiryDate:dayStr(-2), status:"Published", createdBy:"Admin", updatedBy:"Admin", createdAt:Date.now() - 7 * day},
		{id:2, title:"BSCS Hosts Programming Contest", excerpt:"The annual inter-year programming contest challenged students with algorithm and problem-solving tasks.", program:"BSCS", image:"", publishDate:dayStr(-4), expiryDate:dayStr(30), status:"Published", createdBy:"Admin", updatedBy:"Admin", createdAt:Date.now() - 5 * day},
		{id:3, title:"Accountancy Review Program Opens", excerpt:"A free review program for incoming board examinees will start next month.", program:"BSA", image:"", publishDate:dayStr(5), expiryDate:null, status:"Draft", createdBy:"Admin", updatedBy:"Admin", createdAt:Date.now() - 3 * day},
		{id:4, title:"BSED Practicum Orientation", excerpt:"Orientation for students about to begin their practicum in partner schools.", program:"BSED", image:"", publishDate:dayStr(-20), expiryDate:null, status:"Archived", createdBy:"Admin", updatedBy:"Admin", createdAt:Date.now() - 20 * day}
	];
	let nextId = 5, editingId = null, imageData = "", page = 1;
	const pageSize = 10;

	const tbody = $("pnTableBody"), search = $("pnSearch"), programFilter = $("programFilter"), statusFilter = $("pnStatusFilter"), sortSel = $("pnSort");
	const formModal = $("pnFormModal"), previewModal = $("pnPreviewModal"), form = $("pnForm"), imageFile = $("pnImageFile");
	const dateInput = $("pnDate"), expiryInput = $("pnExpiry");

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
		nav: $("programnewsPagination"), prev: $("programnewsPrevPage"), next: $("programnewsNextPage"),
		numbers: $("programnewsPageNumbers"), total: $("programnewsPageTotal")
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
				s.className = "programnews-page-ellipsis"; s.textContent = "…";
				pager.numbers.appendChild(s);
				return;
			}
			const b = document.createElement("button");
			b.type = "button";
			b.className = "programnews-page-btn" + (p === page ? " active" : "");
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

	const statusClass = s => ({Published:"programnews-status-published", Draft:"programnews-status-draft", Archived:"programnews-status-archived", Expired:"programnews-status-expired"}[s] || "programnews-status-draft");

	function toast(message) {
		const t = $("pnToast"); t.textContent = message; t.classList.add("show");
		clearTimeout(toast.timer); toast.timer = setTimeout(() => t.classList.remove("show"), 2400);
	}

	function render() {
		const q = search.value.trim().toLowerCase(), pr = programFilter.value, st = statusFilter.value, mode = sortSel.value;
		const list = items.filter(n =>
			(n.title + " " + n.excerpt + " " + n.program + " " + (PROGRAMS[n.program] || "")).toLowerCase().includes(q) &&
			(pr === "All" || n.program === pr) &&
			(st === "All" || effectiveStatus(n) === st)
		);
		list.sort((a, b) => {
			if (mode === "oldest") return a.createdAt - b.createdAt;
			if (mode === "title") return a.title.localeCompare(b.title);
			// Posts with no expiry date always go last.
			if (mode === "expiry-asc") return (a.expiryDate || "9999-12-31").localeCompare(b.expiryDate || "9999-12-31");
			if (mode === "expiry-desc") return (b.expiryDate || "0000-01-01").localeCompare(a.expiryDate || "0000-01-01");
			return b.createdAt - a.createdAt;
		});
		const last = Math.max(1, Math.ceil(list.length / pageSize));
		if (page > last) page = last;
		const visible = list.slice((page - 1) * pageSize, page * pageSize);

		tbody.innerHTML = visible.map(n => {
			const archived = n.status === "Archived";
			const shownStatus = effectiveStatus(n);
			return `
			<tr data-id="${n.id}">
				<td><div class="programnews-thumb">${n.image ? `<img src="${n.image}" alt="">` : `<i data-lucide="image" aria-hidden="true"></i>`}</div></td>
				<td class="programnews-title-cell"><span class="programnews-title-text" title="${esc(n.title)}">${esc(n.title)}</span><span class="programnews-excerpt" title="${esc(n.excerpt)}">${esc(n.excerpt)}</span></td>
				<td><span class="programnews-cell-ellipsis" title="${esc(PROGRAMS[n.program] || n.program)}">${esc(n.program)}</span></td>
				<td><span class="programnews-cell-ellipsis">${esc(fmtDay(n.publishDate))}</span></td>
				<td><span class="programnews-cell-ellipsis">${esc(fmtExpiry(n.expiryDate))}</span></td>
				<td><span class="programnews-status ${statusClass(shownStatus)}">${esc(shownStatus)}</span></td>
				<td><div class="programnews-actions">
					<button class="programnews-action-btn" data-action="preview" type="button" title="Preview" aria-label="Preview ${esc(n.title)}"><i data-lucide="eye"></i></button>
					<button class="programnews-action-btn programnews-action-edit" data-action="edit" type="button" title="Edit" aria-label="Edit ${esc(n.title)}"><i data-lucide="pencil"></i></button>
					<button class="programnews-action-btn programnews-action-archive" data-action="archive" type="button" title="${archived ? "Restore" : "Archive"}" aria-label="${archived ? "Restore" : "Archive"} ${esc(n.title)}"><i data-lucide="${archived ? "archive-restore" : "archive"}"></i></button>
				</div></td>
			</tr>`;
		}).join("");

		$("pnCount").textContent = list.length + (list.length === 1 ? " item" : " items");
		$("pnEmpty").hidden = list.length !== 0;
		renderPager(list.length);
		icons();
	}
	function resetAndRender() { page = 1; render(); }

	function setImage(src) {
		imageData = src || "";
		const prev = $("pnImagePreview"), ph = $("pnImagePlaceholder"), rm = $("pnRemoveImage");
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
		$("pnFormHeading").textContent = n ? "Edit Program Posts" : "Add Program Posts";
		$("pnTitle").value = n ? n.title : "";
		$("pnExcerpt").value = n ? n.excerpt : "";
		$("pnProgram").value = n ? n.program : "";
		$("pnStatus").value = n && n.status !== "Archived" ? n.status : "Draft";
		$("pnDate").value = n ? n.publishDate : dayStr(0);
		if (expiryInput) expiryInput.value = n && n.expiryDate ? n.expiryDate : "";
		syncExpiryMin();
		setImage(n ? n.image : "");
		formModal.classList.add("open"); formModal.setAttribute("aria-hidden", "false");
		icons(); $("pnTitle").focus();
	}
	function closeForm() {
		formModal.classList.remove("open"); formModal.setAttribute("aria-hidden", "true");
		editingId = null; form.reset(); imageFile.value = ""; setImage("");
		syncExpiryMin();
	}

	function openPreview(n) {
		$("pnPreviewProgram").textContent = PROGRAMS[n.program] || n.program || "Program";
		$("pnPreviewTitle").textContent = n.title || "Untitled program posts";
		const shown = effectiveStatus(n), badge = $("pnPreviewStatus");
		badge.textContent = shown; badge.className = "programnews-status " + statusClass(shown);
		$("pnPreviewDate").textContent = n.publishDate ? "Publish date: " + fmtDay(n.publishDate) : "—";
		const expiryEl = $("pnPreviewExpiry");
		if (expiryEl) expiryEl.textContent = n.expiryDate ? "Expiry date: " + fmtDay(n.expiryDate) : "No expiry date";
		$("pnPreviewExcerpt").textContent = n.excerpt || "";

		const img = $("pnPreviewImage"), ph = $("pnPreviewNoImage");
		if (n.image) { img.src = n.image; img.hidden = false; ph.hidden = true; }
		else { img.removeAttribute("src"); img.hidden = true; ph.hidden = false; }

		$("pnCreatedBy").textContent = n.createdBy || "Admin";
		$("pnDateCreated").textContent = fmtDate(n.createdAt);
		$("pnUpdatedBy").textContent = n.updatedBy || n.createdBy || "—";
		previewModal.classList.add("open"); previewModal.setAttribute("aria-hidden", "false"); icons();
	}
	function closePreview() { previewModal.classList.remove("open"); previewModal.setAttribute("aria-hidden", "true"); }

	async function toggleArchive(n) {
		const restoring = n.status === "Archived";
		let confirmed;
		if (window.Swal) {
			const res = await Swal.fire({title: restoring ? "Restore this post?" : "Archive this post?", text:n.title, icon:"question", showCancelButton:true, confirmButtonText: restoring ? "Yes, restore" : "Yes, archive", confirmButtonColor:"#0d5c3a"});
			confirmed = res.isConfirmed;
		} else {
			confirmed = confirm((restoring ? "Restore" : "Archive") + " \"" + n.title + "\"?");
		}
		if (!confirmed) return;
		n.status = restoring ? "Draft" : "Archived"; n.updatedBy = "Admin";
		render(); toast(restoring ? "Program post restored as draft." : "Program post archived.");
	}

	$("addProgramNews").addEventListener("click", () => openForm(null));
	search.addEventListener("input", resetAndRender);
	[programFilter, statusFilter, sortSel].forEach(el => el.addEventListener("change", resetAndRender));
	pager.prev.addEventListener("click", () => { if (page > 1) { page--; render(); } });
	pager.next.addEventListener("click", () => { page++; render(); });

	tbody.addEventListener("click", e => {
		const btn = e.target.closest("[data-action]"); if (!btn) return;
		const row = btn.closest("tr[data-id]"); if (!row) return;
		const n = items.find(x => x.id === Number(row.dataset.id)); if (!n) return;
		if (btn.dataset.action === "preview") openPreview(n);
		if (btn.dataset.action === "edit") openForm(n);
		if (btn.dataset.action === "archive") toggleArchive(n);
	});

	document.querySelectorAll("[data-close-form]").forEach(b => b.addEventListener("click", closeForm));
	document.querySelectorAll("[data-close-preview]").forEach(b => b.addEventListener("click", closePreview));

	$("pnChooseImage").addEventListener("click", () => imageFile.click());
	$("pnRemoveImage").addEventListener("click", () => { setImage(""); imageFile.value = ""; });
	imageFile.addEventListener("change", readImage);

	$("pnPreviewFromForm").addEventListener("click", () => {
		const existing = editingId !== null ? items.find(x => x.id === editingId) : null;
		openPreview({
			title: $("pnTitle").value.trim(), excerpt: $("pnExcerpt").value.trim(), program: $("pnProgram").value,
			status: existing && existing.status === "Archived" ? "Archived" : $("pnStatus").value,
			publishDate: $("pnDate").value, expiryDate: (expiryInput && expiryInput.value) || null, image: imageData,
			createdBy: existing ? existing.createdBy : "Admin", createdAt: existing ? existing.createdAt : Date.now(),
			updatedBy: existing ? existing.updatedBy : "Admin"
		});
	});

	form.addEventListener("submit", e => {
		e.preventDefault();
		const data = {
			title: $("pnTitle").value.trim(), excerpt: $("pnExcerpt").value.trim(), program: $("pnProgram").value,
			status: $("pnStatus").value, publishDate: $("pnDate").value, expiryDate: (expiryInput && expiryInput.value) || null, image: imageData
		};
		if (!data.title || !data.excerpt || !data.program || !data.publishDate) return;
		if (data.expiryDate && data.expiryDate < data.publishDate) {
			toast("Expiry date cannot be earlier than the publish date.");
			if (expiryInput) expiryInput.focus();
			return;
		}
		if (editingId !== null) {
			const n = items.find(x => x.id === editingId);
			if (n) {
				if (n.status === "Archived") data.status = "Archived";
				Object.assign(n, data, {updatedBy: "Admin"});
			}
			toast("Program post updated.");
		} else {
			items.push({id: nextId++, createdBy: "Admin", updatedBy: "Admin", createdAt: Date.now(), ...data});
			toast("Program post added.");
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