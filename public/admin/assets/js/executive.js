(function () {
	const $ = id => document.getElementById(id);
	const day = 86400000;

	const officials = [
		{id:1, name:"Dr. Ricardo Dela Cruz", position:"University President", bio:"Leads the university's academic, administrative, and strategic direction.", order:1, status:"Active", photo:"", createdBy:"Admin", updatedBy:"Admin", createdAt:Date.now() - 9 * day},
		{id:2, name:"Dr. Elena Villanueva", position:"Vice President for Academic Affairs", bio:"Oversees curriculum, instruction, and academic quality across all colleges.", order:2, status:"Active", photo:"", createdBy:"Admin", updatedBy:"Admin", createdAt:Date.now() - 8 * day},
		{id:3, name:"Atty. Jose Ramirez", position:"Vice President for Administration and Finance", bio:"Manages university finances, human resources, and general administration.", order:3, status:"Active", photo:"", createdBy:"Admin", updatedBy:"Admin", createdAt:Date.now() - 7 * day},
		{id:4, name:"Dr. Carmen Reyes", position:"Vice President for Research and Extension", bio:"Directs research, innovation, and community extension programs.", order:4, status:"Active", photo:"", createdBy:"Admin", updatedBy:"Admin", createdAt:Date.now() - 6 * day},
		{id:5, name:"Mr. Antonio Lim", position:"University Registrar", bio:"", order:5, status:"Inactive", photo:"", createdBy:"Admin", updatedBy:"Admin", createdAt:Date.now() - 5 * day}
	];
	let nextId = 6, editingId = null, photoData = "", page = 1;
	const pageSize = 10;

	const tbody = $("officialsTableBody"), search = $("officialSearch"), statusFilter = $("officialStatusFilter"), orderFilter = $("officialOrderFilter");
	const formModal = $("officialModal"), previewModal = $("officialPreviewModal"), form = $("officialForm"), photoFile = $("officialPhoto");

	const esc = v => String(v).replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;").replaceAll('"', "&quot;").replaceAll("'", "&#039;");
	const initials = name => (name.trim().split(/\s+/).filter(w => /^[A-Za-z]/.test(w) && !/^(Dr|Atty|Mr|Mrs|Ms|Hon|Engr|Prof)\.?$/i.test(w)).slice(0, 2).map(w => w[0]).join("") || "OF").toUpperCase();
	const fmtDate = ts => ts ? new Date(ts).toLocaleDateString("en-US", {year:"numeric", month:"short", day:"numeric"}) : "—";
	const icons = () => { if (window.lucide) lucide.createIcons(); };

	const effectiveStatus = o => o.status === "Inactive" ? "Hidden" : o.status;
	const statusClass = s => s === "Active" ? "executive-status-active" : "executive-status-hidden";

	const pager = {
		nav: $("officialsPagination"), prev: $("officialsPrevPage"), next: $("officialsNextPage"),
		numbers: $("officialsPageNumbers"), total: $("officialsPageTotal")
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
				s.className = "executive-page-ellipsis"; s.textContent = "…";
				pager.numbers.appendChild(s);
				return;
			}
			const b = document.createElement("button");
			b.type = "button";
			b.className = "executive-page-btn" + (p === page ? " active" : "");
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

	function toast(message) {
		const t = $("officialToast"); t.textContent = message; t.classList.add("show");
		clearTimeout(toast.timer); toast.timer = setTimeout(() => t.classList.remove("show"), 2400);
	}

	function render() {
		const q = search.value.trim().toLowerCase(), st = statusFilter.value, mode = orderFilter.value;
		const list = officials.filter(o => {
			const stKey = o.status.toLowerCase();
			return (o.name + " " + o.position + " " + o.bio).toLowerCase().includes(q) && (st === "all" || stKey === st);
		});
		list.sort((a, b) => {
			if (mode === "descending") return b.order - a.order;
			if (mode === "name") return a.name.localeCompare(b.name);
			return a.order - b.order;
		});
		const last = Math.max(1, Math.ceil(list.length / pageSize));
		if (page > last) page = last;
		const visible = list.slice((page - 1) * pageSize, page * pageSize);

		tbody.innerHTML = visible.map(o => `
			<tr data-id="${o.id}">
				<td><div class="executive-thumb">${o.photo ? `<img src="${o.photo}" alt="">` : esc(initials(o.name))}</div></td>
				<td><span class="executive-cell-ellipsis executive-name-text" title="${esc(o.name)}">${esc(o.name)}</span></td>
				<td><span class="executive-cell-ellipsis" title="${esc(o.position)}">${esc(o.position)}</span></td>
				<td><span class="executive-cell-ellipsis">${o.order}</span></td>
				<td><span class="executive-status ${statusClass(effectiveStatus(o))}">${esc(effectiveStatus(o))}</span></td>
				<td><div class="executive-actions">
					<button class="executive-action-btn" data-action="view" type="button" title="View details" aria-label="View ${esc(o.name)}"><i data-lucide="eye"></i></button>
					<button class="executive-action-btn executive-action-edit" data-action="edit" type="button" title="Edit official" aria-label="Edit ${esc(o.name)}"><i data-lucide="pencil"></i></button>
					<button class="executive-action-btn executive-action-delete" data-action="delete" type="button" title="Delete official" aria-label="Delete ${esc(o.name)}"><i data-lucide="trash-2"></i></button>
				</div></td>
			</tr>`).join("");

		$("tableOfficialCount").textContent = list.length + (list.length === 1 ? " official" : " officials");
		$("emptyState").hidden = list.length !== 0;
		renderPager(list.length);
		icons();
	}

	function setPhoto(src) {
		photoData = src || "";
		const prev = $("officialUploadImage"), ph = $("officialUploadInitials"), rm = $("removeOfficialPhoto");
		if (src) {
			prev.src = src; prev.hidden = false; prev.style.display = "block";
			ph.hidden = true; ph.style.display = "none"; rm.hidden = false;
		} else {
			prev.removeAttribute("src"); prev.hidden = true; prev.style.display = "none";
			ph.hidden = false; ph.style.display = "flex"; rm.hidden = true;
		}
	}
	function readPhoto() {
		const f = photoFile.files && photoFile.files[0]; if (!f) return;
		if (!f.type.startsWith("image/")) { toast("Please choose an image file."); photoFile.value = ""; return; }
		const r = new FileReader(); r.onload = e => setPhoto(e.target.result); r.readAsDataURL(f);
	}

	function openForm(o) {
		editingId = o ? o.id : null;
		$("modalTitle").textContent = o ? "Edit Official" : "Add Official";
		$("name").value = o ? o.name : "";
		$("position").value = o ? o.position : "";
		$("bio").value = o ? o.bio : "";
		$("displayOrder").value = o ? o.order : (officials.reduce((m, x) => Math.max(m, x.order), 0) + 1);
		$("status").value = o ? o.status : "Active";
		setPhoto(o ? o.photo : "");
		formModal.classList.add("open"); formModal.setAttribute("aria-hidden", "false");
		icons(); $("name").focus();
	}
	function closeForm() {
		formModal.classList.remove("open"); formModal.setAttribute("aria-hidden", "true");
		editingId = null; form.reset(); photoFile.value = ""; setPhoto("");
	}

	function openPreview(o, title) {
		$("officialPreviewHeading").textContent = title;
		$("officialPreviewName").textContent = o.name || "Official Name";
		$("officialPreviewPosition").textContent = o.position || "";
		const shown = effectiveStatus(o), badge = $("officialPreviewStatus");
		badge.textContent = shown; badge.className = "executive-status " + statusClass(shown);
		$("officialPreviewBio").textContent = o.bio || "No biography provided.";
		$("officialPreviewOrder").textContent = "Display order: " + (o.order || "—");

		const img = $("officialPreviewImage"), ini = $("officialPreviewInitials");
		ini.textContent = initials(o.name || "");
		if (o.photo) { img.src = o.photo; img.hidden = false; ini.hidden = true; }
		else { img.removeAttribute("src"); img.hidden = true; ini.hidden = false; }

		$("previewMetadataCreatedBy").textContent = o.createdBy || "Admin";
		$("previewMetadataDateCreated").textContent = fmtDate(o.createdAt);
		$("previewMetadataLastUpdatedBy").textContent = o.updatedBy || o.createdBy || "—";
		previewModal.classList.add("open"); previewModal.setAttribute("aria-hidden", "false"); icons();
	}
	function closePreview() { previewModal.classList.remove("open"); previewModal.setAttribute("aria-hidden", "true"); }

	async function deleteOfficial(o) {
		let confirmed;
		if (window.Swal) {
			const res = await Swal.fire({title:"Delete this official?", text:o.name, icon:"warning", showCancelButton:true, confirmButtonText:"Yes, delete", confirmButtonColor:"#b42318"});
			confirmed = res.isConfirmed;
		} else {
			confirmed = confirm("Delete \"" + o.name + "\"?");
		}
		if (!confirmed) return;
		officials.splice(officials.findIndex(x => x.id === o.id), 1);
		render(); toast("Official deleted.");
	}

	$("addOfficialBtn").addEventListener("click", () => openForm(null));
	search.addEventListener("input", () => { page = 1; render(); });
	[statusFilter, orderFilter].forEach(el => el.addEventListener("change", () => { page = 1; render(); }));
	pager.prev.addEventListener("click", () => { if (page > 1) { page--; render(); } });
	pager.next.addEventListener("click", () => { page++; render(); });

	tbody.addEventListener("click", e => {
		const btn = e.target.closest("[data-action]"); if (!btn) return;
		const row = btn.closest("tr[data-id]"); if (!row) return;
		const o = officials.find(x => x.id === Number(row.dataset.id)); if (!o) return;
		if (btn.dataset.action === "view") openPreview(o, "Executive Official Details");
		if (btn.dataset.action === "edit") openForm(o);
		if (btn.dataset.action === "delete") deleteOfficial(o);
	});

	document.querySelectorAll("[data-close-modal]").forEach(b => b.addEventListener("click", closeForm));
	document.querySelectorAll("[data-close-preview]").forEach(b => b.addEventListener("click", closePreview));
	$("cancelBtn").addEventListener("click", closeForm);

	$("chooseOfficialPhotoBtn").addEventListener("click", () => photoFile.click());
	$("removeOfficialPhoto").addEventListener("click", () => { setPhoto(""); photoFile.value = ""; });
	photoFile.addEventListener("change", readPhoto);

	$("previewOfficialFormBtn").addEventListener("click", () => {
		const existing = editingId !== null ? officials.find(x => x.id === editingId) : null;
		openPreview({
			name: $("name").value.trim(), position: $("position").value.trim(), bio: $("bio").value.trim(),
			order: Number($("displayOrder").value) || 1, status: $("status").value, photo: photoData,
			createdBy: existing ? existing.createdBy : "Admin", createdAt: existing ? existing.createdAt : Date.now(),
			updatedBy: existing ? existing.updatedBy : "Admin"
		}, "Executive Official Preview");
	});

	form.addEventListener("submit", e => {
		e.preventDefault();
		const data = {
			name: $("name").value.trim(), position: $("position").value.trim(), bio: $("bio").value.trim(),
			order: Number($("displayOrder").value) || 1, status: $("status").value, photo: photoData
		};
		if (!data.name || !data.position) return;
		if (editingId !== null) {
			const o = officials.find(x => x.id === editingId);
			if (o) Object.assign(o, data, {updatedBy: "Admin"});
			toast("Official updated.");
		} else {
			officials.push({id: nextId++, createdBy: "Admin", updatedBy: "Admin", createdAt: Date.now(), ...data});
			toast("Official added.");
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