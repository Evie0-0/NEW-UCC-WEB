(function () {
    const {fillMeta, $, esc, toast, openModal, closeModal, confirmDelete, imageField, list, icons, initMenu} = UCC;
    const day = 86400000;

    const items = [];
    const mk = (id, name, description, status, ago) => items.push({id, name, description, image: "", status, createdBy: "Admin", updatedBy: id % 2 ? "Admin" : "", createdAt: Date.now() - ago * day});
    mk(1, "About University of Caloocan City", "Learn about UCC, its history, mission, and commitment to quality education.", "Active", 12);
    mk(2, "Our History", "Discover the milestones and growth that shaped the university.", "Active", 11);
    mk(3, "Mission and Vision", "Read the university's mission, vision, and guiding values.", "Hidden", 10);
    mk(4, "Our Community", "Meet the students, faculty, and community that make UCC unique.", "Active", 9);
    mk(5, "Academic Excellence", "An overview of UCC's academic offerings and learning environment.", "Hidden", 8);
    let nextId = 200, editingId = null;

    const statusClass = s => ({Active: "about-status-active", Hidden: "about-status-hidden"}[s] || "about-status-hidden");

    const formModal = $("postFormOverlay"), previewModal = $("postPreviewOverlay"), form = $("postForm");
    const img = imageField({fileInput: $("featuredImage"), chooseBtn: $("chooseImageBtn"), removeBtn: $("removeImageBtn"), preview: $("selectedImagePreview"), placeholder: $("uploadPlaceholder")});

    const table = list({
        items: () => items,
        tbody: $("postsTableBody"), search: $("aboutSearch"), filter: $("statusFilter"),
        count: $("postCount"),
        pageSize: 10,
        pager: {
            nav: $("aboutPagination"), prev: $("aboutPrevPage"), next: $("aboutNextPage"),
            numbers: $("aboutPageNumbers"), total: $("aboutPageTotal")
        },
        cols: 5, emptyText: "No posts found.",
        countLabel: n => n + (n === 1 ? " post" : " posts"),
        match: (it, q) => (it.name + " " + it.description).toLowerCase().includes(q),
        filterFn: (it, f) => f === "all" || it.status === f,
        defaultSort: (a, b) => a.id - b.id,
        row: it => `
            <tr data-id="${it.id}">
                <td><div class="about-thumb">${it.image ? `<img src="${esc(it.image)}" alt="">` : `<i data-lucide="image" aria-hidden="true"></i>`}</div></td>
                <td><span class="about-cell-ellipsis about-name" title="${esc(it.name)}">${esc(it.name)}</span></td>
                <td><span class="about-cell-ellipsis" title="${esc(it.description)}">${esc(it.description)}</span></td>
                <td><span class="about-status ${statusClass(it.status)}">${esc(it.status)}</span></td>
                <td><div class="about-actions">
                    <button type="button" class="about-action-btn" data-action="preview" title="Preview post" aria-label="Preview post"><i data-lucide="eye"></i></button>
                    <button type="button" class="about-action-btn about-action-edit" data-action="edit" title="Edit post" aria-label="Edit post"><i data-lucide="pencil"></i></button>
                    <button type="button" class="about-action-btn about-action-delete" data-action="delete" title="Delete post" aria-label="Delete post"><i data-lucide="trash-2"></i></button>
                </div></td>
            </tr>`,
        onAction: async (action, id) => {
            const it = items.find(x => x.id === id);
            if (!it) return;
            if (action === "preview") showPreview(it);
            if (action === "edit") openForm(it);
            if (action === "delete" && await confirmDelete("Delete this post?", it.name)) {
                items.splice(items.indexOf(it), 1);
                table.render(); toast("Post deleted.");
            }
        }
    });

    function openForm(it) {
        editingId = it ? it.id : null;
        $("formModalTitle").textContent = it ? "Edit Post" : "Add Post";
        $("savePostLabel").textContent = it ? "Save Changes" : "Save Post";
        $("postName").value = it ? it.name : "";
        $("postDescription").value = it ? it.description : "";
        $("postStatus").value = it ? it.status : "Active";
        img.set(it ? it.image : "");
        openModal(formModal);
        $("postName").focus();
    }
    function closeForm() { closeModal(formModal); editingId = null; form.reset(); img.reset(); }

    function showPreview(it) {
        $("previewName").textContent = it.name || "Post name";
        $("previewDescription").textContent = it.description || "Post description will appear here.";
        const shown = it.status, badge = $("previewStatus");
        badge.textContent = shown; badge.className = "about-status " + statusClass(shown);
        const pi = $("previewImage"), wrap = $("previewImageWrap");
        if (it.image) { pi.src = it.image; pi.hidden = false; wrap.hidden = false; }
        else { pi.removeAttribute("src"); pi.hidden = true; wrap.hidden = true; }
        fillMeta(it);
        openModal(previewModal);
    }

    const readForm = () => ({name: $("postName").value.trim(), description: $("postDescription").value.trim(), status: $("postStatus").value, image: img.get()});

    $("addPostBtn").addEventListener("click", () => openForm(null));
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
            toast("Post updated.");
        } else {
            items.push({id: nextId++, createdBy: "Admin", updatedBy: "", createdAt: Date.now(), ...data});
            toast("Post added.");
        }
        table.reset(); closeForm();
    });
    document.querySelectorAll("[data-close-form]").forEach(b => b.addEventListener("click", closeForm));
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