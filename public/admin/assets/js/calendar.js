(function () {
    const {$, esc, thumb, fmtDay, todayStr, toast, openModal, closeModal, confirmDelete, imageField, list, icons, initMenu} = UCC;

    const effectiveStatus = it => (it.status === "Published" && it.publishDate > todayStr()) ? "Pending" : it.status;

    const off = n => { const d = new Date(); d.setDate(d.getDate() + n); return d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0") + "-" + String(d.getDate()).padStart(2, "0"); };
    const day = 86400000;
    const mk = (id, title, status, pub, start, end, ago) => ({
        id, title, status, publishDate: off(pub), startDate: off(start), endDate: off(end),
        description: "Sample description for: " + title + ".", image: "", createdBy: "CMS", createdAt: Date.now() - ago * day
    });
    const items = [
        mk(1, "Foundation Day Celebration", "Published", -10, 5, 7, 12),
        mk(2, "Midterm Examination Week", "Published", -3, 14, 18, 8),
        mk(3, "Enrollment Period", "Published", 2, 20, 30, 5),
        mk(4, "Leadership Summit", "Draft", 0, 25, 26, 3),
    ];
    let nextId = 5, editingId = null, dateVal = "";

    const statusCls = s => ({Published: "status-published", Draft: "status-draft", Pending: "status-pending"}[s] || "status-draft");
    const formModal = $("eventModal"), previewModal = $("eventPreviewModal"), form = $("eventForm");
    const img = imageField({fileInput: $("featuredImage"), chooseBtn: $("uploadImageBtn"), removeBtn: $("removeImageBtn"), preview: $("imagePreview"), placeholder: $("uploadPlaceholder")});

    const table = list({
        items: () => dateVal ? items.filter(i => i.publishDate === dateVal) : items,
        tbody: $("calendarTableBody"), search: $("calendarSearch"), filter: $("calendarFilter"), sort: $("calendarSort"),
        count: $("calendarCount"), pageSize: 10,
        pager: {nav: $("calendarPagination"), prev: $("prevPageBtn"), next: $("nextPageBtn"), numbers: $("pageNumbers"), total: $("pageTotal")},
        cols: 7, emptyText: "No events found.",
        countLabel: n => n + (n === 1 ? " event" : " events"),
        match: (it, q) => it.title.toLowerCase().includes(q),   // title only
        filterFn: (it, f) => f === "All" || effectiveStatus(it) === f,
        sorters: {
            "created-desc": (a, b) => b.createdAt - a.createdAt,
            "created-asc": (a, b) => a.createdAt - b.createdAt,
            "date-desc": (a, b) => b.publishDate.localeCompare(a.publishDate),
            "date-asc": (a, b) => a.publishDate.localeCompare(b.publishDate),
            "start-asc": (a, b) => a.startDate.localeCompare(b.startDate),
            "start-desc": (a, b) => b.startDate.localeCompare(a.startDate),
            "title-asc": (a, b) => a.title.localeCompare(b.title),
            "title-desc": (a, b) => b.title.localeCompare(a.title)
        },
        row: it => {
            const st = effectiveStatus(it);
            return `
            <tr data-id="${it.id}">
                <td>${thumb(it.image, "image")}</td>
                <td class="post-title-cell"><span class="cell-ellipsis" title="${esc(it.title)}">${esc(it.title)}</span></td>
                <td><span class="cell-ellipsis">${esc(fmtDay(it.publishDate))}</span></td>
                <td><span class="cell-ellipsis">${esc(fmtDay(it.startDate))}</span></td>
                <td><span class="cell-ellipsis">${esc(fmtDay(it.endDate))}</span></td>
                <td><span class="status-badge ${statusCls(st)}">${esc(st)}</span></td>
                <td><div class="action-buttons">
                    <button class="action-btn" data-action="preview" type="button" title="Preview event" aria-label="Preview event"><i data-lucide="eye"></i></button>
                    <button class="action-btn edit" data-action="edit" type="button" title="Edit event" aria-label="Edit event"><i data-lucide="pencil"></i></button>
                    <button class="action-btn delete" data-action="delete" type="button" title="Delete event" aria-label="Delete event"><i data-lucide="trash-2"></i></button>
                </div></td>
            </tr>`;
        },
        onAction: async (action, id) => {
            const it = items.find(x => x.id === id);
            if (!it) return;
            if (action === "preview") showPreview(it);
            if (action === "edit") openForm(it);
            if (action === "delete" && await confirmDelete("Delete this event?", it.title, "Yes, delete")) {
                items.splice(items.indexOf(it), 1);
                table.render(); toast("Event deleted.");
            }
        }
    });

    function openForm(it) {
        editingId = it ? it.id : null;
        $("eventFormTitle").textContent = it ? "Edit Event" : "Add New Event";
        $("eventFormDescription").textContent = it ? "Update this event." : "Create an event for the university calendar.";
        $("eventTitle").value = it ? it.title : "";
        $("eventDescription").value = it ? it.description : "";
        $("eventStatus").value = it ? it.status : "Draft";
        $("eventPublishDate").value = it ? it.publishDate : todayStr();
        $("eventStartDate").value = it ? it.startDate : "";
        $("eventEndDate").value = it ? it.endDate : "";
        img.set(it ? it.image : "");
        openModal(formModal);
        $("eventTitle").focus();
    }
    function closeForm() { closeModal(formModal); editingId = null; form.reset(); img.reset(); }

    function showPreview(it) {
        $("previewTitle").textContent = it.title || "Event title";
        $("previewDescription").textContent = it.description || "";
        const pi = $("previewImage"), pd = document.querySelector("#previewImageWrap .preview-default-image");
        if (it.image) { pi.src = it.image; pi.hidden = false; pd.hidden = true; }
        else { pi.removeAttribute("src"); pi.hidden = true; pd.hidden = false; }
        $("previewPublish").textContent = it.publishDate ? fmtDay(it.publishDate) : "—";
        $("previewStart").textContent = it.startDate ? fmtDay(it.startDate) : "—";
        $("previewEnd").textContent = it.endDate ? fmtDay(it.endDate) : "—";
        $("previewCreatedBy").textContent = it.createdBy || "—";
        openModal(previewModal);
    }

    const readForm = () => ({
        title: $("eventTitle").value.trim(), description: $("eventDescription").value.trim(),
        status: $("eventStatus").value, publishDate: $("eventPublishDate").value || todayStr(),
        startDate: $("eventStartDate").value, endDate: $("eventEndDate").value, image: img.get()
    });

    $("addEventBtn").addEventListener("click", () => openForm(null));
    $("calendarDate").addEventListener("change", e => { dateVal = e.target.value; table.reset(); });
    $("formPreviewBtn").addEventListener("click", () => {
        const existing = editingId !== null ? items.find(x => x.id === editingId) : null;
        showPreview({createdBy: existing ? existing.createdBy : "CMS", ...readForm()});
    });
    form.addEventListener("submit", e => {
        e.preventDefault();
        const data = readForm();
        if (!data.title || !data.description || !data.startDate || !data.endDate) return;
        if (data.endDate < data.startDate) { toast("End date can't be before the start date."); return; }
        if (editingId !== null) {
            Object.assign(items.find(x => x.id === editingId), data);
            toast("Event updated.");
        } else {
            items.push({id: nextId++, createdBy: "CMS", createdAt: Date.now(), ...data});
            toast("Event added.");
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