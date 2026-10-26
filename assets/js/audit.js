(function () {
    const {$, esc, list, icons, initMenu} = UCC;

    async function loadAudit() {
        try {
            const params = new URLSearchParams({
                search: $("auditSearch").value.trim(),
                filter: $("auditFilter").value,
                sort: $("auditSort").value,
                date: $("auditDate").value    
            });

            const response = await fetch("api/audit/filter.php?" + params.toString());
            const result = await response.json();

            if (!response.ok) {
                await showAlert(false, result.message);
                return false;
            }

            items.splice(0, items.length, ...result.data.map(fromApi));
            table.reset();
            return true;
        } catch (error) {
            await showUnexpectedError(error);
            return false;
        }
    }

    const fromApi = r => ({
        id: Number(r.id),
        action: r.action,
        description: r.description,
        performedBy: r.performed_by,
        createdAt: new Date(r.created_at).getTime()
    });

    const items = [];

    const actionCls = a => ({Create: "status-published", Update: "status-pending", Delete: "status-expired", Archive: "status-archived"}[a] || "status-draft");
    const fmtDateTime = ts => new Date(ts).toLocaleString("en-PH", {year: "numeric", month: "short", day: "numeric", hour: "numeric", minute: "2-digit"});

    const noSort = () => 0;
    const table = list({
        items: () => items,
        tbody: $("auditTableBody"), search: $("auditSearch"), filter: $("auditFilter"), sort: $("auditSort"),
        count: $("auditCount"), pageSize: 10,
        pager: {nav: $("auditPagination"), prev: $("prevPageBtn"), next: $("nextPageBtn"), numbers: $("pageNumbers"), total: $("pageTotal")},
        cols: 4, emptyText: "No audit entries found.",
        countLabel: n => n + (n === 1 ? " entry" : " entries"),
        match: () => true,
        filterFn: () => true,
        sorters: {
            "created-desc": noSort, "created-asc": noSort,
            "action-asc": noSort, "action-desc": noSort,
            "by-asc": noSort, "by-desc": noSort
        },
        row: it => `
            <tr data-id="${it.id}">
                <td><span class="status-badge ${actionCls(it.action)}">${esc(it.action)}</span></td>
                <td><span class="cell-ellipsis" title="${esc(it.description)}">${esc(it.description)}</span></td>
                <td><span class="cell-ellipsis">${esc(it.performedBy)}</span></td>
                <td><span class="cell-ellipsis">${esc(fmtDateTime(it.createdAt))}</span></td>
            </tr>`,
        onAction: () => {}
    });

    let searchTimer;
    $("auditSearch").addEventListener("input", () => { clearTimeout(searchTimer); searchTimer = setTimeout(loadAudit, 300); });
    $("auditFilter").addEventListener("change", loadAudit);
    $("auditSort").addEventListener("change", loadAudit);
    $("auditDate").addEventListener("change", loadAudit);

    table.render();
    initMenu();
    icons();

    loadAudit();
})();