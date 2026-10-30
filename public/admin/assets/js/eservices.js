(function () {
    "use strict";

    if (!window.UCC) {
        console.error("UCC shared helpers are unavailable. Check whether ucc-common.js loaded successfully.");
        return;
    }

    const {
        fillMeta,
        $,
        esc,
        toast,
        openModal,
        closeModal,
        confirmDelete,
        imageField,
        list,
        icons,
        initMenu
    } = window.UCC;

    // These paths are relative to the URL of eservices.php, not the JS file.
    const API_BASE = "../../api/eservices/";
    const UPLOAD_BASE = "../../storage/uploads/";
    const databaseRecords = Array.isArray(window.UCC_ESERVICES) ? window.UCC_ESERVICES : [];
    const databaseError = window.UCC_ESERVICES_DATABASE_ERROR || null;

    function imageUrl(filename) {
        const value = String(filename || "").trim();
        if (/^[a-f0-9]{32}\.(jpg|png|webp)$/.test(value)) {
            return UPLOAD_BASE + encodeURIComponent(value);
        }
        return "";
    }

    function normalizeRecord(record) {
        const createdAt = record.created_at
            ? new Date(String(record.created_at).replace(" ", "T")).getTime()
            : 0;
        const updatedAt = record.updated_at
            ? new Date(String(record.updated_at).replace(" ", "T")).getTime()
            : null;

        return {
            id: Number(record.id),
            name: String(record.name || ""),
            description: String(record.description || ""),
            url: String(record.url || ""),
            image: imageUrl(record.featured_image),
            imageFilename: String(record.featured_image || ""),
            status: String(record.status || "").toLowerCase() === "active" ? "Active" : "Inactive",
            createdBy: record.author_username || (record.author_id ? `User #${record.author_id}` : "—"),
            updatedBy: record.editor_username || (record.editor_id ? `User #${record.editor_id}` : ""),
            createdAt: Number.isFinite(createdAt) ? createdAt : 0,
            updatedAt: Number.isFinite(updatedAt) ? updatedAt : null
        };
    }

    const items = databaseRecords
        .filter(record => Number(record.active) === 1)
        .map(normalizeRecord);

    let editingId = null;
    let removeExistingImage = false;
    let saving = false;
    let deleting = false;

    const formModal = $("mainModal");
    const previewModal = $("previewModal");
    const form = $("form");
    const saveBtn = $("saveBtn");
    const imageInput = $("image");
    const removeImageBtn = $("removeImageBtn");

    const img = imageField({
        fileInput: imageInput,
        chooseBtn: $("uploadImageBtn"),
        removeBtn: removeImageBtn,
        preview: $("imagePreview"),
        placeholder: $("uploadImagePlaceholder")
    });

    function getCsrfToken() {
        return form.querySelector('input[name="csrf_key"]')?.value || "";
    }

    function statusClass(status) {
        return status === "Active" ? "eservice-status-active" : "eservice-status-inactive";
    }

    function showPreview(item) {
        $("previewName").textContent = item.name || "Service Name";
        $("previewDescription").textContent = item.description || "Service description will appear here.";
        $("previewURL").textContent = item.url || "";

        const previewImage = $("previewImage");
        const placeholder = $("previewImageDefault");

        if (item.image) {
            previewImage.src = item.image;
            previewImage.hidden = false;
            placeholder.hidden = true;
        } else {
            previewImage.removeAttribute("src");
            previewImage.hidden = true;
            placeholder.hidden = false;
        }

        fillMeta(item);
        openModal(previewModal);
    }

    function openForm(item = null) {
        form.reset();
        img.reset();
        editingId = item ? item.id : null;
        removeExistingImage = false;

        $("formTitle").textContent = item ? "Edit E-Service" : "Add E-Service";
        $("name").value = item ? item.name : "";
        $("status").value = item ? item.status.toLowerCase() : "active";
        $("description").value = item ? item.description : "";
        $("url").value = item ? item.url : "";

        if (item && item.image) {
            img.set(item.image);
        }

        openModal(formModal);
        $("name").focus();
    }

    function closeForm() {
        if (saving) return;
        closeModal(formModal);
        editingId = null;
        removeExistingImage = false;
        form.reset();
        img.reset();
    }

    // Keep track of whether an existing saved image was explicitly removed.
    removeImageBtn.addEventListener("click", () => {
        if (editingId === null) return;
        const existing = items.find(item => item.id === editingId);
        if (existing && existing.imageFilename) {
            removeExistingImage = true;
        }
    });

    imageInput.addEventListener("change", () => {
        const file = imageInput.files?.[0];
        if (!file) return;

        if (!["image/jpeg", "image/png", "image/webp"].includes(file.type)) {
            toast("Please choose a JPEG, PNG, or WebP image.");
            imageInput.value = "";
            img.set(editingId !== null
                ? items.find(item => item.id === editingId)?.image || ""
                : "");
            return;
        }

        if (file.size > 10 * 1024 * 1024) {
            toast("The image must not exceed 10 MB.");
            imageInput.value = "";
            img.set(editingId !== null
                ? items.find(item => item.id === editingId)?.image || ""
                : "");
            return;
        }

        removeExistingImage = false;
    });

    const table = list({
        items: () => items,
        tbody: $("tableBody"),
        search: $("search"),
        filter: $("filter"),
        sort: $("sort"),
        count: $("itemCount"),
        pageSize: 10,
        pager: {
            nav: $("eservicesPagination"),
            prev: $("eservicesPrevPage"),
            next: $("eservicesNextPage"),
            numbers: $("eservicesPageNumbers"),
            total: $("eservicesPageTotal")
        },
        cols: 5,
        emptyText: databaseError ? "Unable to load E-Services from the database." : "No e-services found",
        countLabel: n => n + (n === 1 ? " service" : " services"),
        match: (item, query) =>
            (item.name + " " + item.description + " " + item.url).toLowerCase().includes(query),
        filterFn: (item, filter) =>
            filter === "all" || item.status.toLowerCase() === filter,
        sorters: {
            "created-desc": (a, b) => b.createdAt - a.createdAt,
            "created-asc": (a, b) => a.createdAt - b.createdAt,
            "name-asc": (a, b) => a.name.localeCompare(b.name),
            "name-desc": (a, b) => b.name.localeCompare(a.name)
        },
        row: item => `
            <tr data-id="${item.id}">
                <td>
                    <div class="eservice-thumb">
                        ${item.image
                            ? `<img src="${esc(item.image)}" alt="">`
                            : `<i data-lucide="globe" aria-hidden="true"></i>`
                        }
                    </div>
                </td>
                <td>
                    <span class="eservice-cell-ellipsis eservice-name" title="${esc(item.name)}">
                        ${esc(item.name)}
                    </span>
                </td>
                <td>
                    <span class="eservice-cell-ellipsis eservice-link" title="${esc(item.url)}">
                        ${esc(item.url || "—")}
                    </span>
                </td>
                <td>
                    <span class="eservice-status ${statusClass(item.status)}">
                        ${esc(item.status)}
                    </span>
                </td>
                <td>
                    <div class="eservice-actions">
                        <button type="button" class="eservice-action-btn" data-action="preview" title="Preview service" aria-label="Preview service">
                            <i data-lucide="eye"></i>
                        </button>
                        <button type="button" class="eservice-action-btn eservice-action-edit" data-action="edit" title="Edit service" aria-label="Edit service">
                            <i data-lucide="pencil"></i>
                        </button>
                        <button type="button" class="eservice-action-btn eservice-action-delete" data-action="delete" title="Delete service" aria-label="Delete service">
                            <i data-lucide="trash-2"></i>
                        </button>
                    </div>
                </td>
            </tr>`,
        onAction: async (action, id) => {
            const item = items.find(entry => entry.id === Number(id));
            if (!item) return;

            if (action === "preview") {
                showPreview(item);
            } else if (action === "edit") {
                openForm(item);
            } else if (action === "delete") {
                await deleteService(item);
            }
        }
    });

    function readForm() {
        return {
            name: $("name").value.trim(),
            status: $("status").value,
            description: $("description").value.trim(),
            url: $("url").value.trim(),
            image: img.get()
        };
    }

    async function parseResponse(response) {
        const contentType = response.headers.get("content-type") || "";
        if (!contentType.includes("application/json")) {
            throw new Error(`Server returned HTTP ${response.status} instead of JSON. Check the API URL and PHP logs.`);
        }

        const result = await response.json();

        if (!response.ok) {
            if (result.data?.redirect) {
                window.location.href = result.data.redirect;
                return null;
            }
            throw new Error(result.message || "Request failed.");
        }

        return result;
    }

    async function saveService() {
        if (saving) return;
        if (!form.reportValidity()) return;

        const data = readForm();
        if (!data.name || !data.description) {
            toast("Please complete the required fields.");
            return;
        }

        const formData = new FormData(form);
        formData.set("id", String(editingId ?? 0));
        formData.set("remove_featured_image", removeExistingImage ? "true" : "false");
        formData.delete("featured_image");

        const file = imageInput.files?.[0];
        if (file) {
            if (!["image/jpeg", "image/png", "image/webp"].includes(file.type)) {
                toast("Please choose a JPEG, PNG, or WebP image.");
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                toast("The image must not exceed 10 MB.");
                return;
            }
            formData.set("featured_image", file);
        }

        saving = true;
        saveBtn.disabled = true;

        try {
            const response = await fetch(API_BASE + "save.php", {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            });

            const result = await parseResponse(response);
            if (!result) return;

            toast(result.message || "E-Service saved successfully.");
            window.location.reload();
        } catch (error) {
            console.error("E-Service save error:", error);
            toast(error.message || "Unable to save E-Service.");
        } finally {
            saving = false;
            saveBtn.disabled = false;
        }
    }

    async function deleteService(item) {
        if (deleting) return;

        const confirmed = await confirmDelete(
            "Delete E-Service?",
            `Are you sure you want to delete "${item.name}"? This will deactivate and remove it from the list.`,
            "Yes, delete"
        );

        if (!confirmed) return;
        deleting = true;

        try {
            const formData = new FormData();
            formData.set("id", String(item.id));
            formData.set("csrf_key", getCsrfToken());

            const response = await fetch(API_BASE + "delete.php", {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            });

            const result = await parseResponse(response);
            if (!result) return;

            toast(result.message || "E-Service deleted successfully.");

            const index = items.findIndex(entry => entry.id === item.id);
            if (index !== -1) {
                items.splice(index, 1);
            }

            table.render();
        } catch (error) {
            console.error("E-Service delete error:", error);
            toast(error.message || "Unable to delete E-Service.");
        } finally {
            deleting = false;
        }
    }

    $("addBtn").addEventListener("click", () => openForm());

    $("formPreviewBtn").addEventListener("click", () => {
        const existing = editingId !== null
            ? items.find(item => item.id === editingId)
            : null;

        showPreview({
            createdBy: existing ? existing.createdBy : "Admin",
            createdAt: existing ? existing.createdAt : Date.now(),
            updatedBy: existing ? existing.updatedBy : "",
            ...readForm()
        });
    });

    form.addEventListener("submit", async event => {
        event.preventDefault();
        await saveService();
    });

    document.querySelectorAll("[data-close-modal]").forEach(button => {
        button.addEventListener("click", closeForm);
    });

    document.querySelectorAll("[data-close-preview]").forEach(button => {
        button.addEventListener("click", () => closeModal(previewModal));
    });

    document.addEventListener("keydown", event => {
        if (event.key !== "Escape") return;

        if (previewModal.classList.contains("open")) {
            closeModal(previewModal);
        } else if (formModal.classList.contains("open")) {
            closeForm();
        }
    });

    table.render();
    initMenu();
    icons();

    console.log("UCC E-Services loaded from MariaDB:", items.length);
})();