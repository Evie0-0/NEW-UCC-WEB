(function () {
    const {fillMeta, $, esc, fmtDate, toast, openModal, closeModal, confirmDelete, imageField, list, icons, initMenu} = UCC;
    const day = 86400000;

    const items = [];
    const mk = (id, order, name, url, status, ago) => items.push({id, order, name, url, image: "", status, createdBy: "Admin", updatedBy: id % 2 ? "Admin" : "", createdAt: Date.now() - ago * day});
    mk(1, 1, "Student Portal", "https://example.com/student-portal", "Active", 12);
    mk(2, 2, "Online Enrollment", "https://example.com/enrollment", "Active", 11);
    mk(3, 3, "Library", "https://example.com/library", "Active", 10);
    mk(4, 4, "Admissions", "https://example.com/admissions", "Hidden", 9);
    mk(5, 5, "Academic Calendar", "https://example.com/calendar", "Active", 8);
    let nextId = 200, editingId = null;

    const statusClass = s => s === "Active" ? "quicklink-status-active" : "quicklink-status-hidden";

    const formModal = $("quickModal"), previewModal = $("quickPreviewModal"), form = $("quickLinkForm");
    const img = imageField({fileInput: $("featuredImage"), chooseBtn: $("chooseImageBtn"), removeBtn: $("removeImageBtn"), preview: $("selectedImagePreview"), placeholder: $("uploadPlaceholder")});

    const table = list({
        items: () => items,
        tbody: $("quickLinksBody"), search: $("quickSearch"),
        count: $("totalLinks"),
        pageSize: 10,
        pager: {
            nav: $("quicklinksPagination"), prev: $("quicklinksPrevPage"), next: $("quicklinksNextPage"),
            numbers: $("quicklinksPageNumbers"), total: $("quicklinksPageTotal")
        },
        cols: 5, emptyText: "No quick links match your search.",
        countLabel: n => n + (n === 1 ? " link" : " links"),
        match: (it, q) => (it.name + " " + it.url).toLowerCase().includes(q),
        defaultSort: (a, b) => a.order - b.order || a.id - b.id,
        row: it => `
            <tr data-id="${it.id}">
                <td>${it.order}</td>
                <td class="quicklink-title-cell"><span class="quicklink-cell-ellipsis" title="${esc(it.name)}">${esc(it.name)}</span></td>
                <td><span class="quicklink-cell-ellipsis" title="${esc(it.url)}">${esc(it.url)}</span></td>
                <td><span class="quicklink-status ${statusClass(it.status)}">${esc(it.status)}</span></td>
                <td><div class="quicklink-actions">
                    <button type="button" class="quicklink-action-btn" data-action="preview" title="Preview link" aria-label="Preview link"><i data-lucide="eye"></i></button>
                    <button type="button" class="quicklink-action-btn quicklink-action-edit" data-action="edit" title="Edit link" aria-label="Edit link"><i data-lucide="pencil"></i></button>
                    <button type="button" class="quicklink-action-btn quicklink-action-delete" data-action="delete" title="Delete link" aria-label="Delete link"><i data-lucide="trash-2"></i></button>
                </div></td>
            </tr>`,
        onAction: async (action, id) => {
            const it = items.find(x => x.id === id);
            if (!it) return;
            if (action === "preview") showPreview(it);
            if (action === "edit") openForm(it);
            if (action === "delete" && await confirmDelete("Delete this quick link?", it.name)) {
                items.splice(items.indexOf(it), 1);
                table.render(); toast("Quick link deleted.");
            }
        }
    });

    function clearErrors() { ["linkNameError", "linkUrlError", "displayOrderError"].forEach(id => { $(id).textContent = ""; }); }

    function openForm(it) {
        editingId = it ? it.id : null;
        clearErrors();
        $("modalTitle").textContent = it ? "Edit Quick Link" : "Add Quick Link";
        $("linkName").value = it ? it.name : "";
        $("linkUrl").value = it ? it.url : "";
        $("displayOrder").value = it ? it.order : (items.reduce((m, x) => Math.max(m, x.order), 0) + 1);
        $("linkStatus").value = it ? it.status : "Active";
        img.set(it ? it.image : "");
        openModal(formModal);
        $("linkName").focus();
    }
    function closeForm() { closeModal(formModal); editingId = null; form.reset(); img.reset(); clearErrors(); }

    function showPreview(it) {
        $("previewName").textContent = it.name || "Link name";
        $("previewUrl").textContent = it.url || "";
        const shown = it.status, badge = $("previewStatus");
        badge.textContent = shown; badge.className = "quicklink-status " + statusClass(shown);
        const link = $("previewVisitLink");
        link.href = it.url || "#";
        const pi = $("previewImage");
        if (it.image) { pi.src = it.image; pi.hidden = false; } else { pi.removeAttribute("src"); pi.hidden = true; }
        fillMeta(it);
        openModal(previewModal);
    }

    const readForm = () => ({name: $("linkName").value.trim(), url: $("linkUrl").value.trim(), order: parseInt($("displayOrder").value, 10), status: $("linkStatus").value, image: img.get()});

    function validate(d) {
        clearErrors();
        let ok = true;
        if (!d.name) { $("linkNameError").textContent = "Link name is required."; ok = false; }
        if (!/^https?:\/\/\S+\.\S+/i.test(d.url)) { $("linkUrlError").textContent = "Enter a valid URL starting with http:// or https://."; ok = false; }
        if (!Number.isInteger(d.order) || d.order < 1) { $("displayOrderError").textContent = "Display order must be 1 or higher."; ok = false; }
        return ok;
    }

    $("addLinkBtn").addEventListener("click", () => openForm(null));
    $("previewLinkBtn").addEventListener("click", () => {
        const existing = editingId !== null ? items.find(x => x.id === editingId) : null;
        showPreview({createdBy: existing ? existing.createdBy : "Admin", createdAt: existing ? existing.createdAt : Date.now(), updatedBy: existing ? existing.updatedBy : "", ...readForm()});
    });
    form.addEventListener("submit", e => {
        e.preventDefault();
        const data = readForm();
        if (!validate(data)) return;
        if (editingId !== null) {
            Object.assign(items.find(x => x.id === editingId), data, {updatedBy: "Admin"});
            toast("Quick link updated.");
        } else {
            items.push({id: nextId++, createdBy: "Admin", updatedBy: "", createdAt: Date.now(), ...data});
            toast("Quick link added.");
        }
        table.reset(); closeForm();
    });
    document.querySelectorAll("[data-close-modal]").forEach(b => b.addEventListener("click", closeForm));
    $("closeModalBtn").addEventListener("click", closeForm);
    $("cancelModalBtn").addEventListener("click", closeForm);
    document.querySelectorAll("[data-close-preview]").forEach(b => b.addEventListener("click", () => closeModal(previewModal)));
    $("closePreviewBtn").addEventListener("click", () => closeModal(previewModal));
    document.addEventListener("keydown", e => {
        if (e.key !== "Escape") return;
        if (previewModal.classList.contains("open")) closeModal(previewModal);
        else if (formModal.classList.contains("open")) closeForm();
    });

    table.render();
    initMenu();
    icons();
})();