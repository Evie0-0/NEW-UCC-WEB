(function (w) {
    "use strict";

    const $ = id => document.getElementById(id);
    const esc = v => String(v == null ? "" : v).replace(/[&<>"']/g, c => ({"&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;"}[c]));
    const icons = () => { if (w.lucide) w.lucide.createIcons(); };
    const pad = n => String(n).padStart(2, "0");
    const todayStr = () => { const d = new Date(); return d.getFullYear() + "-" + pad(d.getMonth() + 1) + "-" + pad(d.getDate()); };
    const fmtDate = ts => ts ? new Date(ts).toLocaleDateString("en-US", {year: "numeric", month: "short", day: "numeric"}) : "—";
    const fmtDay = d => d ? new Date(d + "T00:00:00").toLocaleDateString("en-US", {year: "numeric", month: "short", day: "numeric"}) : "—";
    const isUrl = v => /^https?:\/\//i.test(String(v || "").trim());
    const mapsUrl = q => "https://www.google.com/maps/search/?api=1&query=" + encodeURIComponent(q || "");
    const statusClass = s => {
        s = String(s).toLowerCase();
        return s === "active" ? "status-published" : (s === "expired" ? "status-expired-unit" : "status-hidden-unit");
    };
    const effectiveStatus = it => (it.status === "Active" && it.expiryDate && it.expiryDate < todayStr()) ? "Expired" : it.status;
    const thumb = (src, icon) => '<div class="featured-thumb">' + (src ? '<img src="' + esc(src) + '" alt="">' : '<i data-lucide="' + (icon || "image") + '"></i>') + '</div>';

    function toast(message) {
        const t = $("uiToast");
        if (!t) return;
        t.textContent = message;
        t.classList.add("show");
        clearTimeout(toast.timer);
        toast.timer = setTimeout(() => t.classList.remove("show"), 2400);
    }

    function fillMeta(it) {
        const set = (id, v) => { const el = $(id); if (el) el.textContent = v; };
        set("previewMetadataCreatedBy", it.createdBy || "—");
        set("previewMetadataDateCreated", fmtDate(it.createdAt));
        set("previewMetadataLastUpdatedBy", it.updatedBy || "—");
        set("previewMetadataExpiry", it.expiryDate ? fmtDay(it.expiryDate) : "No expiry");
    }
    const offsetDate = n => { const d = new Date(); d.setDate(d.getDate() + n); return d.getFullYear() + "-" + pad(d.getMonth() + 1) + "-" + pad(d.getDate()); };

    function openModal(m) { m.classList.add("open"); m.setAttribute("aria-hidden", "false"); icons(); }
    function closeModal(m) { m.classList.remove("open"); m.setAttribute("aria-hidden", "true"); }

    async function confirmDelete(title, text, button) {
        if (w.Swal) {
            const res = await Swal.fire({title, text, icon: "warning", showCancelButton: true, confirmButtonText: button || "Yes, delete", confirmButtonColor: "#b42318"});
            return res.isConfirmed;
        }
        return confirm(title + "\n" + (text || ""));
    }

    function imageField(o) {
        let data = "";
        function set(src) {
            data = src || "";
            if (data) {
                o.preview.src = data; o.preview.hidden = false; o.preview.style.display = "block";
                o.placeholder.hidden = true; o.placeholder.style.display = "none";
                o.removeBtn.hidden = false;
            } else {
                o.preview.removeAttribute("src"); o.preview.hidden = true; o.preview.style.display = "none";
                o.placeholder.hidden = false; o.placeholder.style.display = "flex";
                o.removeBtn.hidden = true;
            }
        }
        o.chooseBtn.addEventListener("click", () => o.fileInput.click());
        o.removeBtn.addEventListener("click", () => { set(""); o.fileInput.value = ""; });
        o.fileInput.addEventListener("change", () => {
            const f = o.fileInput.files && o.fileInput.files[0];
            if (!f) return;
            if (!f.type.startsWith("image/")) { toast("Please choose an image file."); o.fileInput.value = ""; return; }
            const r = new FileReader();
            r.onload = e => set(e.target.result);
            r.readAsDataURL(f);
        });
        set("");
        return {set, get: () => data, reset: () => { set(""); o.fileInput.value = ""; }};
    }

    function pageList(cur, last) {
        if (last <= 7) return Array.from({length: last}, (_, i) => i + 1);
        const out = [1], s = Math.max(2, cur - 1), e = Math.min(last - 1, cur + 1);
        if (s > 2) out.push("...");
        for (let i = s; i <= e; i++) out.push(i);
        if (e < last - 1) out.push("...");
        out.push(last);
        return out;
    }

    function list(c) {
        let showingAll = false, page = 1;
        const pageSize = c.pageSize || 10;
        const pg = c.pager || null; 

        function renderPager(totalRows) {
            const last = Math.max(1, Math.ceil(totalRows / pageSize));
            pg.nav.hidden = totalRows === 0;
            pg.numbers.innerHTML = "";
            pageList(page, last).forEach(p => {
                if (p === "...") {
                    const s = document.createElement("span");
                    s.className = "page-ellipsis"; s.textContent = "…";
                    pg.numbers.appendChild(s);
                    return;
                }
                const b = document.createElement("button");
                b.type = "button";
                b.className = "page-btn" + (p === page ? " active" : "");
                b.textContent = p;
                b.setAttribute("aria-label", "Page " + p);
                if (p === page) b.setAttribute("aria-current", "page");
                b.addEventListener("click", () => { page = p; render(); });
                pg.numbers.appendChild(b);
            });
            pg.prev.disabled = page <= 1;
            pg.next.disabled = page >= last;
            pg.total.textContent = last + (last === 1 ? " page" : " pages");
        }
        function render() {
            const q = c.search ? c.search.value.trim().toLowerCase() : "";
            const f = c.filter ? c.filter.value : "";
            const rows = c.items().filter(it => c.match(it, q) && (!c.filterFn || c.filterFn(it, f)));
            const sorter = c.sort && c.sorters ? c.sorters[c.sort.value] : null;
            rows.sort(sorter || c.defaultSort || (() => 0));

            let visible;
            if (pg) {
                const last = Math.max(1, Math.ceil(rows.length / pageSize));
                if (page > last) page = last;
                visible = rows.slice((page - 1) * pageSize, page * pageSize);
            } else {
                visible = showingAll ? rows : rows.slice(0, pageSize);
            }

            c.tbody.innerHTML = rows.length
                ? visible.map(c.row).join("")
                : '<tr><td colspan="' + c.cols + '" class="empty-row">' + esc(c.emptyText) + '</td></tr>';
            c.count.textContent = c.countLabel(rows.length);

            if (pg) {
                renderPager(rows.length);
            } else {
                const more = rows.length > pageSize;
                if (c.less) {
                    c.more.hidden = !more || showingAll;
                    c.less.hidden = !more || !showingAll;
                } else {
                    c.more.hidden = !more;
                    c.more.textContent = showingAll ? "Show less" : "Show more";
                }
                c.end.hidden = !(rows.length > 0 && (!more || showingAll));
            }
            icons();
        }

        const reset = () => { showingAll = false; page = 1; render(); };
        if (c.search) c.search.addEventListener("input", reset);
        if (c.filter) c.filter.addEventListener("change", reset);
        if (c.sort) c.sort.addEventListener("change", reset);

        if (pg) {
            pg.prev.addEventListener("click", () => { if (page > 1) { page--; render(); } });
            pg.next.addEventListener("click", () => { page++; render(); });
        } else {
            c.more.addEventListener("click", () => { showingAll = c.less ? true : !showingAll; render(); });
            if (c.less) c.less.addEventListener("click", () => { showingAll = false; render(); });
        }

        c.tbody.addEventListener("click", e => {
            const btn = e.target.closest("[data-action]");
            if (!btn) return;
            const row = btn.closest("tr[data-id]");
            if (!row) return;
            c.onAction(btn.dataset.action, Number(row.dataset.id));
        });

        return {render, reset};
    }

    function initMenu() {
        document.querySelectorAll(".sidebar .menu-title").forEach(btn => btn.addEventListener("click", () => {
            const group = btn.closest(".menu-group");
            if (!group) return;
            btn.setAttribute("aria-expanded", String(group.classList.toggle("open")));
        }));
        icons();
    }

    w.UCC = {$, esc, icons, todayStr, fmtDate, fmtDay, isUrl, mapsUrl, statusClass, effectiveStatus, thumb, fillMeta, offsetDate, toast, openModal, closeModal, confirmDelete, imageField, list, initMenu};
})(window);