(function () {
	const $ = id => document.getElementById(id);
	const day = 86400000;
	const dayStr = offset => { const d = new Date(Date.now() + offset * day); return d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0") + "-" + String(d.getDate()).padStart(2, "0"); };

	const regents = [
		{id:1, name:"Hon. Maria Lourdes Aquino", position:"Chairperson", bio:"Presides over meetings of the Board of Regents and guides university governance.", order:1, status:"Active", photo:"", expiryDate:null, createdBy:"Admin", updatedBy:"Admin", createdAt:Date.now() - 9 * day},
		{id:2, name:"Hon. Roberto Mendoza", position:"Vice Chairperson", bio:"Assists the chairperson and represents the board in official functions.", order:2, status:"Active", photo:"", expiryDate:null, createdBy:"Admin", updatedBy:"Admin", createdAt:Date.now() - 8 * day},
		{id:3, name:"Dr. Ricardo Dela Cruz", position:"Member (University President)", bio:"Serves as an ex officio member of the board.", order:3, status:"Active", photo:"", expiryDate:"2026-09-30", createdBy:"Admin", updatedBy:"Admin", createdAt:Date.now() - 7 * day},
		{id:4, name:"Hon. Teresa Bautista", position:"Member (Faculty Representative)", bio:"", order:4, status:"Hidden", photo:"", expiryDate:null, createdBy:"Admin", updatedBy:"Admin", createdAt:Date.now() - 6 * day}
	];
	let nextId = 5, editingId = null, photoData = "", page = 1;
	const pageSize = 10;

	const tbody = $("borTableBody"), search = $("borSearch"), statusFilter = $("borStatusFilter"), orderFilter = $("borOrderFilter");
	const formModal = $("borModal"), previewModal = $("borPreviewModal"), form = $("borForm"), photoFile = $("borPhoto");

	const esc = v => String(v).replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;").replaceAll('"', "&quot;").replaceAll("'", "&#039;");
	const initials = name => (name.trim().split(/\s+/).filter(w => /^[A-Za-z]/.test(w) && !/^(Dr|Atty|Mr|Mrs|Ms|Hon|Engr|Prof)\.?$/i.test(w)).slice(0, 2).map(w => w[0]).join("") || "RG").toUpperCase();
	const fmtDate = ts => ts ? new Date(ts).toLocaleDateString("en-US", {year:"numeric", month:"short", day:"numeric"}) : "—";
	const fmtDay = d => d ? new Date(d + "T00:00:00").toLocaleDateString("en-US", {year:"numeric", month:"short", day:"numeric"}) : "No expiry";
	const icons = () => { if (window.lucide) lucide.createIcons(); };

	const effectiveStatus = r => (r.status === "Active" && r.expiryDate && r.expiryDate < dayStr(0)) ? "Expired" : r.status;
	const statusClass = s => s === "Active" ? "regents-status-active" : (s === "Expired" ? "regents-status-expired" : "regents-status-hidden");

	const pager = {
		nav: $("borPagination"), prev: $("borPrevPage"), next: $("borNextPage"),
		numbers: $("borPageNumbers"), total: $("borPageTotal")
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
				s.className = "regents-page-ellipsis"; s.textContent = "…";
				pager.numbers.appendChild(s);
				return;
			}
			const b = document.createElement("button");
			b.type = "button";
			b.className = "regents-page-btn" + (p === page ? " active" : "");
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
		const t = $("borToast"); t.textContent = message; t.classList.add("show");
		clearTimeout(toast.timer); toast.timer = setTimeout(() => t.classList.remove("show"), 2400);
	}

	function render() {
		const q = search.value.trim().toLowerCase(), st = statusFilter.value, mode = orderFilter.value;
		const list = regents.filter(r =>
			(r.name + " " + r.position + " " + r.bio).toLowerCase().includes(q) &&
			(st === "all" || effectiveStatus(r).toLowerCase() === st)
		);
		list.sort((a, b) => {
			if (mode === "descending") return b.order - a.order;
			if (mode === "name") return a.name.localeCompare(b.name);
			return a.order - b.order;
		});
		const last = Math.max(1, Math.ceil(list.length / pageSize));
		if (page > last) page = last;
		const visible = list.slice((page - 1) * pageSize, page * pageSize);

		tbody.innerHTML = visible.map(r => `
			<tr data-id="${r.id}">
				<td><div class="regents-thumb">${r.photo ? `<img src="${r.photo}" alt="">` : esc(initials(r.name))}</div></td>
				<td><span class="regents-cell-ellipsis regents-name-text" title="${esc(r.name)}">${esc(r.name)}</span></td>
				<td><span class="regents-cell-ellipsis" title="${esc(r.position)}">${esc(r.position)}</span></td>
				<td><span class="regents-cell-ellipsis">${r.order}</span></td>
				<td><span class="regents-status ${statusClass(effectiveStatus(r))}">${esc(effectiveStatus(r))}</span></td>
				<td><div class="regents-actions">
					<button class="regents-action-btn" data-action="view" type="button" title="View details" aria-label="View ${esc(r.name)}"><i data-lucide="eye"></i></button>
					<button class="regents-action-btn regents-action-edit" data-action="edit" type="button" title="Edit regent" aria-label="Edit ${esc(r.name)}"><i data-lucide="pencil"></i></button>
					<button class="regents-action-btn regents-action-delete" data-action="delete" type="button" title="Delete regent" aria-label="Delete ${esc(r.name)}"><i data-lucide="trash-2"></i></button>
				</div></td>
			</tr>`).join("");

		$("tableBorCount").textContent = list.length + (list.length === 1 ? " regent" : " regents");
		$("borEmpty").hidden = list.length !== 0;
		renderPager(list.length);
		icons();
	}

	function setPhoto(src) {
		photoData = src || "";
		const prev = $("borUploadImage"), ph = $("borUploadInitials"), rm = $("removeBorPhoto");
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

	function openForm(r) {
		editingId = r ? r.id : null;
		$("borModalTitle").textContent = r ? "Edit Regent" : "Add Regent";
		$("borName").value = r ? r.name : "";
		$("borPosition").value = r ? r.position : "";
		$("borBio").value = r ? r.bio : "";
		$("borOrder").value = r ? r.order : (regents.reduce((m, x) => Math.max(m, x.order), 0) + 1);
		$("borStatus").value = r ? r.status : "Active";
		$("borExpiryDate").value = r && r.expiryDate ? r.expiryDate : "";
		setPhoto(r ? r.photo : "");
		formModal.classList.add("open"); formModal.setAttribute("aria-hidden", "false");
		icons(); $("borName").focus();
	}
	function closeForm() {
		formModal.classList.remove("open"); formModal.setAttribute("aria-hidden", "true");
		editingId = null; form.reset(); photoFile.value = ""; setPhoto("");
	}

	function openPreview(r, title) {
		$("borPreviewHeading").textContent = title;
		$("borPreviewName").textContent = r.name || "Regent Name";
		$("borPreviewPosition").textContent = r.position || "";
		const shown = effectiveStatus(r), badge = $("borPreviewStatus");
		badge.textContent = shown; badge.className = "regents-status " + statusClass(shown);
		$("borPreviewBio").textContent = r.bio || "No biography provided.";
		$("borPreviewOrder").textContent = "Display order: " + (r.order || "—");

		const img = $("borPreviewImage"), ini = $("borPreviewInitials");
		ini.textContent = initials(r.name || "");
		if (r.photo) { img.src = r.photo; img.hidden = false; ini.hidden = true; }
		else { img.removeAttribute("src"); img.hidden = true; ini.hidden = false; }

		$("borPreviewCreatedBy").textContent = r.createdBy || "Admin";
		$("borPreviewDateCreated").textContent = fmtDate(r.createdAt);
		$("borPreviewLastUpdatedBy").textContent = r.updatedBy || r.createdBy || "—";
		$("borPreviewExpiry").textContent = fmtDay(r.expiryDate);
		previewModal.classList.add("open"); previewModal.setAttribute("aria-hidden", "false"); icons();
	}
	function closePreview() { previewModal.classList.remove("open"); previewModal.setAttribute("aria-hidden", "true"); }

	async function deleteRegent(r) {
		let confirmed;
		if (window.Swal) {
			const res = await Swal.fire({title:"Delete this regent?", text:r.name, icon:"warning", showCancelButton:true, confirmButtonText:"Yes, delete", confirmButtonColor:"#b42318"});
			confirmed = res.isConfirmed;
		} else {
			confirmed = confirm("Delete \"" + r.name + "\"?");
		}
		if (!confirmed) return;
		regents.splice(regents.findIndex(x => x.id === r.id), 1);
		render(); toast("Regent deleted.");
	}

	$("addBorBtn").addEventListener("click", () => openForm(null));
	search.addEventListener("input", () => { page = 1; render(); });
	[statusFilter, orderFilter].forEach(el => el.addEventListener("change", () => { page = 1; render(); }));
	pager.prev.addEventListener("click", () => { if (page > 1) { page--; render(); } });
	pager.next.addEventListener("click", () => { page++; render(); });

	tbody.addEventListener("click", e => {
		const btn = e.target.closest("[data-action]"); if (!btn) return;
		const row = btn.closest("tr[data-id]"); if (!row) return;
		const r = regents.find(x => x.id === Number(row.dataset.id)); if (!r) return;
		if (btn.dataset.action === "view") openPreview(r, "Regent Details");
		if (btn.dataset.action === "edit") openForm(r);
		if (btn.dataset.action === "delete") deleteRegent(r);
	});

	document.querySelectorAll("[data-close-bor-modal]").forEach(b => b.addEventListener("click", closeForm));
	document.querySelectorAll("[data-close-bor-preview]").forEach(b => b.addEventListener("click", closePreview));
	$("cancelBorBtn").addEventListener("click", closeForm);

	$("chooseBorPhotoBtn").addEventListener("click", () => photoFile.click());
	$("removeBorPhoto").addEventListener("click", () => { setPhoto(""); photoFile.value = ""; });
	photoFile.addEventListener("change", readPhoto);

	$("previewBorFormBtn").addEventListener("click", () => {
		const existing = editingId !== null ? regents.find(x => x.id === editingId) : null;
		openPreview({
			name: $("borName").value.trim(), position: $("borPosition").value.trim(), bio: $("borBio").value.trim(),
			order: Number($("borOrder").value) || 1, status: $("borStatus").value, photo: photoData, expiryDate: $("borExpiryDate").value || null,
			createdBy: existing ? existing.createdBy : "Admin", createdAt: existing ? existing.createdAt : Date.now(),
			updatedBy: existing ? existing.updatedBy : "Admin"
		}, "Regent Preview");
	});

	form.addEventListener("submit", e => {
		e.preventDefault();
		const data = {
			name: $("borName").value.trim(), position: $("borPosition").value.trim(), bio: $("borBio").value.trim(),
			order: Number($("borOrder").value) || 1, status: $("borStatus").value, photo: photoData, expiryDate: $("borExpiryDate").value || null
		};
		if (!data.name || !data.position) return;
		if (editingId !== null) {
			const r = regents.find(x => x.id === editingId);
			if (r) Object.assign(r, data, {updatedBy: "Admin"});
			toast("Regent updated.");
		} else {
			regents.push({id: nextId++, createdBy: "Admin", updatedBy: "Admin", createdAt: Date.now(), ...data});
			toast("Regent added.");
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