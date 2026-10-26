(function () {
    const {fillMeta, $, esc, fmtDay, toast, openModal, closeModal, confirmDelete, list, icons, initMenu} = UCC;

    const csrfKey = () => {
        const el = document.querySelector('input[name="csrf_key"]');
        return el ? el.value : "";
    };

    const SORT_MAP = {
        newest: "publish-desc",   
        oldest: "publish-asc",
        az: "title-asc",
        za: "title-desc"
    };

    const CATEGORY_CLASSES = ["post"];   
    const categoryNames = {};            

    async function loadCategories() {
        try {
            const lists = await Promise.all(CATEGORY_CLASSES.map(async cls => {
                const params = new URLSearchParams({class: cls});
                const response = await fetch("api/categories/by-class.php?" + params.toString());
                const result = await response.json();
                return response.ok ? result.data : [];   
            }));
            const categories = lists.flat();

            const sel = $("categoryFilter");
            const current = sel.value;
            sel.innerHTML = '<option value="all">All</option>' +
                categories.map(c => `<option value="${esc(c.id)}">${esc(c.name)}</option>`).join("");
            if (current) sel.value = current;

            categories.forEach(c => { categoryNames[c.id] = c.name; });
            return true;
        } catch (error) {
            return false;   
        }
    }

    async function loadArchive() {
        try {
            const sortValue = $("orderFilter").value;
            const params = new URLSearchParams({
                search: $("archiveSearch").value.trim(),
                filter: $("categoryFilter").value,          
                sort: SORT_MAP[sortValue] || sortValue
            });

            const response = await fetch("api/archive/filter.php?" + params.toString());
            const result = await response.json();

            if (!response.ok) {
                await showAlert(false, result.message);
                return false;
            }

            items.splice(0, items.length, ...result.data.map(r => fromApi(r)));
            table.reset();
            return true;
        } catch (error) {
            await showUnexpectedError(error);
            return false;
        }
    }

    async function unarchivePost(id, category) {
        return sendPostAction("api/archive/unarchive.php", id, category);
    }

    async function deletePost(id, category) {
        return sendPostAction("api/archive/delete.php", id, category);
    }

    async function sendPostAction(url, id, category) {
        try {
            const formData = new FormData();
            formData.append("csrf_key", csrfKey());
            formData.append("id", id);
            formData.append("category", category);

            const response = await fetch(url, {
                method: "POST",
                body: formData
            });

            const result = await response.json();

            if (!response.ok) {
                await showAlert(false, result.message);
                return false;
            }

            await showAlert(true, result.message);
            return true;
        } catch (error) {
            await showUnexpectedError(error);
            return false;
        }
    }

    const fromApi = r => ({
        id: Number(r.id),
        title: r.title,
        excerpt: r.excerpt,
        content: r.content,
        category: r.category,                                                  
        categoryName: r.category_name || categoryNames[r.category] || r.category,   
        publish: r.publish_date,
        archived: r.archived_date,
        image: r.featured_image || "",
        createdBy: r.created_by,
        updatedBy: r.updated_by,
        createdAt: r.created_at ? new Date(r.created_at).getTime() : 0
    });

    const items = [];

    const previewModal = $("archivePreviewModal");

    const noSort = () => 0;
    const table = list({
        items: () => items,
        tbody: $("archiveTableBody"), search: $("archiveSearch"), filter: $("categoryFilter"), sort: $("orderFilter"),
        count: $("archiveCount"),
        pageSize: 10,
        pager: {
            nav: $("archivePagination"), prev: $("archivePrevPage"), next: $("archiveNextPage"),
            numbers: $("archivePageNumbers"), total: $("archivePageTotal")
        },
        cols: 6, emptyText: "No archived content found.",
        countLabel: n => n + (n === 1 ? " archived item" : " archived items"),
        match: () => true,
        filterFn: () => true,
        sorters: {newest: noSort, oldest: noSort, az: noSort, za: noSort},
        row: it => `
            <tr data-id="${it.id}">
                <td><div class="archive-thumb">${it.image ? `<img src="${esc(it.image)}" alt="">` : `<i data-lucide="image" aria-hidden="true"></i>`}</div></td>
                <td class="archive-title-cell"><span class="archive-cell-ellipsis" title="${esc(it.title)}">${esc(it.title)}</span><span class="archive-cell-ellipsis archive-excerpt-line">${esc(it.excerpt)}</span></td>
                <td><span class="archive-category">${esc(it.categoryName)}</span></td>
                <td>${esc(fmtDay(it.publish))}</td>
                <td>${esc(fmtDay(it.archived))}</td>
                <td><div class="archive-actions">
                    <button type="button" class="archive-action-btn" data-action="preview" title="Preview post" aria-label="Preview post"><i data-lucide="eye"></i></button>
                    <button type="button" class="archive-action-btn archive-action-restore" data-action="restore" title="Unarchive post" aria-label="Unarchive post"><i data-lucide="archive-restore"></i></button>
                    <button type="button" class="archive-action-btn archive-action-delete" data-action="delete" title="Delete permanently" aria-label="Delete permanently"><i data-lucide="trash-2"></i></button>
                </div></td>
            </tr>`,
        onAction: async (action, id) => {
            const it = items.find(x => x.id === id);
            if (!it) return;

            if (action === "preview") showPreview(it);

            if (action === "restore" && await confirmDelete("Restore this post?", it.title, "Yes, restore")) {
                if (!await unarchivePost(it.id, it.category)) return;
                await loadArchive();
            }

            if (action === "delete" && await confirmDelete("Delete permanently?", it.title)) {
                if (!await deletePost(it.id, it.category)) return;
                await loadArchive();
            }
        }
    });

    function showPreview(it) {
        $("previewTitle").textContent = it.title;
        $("previewExcerpt").textContent = it.excerpt;
        $("previewBody").textContent = it.content;
        const pi = $("previewImage"), pd = $("previewImagePlaceholder");
        if (it.image) { pi.src = it.image; pi.hidden = false; pd.hidden = true; }
        else { pi.removeAttribute("src"); pi.hidden = true; pd.hidden = false; }
        fillMeta(it);
        openModal(previewModal);
    }

    let searchTimer;
    $("archiveSearch").addEventListener("input", () => { clearTimeout(searchTimer); searchTimer = setTimeout(loadArchive, 300); });
    $("categoryFilter").addEventListener("change", loadArchive);
    $("orderFilter").addEventListener("change", loadArchive);

    document.querySelectorAll("[data-close-preview]").forEach(b => b.addEventListener("click", () => closeModal(previewModal)));
    document.addEventListener("keydown", e => { if (e.key === "Escape" && previewModal.classList.contains("open")) closeModal(previewModal); });

    table.render();
    initMenu();
    icons();

    loadCategories().then(loadArchive);
})();