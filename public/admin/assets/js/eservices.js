(function () {
    const {fillMeta, $, esc, toast, openModal, closeModal, confirmDelete, imageField, list, icons, initMenu} = UCC;
    const day = 86400000;

    const items = [];
    const mk = (id, name, description, url, status, ago) => items.push({id, name, description, url, image: "", status, createdBy: "Admin", updatedBy: id % 2 ? "Admin" : "", createdAt: Date.now() - ago * day});
    mk(1, "Student Portal", "Access grades, enrollment records, and student information.", "https://example.com/student-portal", "Active", 12);
    mk(2, "Online Enrollment", "Enroll online for the upcoming semester.", "https://example.com/enrollment", "Active", 11);
    mk(3, "Library Catalog", "Search the university library collection.", "https://example.com/library", "Active", 10);
    mk(4, "Request a Document", "Request transcripts and certificates online.", "", "Inactive", 9);
    mk(5, "Online Payment", "Pay tuition and other fees online.", "https://example.com/payment", "Active", 8);

    let nextId = 200, editingId = null;
    const statusClass = s => s === "Active" ? "eservice-status-active" : "eservice-status-inactive";

    const formModal = $("mainModal"), previewModal = $("previewModal"), form = $("form");
    const img = imageField({fileInput: $("image"), chooseBtn: $("uploadImageBtn"), removeBtn: $("removeImageBtn"), preview: $("imagePreview"), placeholder: $("uploadImagePlaceholder")});

    const table = list({
        items: () => items,
        tbody: $("tableBody"), search: $("search"), filter: $("filter"), sort: $("sort"),
        count: $("itemCount"),
        pageSize: 10,
        pager: {
            nav: $("eservicesPagination"), prev: $("eservicesPrevPage"), next: $("eservicesNextPage"),
            numbers: $("eservicesPageNumbers"), total: $("eservicesPageTotal")
        },
        cols: 5, emptyText: "No e-services found",
        countLabel: n => n + (n === 1 ? " service" : " services"),
        match: (it, q) => (it.name + " " + it.description + " " + it.url).toLowerCase().includes(q),
        filterFn: (it, f) => f === "all" || it.status.toLowerCase() === f,
        sorters: {
            "created-desc": (a, b) => b.createdAt - a.createdAt,
            "created-asc": (a, b) => a.createdAt - b.createdAt,
            "name-asc": (a, b) => a.name.localeCompare(b.name),
            "name-desc": (a, b) => b.name.localeCompare(a.name)
        },
        row: it => `
            <tr data-id="${it.id}">
                <td><div class="eservice-thumb">${it.image ? `<img src="${esc(it.image)}" alt="">` : `<i data-lucide="globe" aria-hidden="true"></i>`}</div></td>
                <td><span class="eservice-cell-ellipsis eservice-name" title="${esc(it.name)}">${esc(it.name)}</span></td>
                <td><span class="eservice-cell-ellipsis eservice-link" title="${esc(it.url)}">${esc(it.url || "—")}</span></td>
                <td><span class="eservice-status ${statusClass(it.status)}">${esc(it.status)}</span></td>
                <td><div class="eservice-actions">
                    <button type="button" class="eservice-action-btn" data-action="preview" title="Preview service" aria-label="Preview service"><i data-lucide="eye"></i></button>
                    <button type="button" class="eservice-action-btn eservice-action-edit" data-action="edit" title="Edit service" aria-label="Edit service"><i data-lucide="pencil"></i></button>
                    <button type="button" class="eservice-action-btn eservice-action-delete" data-action="delete" title="Delete service" aria-label="Delete service"><i data-lucide="trash-2"></i></button>
                </div></td>
            </tr>`,
        onAction: async (action, id) => {
            const it = items.find(x => x.id === id);
            if (!it) return;
            if (action === "preview") showPreview(it);
            if (action === "edit") openForm(it);
            if (action === "delete" && await confirmDelete("Delete this e-service?", it.name)) {
                items.splice(items.indexOf(it), 1);
                table.render(); toast("E-service deleted.");
            }
        }
    });

    function openForm(it) {
        editingId = it ? it.id : null;
        $("formTitle").textContent = it ? "Edit E-Service" : "Add E-Service";
        $("name").value = it ? it.name : "";
        $("status").value = it ? it.status : "Active";
        $("description").value = it ? it.description : "";
        $("url").value = it ? it.url : "";
        img.set(it ? it.image : "");
        openModal(formModal);
        $("name").focus();
    }
    function closeForm() { closeModal(formModal); editingId = null; form.reset(); img.reset(); }

    function showPreview(it) {
        $("previewName").textContent = it.name || "Service Name";
        $("previewDescription").textContent = it.description || "Service description will appear here.";
        $("previewURL").textContent = it.url || "";
        const pi = $("previewImage"), pd = $("previewImageDefault");
        if (it.image) { pi.src = it.image; pi.hidden = false; pd.hidden = true; }
        else { pi.removeAttribute("src"); pi.hidden = true; pd.hidden = false; }
        fillMeta(it);
        openModal(previewModal);
    }

    const readForm = () => ({name: $("name").value.trim(), status: $("status").value, description: $("description").value.trim(), url: $("url").value.trim(), image: img.get()});

    $("addBtn").addEventListener("click", () => openForm(null));
    $("formPreviewBtn").addEventListener("click", () => {
        const existing = editingId !== null ? items.find(x => x.id === editingId) : null;
        showPreview({createdBy: existing ? existing.createdBy : "Admin", createdAt: existing ? existing.createdAt : Date.now(), updatedBy: existing ? existing.updatedBy : "", ...readForm()});
    });
    form.addEventListener("submit", e => {
        e.preventDefault();
        const data = readForm();
        if (!data.name || !data.description) return;
        if (editingId !== null) {
            Object.assign(items.find(x => x.id === editingId), data, {updatedBy: "Admin"});
            toast("E-service updated.");
        } else {
            items.push({id: nextId++, createdBy: "Admin", updatedBy: "", createdAt: Date.now(), ...data});
            toast("E-service added.");
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