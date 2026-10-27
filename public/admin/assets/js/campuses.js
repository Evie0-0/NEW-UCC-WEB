(function () {
    const {fillMeta, $, esc, mapsUrl, toast, openModal, closeModal, confirmDelete, imageField, list, icons, initMenu} = UCC;
    const day = 86400000;
    const items = [];
    const mk = (id, name, address, status, ago) => items.push({id, name, email: "campus" + id + "@ucc.edu.ph", address, contact: "(02) 5310 " + (1230 + id), url: "", image: "", status, createdBy: "Admin", updatedBy: id % 2 ? "Admin" : "", createdAt: Date.now() - ago * day});
    mk(1, "University of Caloocan City – South Campus", "Biglang Awa Street, Cor 11th Ave Caloocan City", "Active", 12);
    mk(2, "University of Caloocan City – North Campus", "Congressional Road Extension, Camarin, Caloocan City", "Active", 11);
    mk(3, "UCC Extension Campus (Sample)", "Sample Street, Caloocan City", "Hidden", 10);
    mk(4, "UCC Annex (Sample)", "Sample Annex Road, Caloocan City", "Active", 9);
    for (let i = 5; i <= 12; i++) mk(i, "UCC Sample Campus " + i, "Sample Address " + i + ", Caloocan City", i % 3 === 0 ? "Hidden" : "Active", 13 - i);
    let nextId = 200, editingId = null;

    const statusClass = s => ({Active: "campus-status-active", Hidden: "campus-status-hidden"}[s] || "campus-status-hidden");

    const formModal = $("mainModal"), previewModal = $("previewModal"), form = $("form");
    const img = imageField({fileInput: $("image"), chooseBtn: $("uploadImageBtn"), removeBtn: $("removeImageBtn"), preview: $("imagePreview"), placeholder: $("uploadImagePlaceholder")});
    const mapHref = it => it.url || mapsUrl(it.address);

    const table = list({
        items: () => items,
        tbody: $("tableBody"), search: $("search"), filter: $("filter"), sort: $("sort"),
        count: $("itemCount"),
        pageSize: 10,
        pager: {
            nav: $("campusesPagination"), prev: $("campusesPrevPage"), next: $("campusesNextPage"),
            numbers: $("campusesPageNumbers"), total: $("campusesPageTotal")
        },
        cols: 5, emptyText: "No campuses found.",
        countLabel: n => n + (n === 1 ? " campus" : " campuses"),
        match: (it, q) => (it.name + " " + it.address + " " + it.email).toLowerCase().includes(q),
        filterFn: (it, f) => f === "all" || it.status.toLowerCase() === f,
        sorters: {
            "created-desc": (a, b) => b.createdAt - a.createdAt,
            "created-asc": (a, b) => a.createdAt - b.createdAt,
            "name-asc": (a, b) => a.name.localeCompare(b.name),
            "name-desc": (a, b) => b.name.localeCompare(a.name)
        },
        row: it => `
            <tr data-id="${it.id}">
                <td><div class="campus-thumb">${it.image ? `<img src="${esc(it.image)}" alt="">` : `<i data-lucide="image" aria-hidden="true"></i>`}</div></td>
                <td><span class="campus-cell-ellipsis campus-name" title="${esc(it.name)}">${esc(it.name)}</span></td>
                <td><a class="campus-map-link" href="${esc(mapHref(it))}" target="_blank" rel="noopener noreferrer"><i data-lucide="map-pin"></i><span class="campus-cell-ellipsis">${esc(it.address)}</span></a></td>
                <td><span class="campus-status ${statusClass(it.status)}">${esc(it.status)}</span></td>
                <td><div class="campus-actions">
                    <button type="button" class="campus-action-btn" data-action="preview" title="Preview campus" aria-label="Preview campus"><i data-lucide="eye"></i></button>
                    <button type="button" class="campus-action-btn campus-action-edit" data-action="edit" title="Edit campus" aria-label="Edit campus"><i data-lucide="pencil"></i></button>
                    <button type="button" class="campus-action-btn campus-action-delete" data-action="delete" title="Delete campus" aria-label="Delete campus"><i data-lucide="trash-2"></i></button>
                </div></td>
            </tr>`,
        onAction: async (action, id) => {
            const it = items.find(x => x.id === id);
            if (!it) return;
            if (action === "preview") showPreview(it);
            if (action === "edit") openForm(it);
            if (action === "delete" && await confirmDelete("Delete this campus?", it.name)) {
                items.splice(items.indexOf(it), 1);
                table.render(); toast("Campus deleted.");
            }
        }
    });

    function openForm(it) {
        editingId = it ? it.id : null;
        $("formTitle").textContent = it ? "Edit Campus" : "Add Campus";
        $("name").value = it ? it.name : "";
        $("email").value = it ? it.email : "";
        $("address").value = it ? it.address : "";
        $("status").value = it ? it.status : "Active";
        $("url").value = it ? it.url : "";
        $("contact").value = it ? it.contact : "";
        img.set(it ? it.image : "");
        openModal(formModal);
        $("name").focus();
    }
    function closeForm() { closeModal(formModal); editingId = null; form.reset(); img.reset(); }

    function showPreview(it) {
        $("previewCampusName").textContent = it.name || "Campus Name";
        $("previewCampusEmail").textContent = it.email || "";
        $("previewCampusAddress").textContent = it.address || "";
        $("previewCampusContact").textContent = it.contact || "";
        const pi = $("previewImage"), pd = $("previewImageDefault");
        if (it.image) { pi.src = it.image; pi.hidden = false; pd.hidden = true; }
        else { pi.removeAttribute("src"); pi.hidden = true; pd.hidden = false; }
        const link = $("previewCampusMap");
        if (it.url || it.address) { link.href = mapHref(it); link.hidden = false; } else { link.hidden = true; }
        fillMeta(it);
        openModal(previewModal);
    }

    const readForm = () => ({
        name: $("name").value.trim(), email: $("email").value.trim(), address: $("address").value.trim(),
        status: $("status").value, url: $("url").value.trim(),
        contact: $("contact").value.trim(), image: img.get()
    });

    $("addBtn").addEventListener("click", () => openForm(null));
    $("formPreviewBtn").addEventListener("click", () => {
        const existing = editingId !== null ? items.find(x => x.id === editingId) : null;
        showPreview({createdBy: existing ? existing.createdBy : "Admin", createdAt: existing ? existing.createdAt : Date.now(), updatedBy: existing ? existing.updatedBy : "", ...readForm()});
    });
    form.addEventListener("submit", e => {
        e.preventDefault();
        const data = readForm();
        if (!data.name || !data.address) return;
        if (editingId !== null) {
            Object.assign(items.find(x => x.id === editingId), data, {updatedBy: "Admin"});
            toast("Campus updated.");
        } else {
            items.push({id: nextId++, createdBy: "Admin", updatedBy: "", createdAt: Date.now(), ...data});
            toast("Campus added.");
        }
        table.reset(); closeForm();
    });
    document.querySelectorAll("[data-close-modal]").forEach(b => b.addEventListener("click", closeForm));
    document.querySelectorAll("[data-close-preview]").forEach(b => b.addEventListener("click", () => closeModal(previewModal)));
    document.addEventListener("keydown", e => {
        if (e.key !== "Escape") return;
        if (previewModal.classList.contains("open")) closeModal(previewModal);
        else if (formModal.classList.contains("open")) closeForm();
    });

    table.render();
    initMenu();
    icons();
})();