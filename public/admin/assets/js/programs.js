(function () {
    const $ = id => document.getElementById(id);
    const day = 86400000;

    const programs = [
        {id:1, name:"Bachelor of Science in Computer Science", college:"College of Computer Studies", mission:"To produce competent computing professionals who can design and build reliable software solutions for society.", vision:"A leading program recognized for excellence in computing education and innovation.", status:"Active", createdBy:"Admin", updatedBy:"Admin", createdAt:Date.now() - 6 * day},
        {id:2, name:"Bachelor of Science in Accountancy", college:"College of Business and Accountancy", mission:"To develop ethical and skilled accountants ready for professional practice.", vision:"A center of excellence in accountancy education in the region.", status:"Active", createdBy:"Admin", updatedBy:"Admin", createdAt:Date.now() - 5 * day},
        {id:3, name:"Bachelor of Elementary Education", college:"College of Education", mission:"To prepare dedicated teachers who nurture learners with competence and compassion.", vision:"A premier teacher education program committed to quality and service.", status:"Active", createdBy:"Admin", updatedBy:"Admin", createdAt:Date.now() - 4 * day},
        {id:4, name:"Bachelor of Arts in Communication", college:"College of Arts and Sciences", mission:"To train communicators who think critically and express ideas responsibly.", vision:"A program that shapes socially engaged and creative communicators.", status:"Hidden", createdBy:"Admin", updatedBy:"Admin", createdAt:Date.now() - 3 * day},
        {id:5, name:"Bachelor of Science in Civil Engineering", college:"College of Engineering", mission:"To produce engineers who build safe and sustainable infrastructure.", vision:"A recognized engineering program driven by innovation and integrity.", status:"Active", createdBy:"Admin", updatedBy:"Admin", createdAt:Date.now() - 2 * day}
    ];
    let nextId = 6, editingId = null, page = 1;
    const pageSize = 10;

    const tbody = $("programsTableBody"), search = $("programSearch"), collegeFilter = $("collegeFilter"), statusFilter = $("programStatusFilter"), sortSel = $("programSort");
    const formModal = $("programFormModal"), previewModal = $("programPreviewModal"), form = $("programForm");

    const esc = v => String(v).replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;").replaceAll('"', "&quot;").replaceAll("'", "&#039;");
    const fmtDate = ts => ts ? new Date(ts).toLocaleDateString("en-US", {year:"numeric", month:"short", day:"numeric"}) : "—";
    const icons = () => { if (window.lucide) lucide.createIcons(); };

    const pager = {
        nav: $("programsPagination"), prev: $("programsPrevPage"), next: $("programsNextPage"),
        numbers: $("programsPageNumbers"), total: $("programsPageTotal")
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
                b.className = "program-page-btn" + (p === page ? " active" : "");
                pager.numbers.appendChild(s);
                return;
            }
            const b = document.createElement("button");
            b.type = "button";
            b.className = "page-btn" + (p === page ? " active" : "");
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

    const statusClass = s => s === "Active" ? "program-status-active" : "program-status-hidden";

    function toast(message) {
        const t = $("programsToast"); t.textContent = message; t.classList.add("show");
        clearTimeout(toast.timer); toast.timer = setTimeout(() => t.classList.remove("show"), 2400);
    }

    function render() {
        const q = search.value.trim().toLowerCase(), col = collegeFilter.value, st = statusFilter.value, mode = sortSel.value;
        const list = programs.filter(p =>
            (p.name + " " + p.college + " " + p.mission + " " + p.vision).toLowerCase().includes(q) &&
            (col === "All" || p.college === col) &&
            (st === "All" || p.status === st)
        );
        list.sort((a, b) => {
            if (mode === "name-desc") return b.name.localeCompare(a.name);
            if (mode === "newest") return b.createdAt - a.createdAt;
            if (mode === "oldest") return a.createdAt - b.createdAt;

            return a.name.localeCompare(b.name);
        });
        const last = Math.max(1, Math.ceil(list.length / pageSize));
        if (page > last) page = last;
        const visible = list.slice((page - 1) * pageSize, page * pageSize);

                tbody.innerHTML = visible.map(p => `
            <tr data-id="${p.id}">
                <td><span class="program-cell-ellipsis program-name" title="${esc(p.name)}">${esc(p.name)}</span></td>
                <td><span class="program-cell-ellipsis" title="${esc(p.college)}">${esc(p.college)}</span></td>
                <td><span class="program-mission-excerpt" title="${esc(p.mission)}">${esc(p.mission)}</span></td>
                <td><span class="program-status ${statusClass(p.status)}">${esc(p.status)}</span></td>
                <td><div class="program-actions">
                    <button class="program-action-btn" data-action="preview" type="button" title="Preview program" aria-label="Preview ${esc(p.name)}"><i data-lucide="eye"></i></button>
                    <button class="program-action-btn program-action-edit" data-action="edit" type="button" title="Edit program" aria-label="Edit ${esc(p.name)}"><i data-lucide="pencil"></i></button>
                    <button class="program-action-btn program-action-delete" data-action="delete" type="button" title="Delete program" aria-label="Delete ${esc(p.name)}"><i data-lucide="trash-2"></i></button>
                </div></td>
            </tr>`).join("");

        $("programCount").textContent = list.length + (list.length === 1 ? " program" : " programs");
        $("programsEmpty").hidden = list.length !== 0;
        renderPager(list.length);
        icons();
    }

    function openForm(p) {
        editingId = p ? p.id : null;
        $("programFormTitle").textContent = p ? "Edit Program" : "Add Program";
        $("programCollege").value = p ? p.college : "";
        $("programName").value = p ? p.name : "";
        $("programMission").value = p ? p.mission : "";
        $("programVision").value = p ? p.vision : "";
        $("programStatus").value = p ? p.status : "Active";
        formModal.classList.add("open"); formModal.setAttribute("aria-hidden", "false");
        icons(); $("programCollege").focus();
    }

    function closeForm() {
        formModal.classList.remove("open"); formModal.setAttribute("aria-hidden", "true");
        editingId = null; form.reset();
    }

    function openPreview(p) {
        $("previewProgramCollege").textContent = p.college || "College Name";
        $("previewProgramName").textContent = p.name || "Program Name";
        const shown = p.status, badge = $("previewProgramStatus");
        badge.textContent = shown; badge.className = "program-status " + statusClass(shown);
        $("previewProgramMission").textContent = p.mission || "";
        $("previewProgramVision").textContent = p.vision || "";
        $("programCreatedBy").textContent = p.createdBy || "Admin";
        $("programDateCreated").textContent = fmtDate(p.createdAt);
        $("programLastUpdatedBy").textContent = p.updatedBy || p.createdBy || "—";
        previewModal.classList.add("open"); previewModal.setAttribute("aria-hidden", "false"); icons();
    }
    
    function closePreview() { previewModal.classList.remove("open"); previewModal.setAttribute("aria-hidden", "true"); }

    async function deleteProgram(p) {
        let confirmed;
        if (window.Swal) {
            const res = await Swal.fire({title:"Delete this program?", text:p.name, icon:"warning", showCancelButton:true, confirmButtonText:"Yes, delete", confirmButtonColor:"#b42318"});
            confirmed = res.isConfirmed;
        } else {
            confirmed = confirm("Delete \"" + p.name + "\"?");
        }
        if (!confirmed) return;
        programs.splice(programs.findIndex(x => x.id === p.id), 1);
        render(); toast("Program deleted.");
    }

    $("addProgramBtn").addEventListener("click", () => openForm(null));
    search.addEventListener("input", () => { page = 1; render(); });
    [collegeFilter, statusFilter, sortSel].forEach(el => el.addEventListener("change", () => { page = 1; render(); }));
    pager.prev.addEventListener("click", () => { if (page > 1) { page--; render(); } });
    pager.next.addEventListener("click", () => { page++; render(); });

    tbody.addEventListener("click", e => {
        const btn = e.target.closest("[data-action]"); if (!btn) return;
        const row = btn.closest("tr[data-id]"); if (!row) return;
        const p = programs.find(x => x.id === Number(row.dataset.id)); if (!p) return;
        if (btn.dataset.action === "preview") openPreview(p);
        if (btn.dataset.action === "edit") openForm(p);
        if (btn.dataset.action === "delete") deleteProgram(p);
    });

    document.querySelectorAll("[data-close-program-form]").forEach(b => b.addEventListener("click", closeForm));
    document.querySelectorAll("[data-close-program-preview]").forEach(b => b.addEventListener("click", closePreview));
    $("cancelProgramBtn").addEventListener("click", closeForm);

    $("previewProgramBtn").addEventListener("click", () => {
        const existing = editingId !== null ? programs.find(x => x.id === editingId) : null;
        openPreview({
            name: $("programName").value.trim(), college: $("programCollege").value,
            mission: $("programMission").value.trim(), vision: $("programVision").value.trim(),
            status: $("programStatus").value,
            createdBy: existing ? existing.createdBy : "Admin", createdAt: existing ? existing.createdAt : Date.now(),
            updatedBy: existing ? existing.updatedBy : "Admin"
        });
    });

    form.addEventListener("submit", e => {
        e.preventDefault();
        const data = {
            college: $("programCollege").value, name: $("programName").value.trim(),
            mission: $("programMission").value.trim(), vision: $("programVision").value.trim(),
            status: $("programStatus").value
        };
        if (!data.college || !data.name || !data.mission || !data.vision) return;
        if (editingId !== null) {
            const p = programs.find(x => x.id === editingId);
            if (p) Object.assign(p, data, {updatedBy: "Admin"});
            toast("Program updated.");
        } else {
            programs.push({id: nextId++, createdBy: "Admin", updatedBy: "Admin", createdAt: Date.now(), ...data});
            toast("Program added.");
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