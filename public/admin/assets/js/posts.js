(function () {
    const {$, esc, thumb, fmtDay, fmtDate, todayStr, toast, openModal, closeModal, confirmDelete, imageField, list, icons, initMenu} = UCC;

    const csrfKey = () => {
        const el = document.querySelector('input[name="csrf_key"]');
        return el ? el.value : "";
    };

    const SORT_MAP = {
        "created-desc": "create-desc", "created-asc": "create-asc",
        "date-desc": "publish-desc", "date-asc": "publish-asc",
        "expiry-desc": "expiry-desc", "expiry-asc": "expiry-asc",
        "title-desc": "title-desc", "title-asc": "title-asc"
    };


    const CATEGORY_CLASS = "post";   
    const categoryNames = {};        

    async function loadCategories() {
        try {
            const params = new URLSearchParams({class: CATEGORY_CLASS});

            const response = await fetch("api/categories/by-class.php?" + params.toString());
            const result = await response.json();

            if (!response.ok) return false;   

            const sel = $("postCategory");
            const current = sel.value;
            sel.innerHTML = result.data.map(c => `<option value="${esc(c.id)}">${esc(c.name)}</option>`).join("");
            if (current) sel.value = current;

            result.data.forEach(c => { categoryNames[c.id] = c.name; });
            return true;
        } catch (error) {
            return false;   
        }
    }

    async function savePost(formData) {
        try {
            const response = await fetch("api/posts/save.php", {
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

    async function loadPosts() {
        try {
            const sortValue = $("postSort").value;
            const params = new URLSearchParams({
                search: $("postSearch").value.trim(),
                filter: $("postFilter").value.toLowerCase(),   
                sort: SORT_MAP[sortValue] || sortValue
            });

            const response = await fetch("api/posts/filter.php?" + params.toString());
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

    async function fetchPost(id) {
        try {
            const params = new URLSearchParams({id});

            const response = await fetch("api/posts/item.php?" + params.toString());
            const result = await response.json();

            if (!response.ok) {
                await showAlert(false, result.message);
                return null;
            }

            const displayStatus = result.display_status || (result.data && result.data.display_status);
            return fromApi(result.data, displayStatus);
        } catch (error) {
            await showUnexpectedError(error);
            return null;
        }
    }

    async function archivePost(id) {
        try {
            const formData = new FormData();
            formData.append("csrf_key", csrfKey());   
            formData.append("id", id);

            const response = await fetch("api/posts/archive.php", {
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

    const fromApi = (r, displayStatus) => ({
        id: Number(r.id),
        title: r.title,
        excerpt: r.excerpt,
        content: r.content,
        category: r.category,
        categoryName: r.category_name || categoryNames[r.category] || r.category,
        status: r.status,
        displayStatus: displayStatus || r.display_status || r.status,
        publishDate: r.publish_date,
        expiryDate: r.expiration_date || null,
        image: r.featured_image || "",
        createdBy: r.created_by,
        updatedBy: r.updated_by,
        createdAt: r.created_at ? new Date(r.created_at).getTime() : 0
    });

    const effectiveStatus = it => it.displayStatus;

    const items = [];
    let editingId = null, isSaving = false;

    const statusCls = s => ({published: "status-published", draft: "status-draft", pending: "status-pending", archived: "status-archived", expired: "status-expired"}[String(s).toLowerCase()] || "status-draft");
    const formModal = $("postModal"), previewModal = $("postPreviewModal"), form = $("postForm");
    const img = imageField({fileInput: $("featuredImage"), chooseBtn: $("uploadImageBtn"), removeBtn: $("removeImageBtn"), preview: $("imagePreview"), placeholder: $("uploadPlaceholder")});

    const noSort = () => 0;
    const table = list({
        items: () => items,
        tbody: $("postsTableBody"), search: $("postSearch"), filter: $("postFilter"), sort: $("postSort"),
        count: $("postsCount"),
        pageSize: 10,
        pager: {
            nav: $("postsPagination"), prev: $("prevPageBtn"), next: $("nextPageBtn"),
            numbers: $("pageNumbers"), total: $("pageTotal")
        },
        cols: 7, emptyText: "No posts found.",
        countLabel: n => n + (n === 1 ? " post" : " posts"),
        match: () => true,
        filterFn: () => true,
        sorters: {
            "created-desc": noSort, "created-asc": noSort,
            "date-desc": noSort, "date-asc": noSort,
            "expiry-asc": noSort, "expiry-desc": noSort,
            "title-asc": noSort, "title-desc": noSort
        },
        row: it => {
            const st = effectiveStatus(it);
            return `
            <tr data-id="${it.id}">
                <td>${thumb(it.image, "image")}</td>
                <td class="post-title-cell"><span class="cell-ellipsis" title="${esc(it.title)}">${esc(it.title)}</span></td>
                <td><span class="cell-ellipsis category-text">${esc(it.categoryName)}</span></td>
                <td><span class="cell-ellipsis">${esc(fmtDay(it.publishDate))}</span></td>
                <td><span class="cell-ellipsis">${it.expiryDate ? esc(fmtDay(it.expiryDate)) : "No expiry"}</span></td>
                <td><span class="status-badge ${statusCls(st)}">${esc(st)}</span></td>
                <td><div class="action-buttons">
                    <button class="action-btn" data-action="preview" type="button" title="Preview post" aria-label="Preview post"><i data-lucide="eye"></i></button>
                    <button class="action-btn edit" data-action="edit" type="button" title="Edit post" aria-label="Edit post"><i data-lucide="pencil"></i></button>
                    ${String(it.status).toLowerCase() === "archived" ? "" : '<button class="action-btn archive" data-action="archive" type="button" title="Archive post" aria-label="Archive post"><i data-lucide="archive"></i></button>'}
                </div></td>
            </tr>`;
        },
        onAction: async (action, id) => {
            const it = items.find(x => x.id === id);
            if (!it) return;

            if (action === "preview") {
                const post = await fetchPost(id);
                if (post) showPreview(post);
            }
            if (action === "edit") {
                const post = await fetchPost(id);
                if (post) openForm(post);
            }

            if (action === "archive" && await confirmDelete("Archive this post?", it.title, "Yes, archive")) {
                if (!await archivePost(id)) return;
                await loadPosts();
            }
        }
    });

    function openForm(it) {
        editingId = it ? it.id : null;
        $("postFormTitle").textContent = it ? "Edit Post" : "Add New Post";
        $("postFormDescription").textContent = it ? "Update this post." : "Create a post for the university website.";
        const sel = $("postStatus");
        if (it && ![...sel.options].some(o => o.value === it.status)) sel.add(new Option(it.status, it.status));
        $("postTitle").value = it ? it.title : "";
        $("postExcerpt").value = it ? it.excerpt : "";

        if (it) $("postCategory").value = it.category;
        sel.value = it ? it.status : "draft";   
        $("postDate").value = it ? it.publishDate : todayStr();
        $("postExpiryDate").value = it && it.expiryDate ? it.expiryDate : "";
        $("postContent").value = it ? it.content : "";
        img.set(it ? it.image : "");
        openModal(formModal);
        $("postTitle").focus();
    }
    function closeForm() { closeModal(formModal); editingId = null; form.reset(); img.reset(); }

    function showPreview(it) {
        $("previewTitle").textContent = it.title || "Post title";
        $("previewExcerpt").textContent = it.excerpt || "";
        $("previewContent").textContent = it.content || "";
        const pi = $("previewImage"), pd = document.querySelector("#previewImageWrap .preview-default-image");
        if (it.image) { pi.src = it.image; pi.hidden = false; pd.hidden = true; }
        else { pi.removeAttribute("src"); pi.hidden = true; pd.hidden = false; }
        $("previewMetadataCreatedBy").textContent = it.createdBy || "—";
        $("previewMetadataDateCreated").textContent = it.createdAt ? fmtDate(it.createdAt) : "—";
        $("previewMetadataLastUpdatedBy").textContent = it.updatedBy || "—";
        $("previewMetadataExpiry").textContent = it.expiryDate ? fmtDay(it.expiryDate) : "No expiry";
        openModal(previewModal);
    }

    const readForm = () => ({
        title: $("postTitle").value.trim(), excerpt: $("postExcerpt").value.trim(), category: $("postCategory").value,
        status: $("postStatus").value, publishDate: $("postDate").value || todayStr(), expiryDate: $("postExpiryDate").value || null,
        content: $("postContent").value.trim(), image: img.get()
    });

    function buildFormData(data, existing) {
        const formData = new FormData();

        formData.append("csrf_key", csrfKey());                         
        formData.append("id", editingId !== null ? editingId : 0);      

        formData.append("title", data.title);
        formData.append("excerpt", data.excerpt);
        formData.append("content", data.content);
        formData.append("category", data.category);
        formData.append("status", data.status);
        formData.append("publish_date", data.publishDate);
        formData.append("expiration_date", data.expiryDate || "");

        const file = $("featuredImage").files[0];
        if (file) {
            formData.append("featured_image", file);
        } else if (existing && existing.image && !data.image) {
            formData.append("remove_featured_image", "1");
        }

        return formData;
    }

    $("addPostBtn").addEventListener("click", () => openForm(null));
    $("formPreviewBtn").addEventListener("click", () => {
        const existing = editingId !== null ? items.find(x => x.id === editingId) : null;
        showPreview({createdBy: existing ? existing.createdBy : "Admin", createdAt: existing ? existing.createdAt : Date.now(), updatedBy: existing ? existing.updatedBy : "", ...readForm()});
    });

    form.addEventListener("submit", async e => {
        e.preventDefault();
        if (isSaving) return;

        const data = readForm();
        if (!data.title || !data.excerpt || !data.content) return;

        const existing = editingId !== null ? items.find(x => x.id === editingId) : null;

        isSaving = true;
        const ok = await savePost(buildFormData(data, existing));
        isSaving = false;
        if (!ok) return;

        closeForm();
        await loadPosts();
    });

    let searchTimer;
    $("postSearch").addEventListener("input", () => { clearTimeout(searchTimer); searchTimer = setTimeout(loadPosts, 300); });
    $("postFilter").addEventListener("change", loadPosts);
    $("postSort").addEventListener("change", loadPosts);

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

    loadCategories().then(loadPosts);
})();