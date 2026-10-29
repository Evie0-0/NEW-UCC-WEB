import { formatTimestamp, showAlert, showUnexpectedError } from './common.js';

window.lucide?.createIcons();

// Load actions
const actionSelect = document.getElementById('auditFilter');

async function loadActions() {
    actionSelect.disabled = true;
    actionSelect.replaceChildren(new Option('Loading actions…', ''));

    try {
        const response = await fetch('../../api/audit-trail/actions.php');
        const result = await response.json();

        if (!response.ok) {
            if (result.data?.redirect) {
                window.location.href = result.data.redirect;
                return;
            }

            actionSelect.replaceChildren(new Option('Unable to load actions', ''));
            showTableMessage('Could not load audit trails. Please try again.');
            await showAlert(false, result.message);
            return;
        }

        actionSelect.replaceChildren();

        result.data.forEach(action => {
            const value = action.toLowerCase();
            const label = value === 'all'
                ? 'All actions'
                : value.charAt(0).toUpperCase() + value.slice(1);

            actionSelect.add(new Option(
                label,
                value,
                value === 'all',
                value === 'all'
            ));
        });

        if (actionSelect.options.length === 0) {
            actionSelect.add(new Option('No actions available', ''));
            showTableMessage('No audit trails found.');
            return;
        }

        actionSelect.disabled = false;
        await loadAuditTrails();
    } catch (error) {
        actionSelect.replaceChildren(new Option('Unable to load actions', ''));
        actionSelect.disabled = true;
        showTableMessage('Could not load audit trails. Please try again.');
        await showUnexpectedError(error);
    }
}

// Filter
const searchInput = document.getElementById('auditSearch');
const sortSelect = document.getElementById('auditSort');
const tableBody = document.getElementById('auditTableBody');
const rowTemplate = document.getElementById('auditRowTemplate');
const itemCount = document.getElementById('auditCount');
const pagination = document.getElementById('auditPagination');
const prevBtn = document.getElementById('prevPageBtn');
const nextBtn = document.getElementById('nextPageBtn');
const pageNumbers = document.getElementById('pageNumbers');
const pageTotal = document.getElementById('pageTotal');
let currentPage = 1;
let totalPages = 0;
let controller = null;
let timer = null;

function renderAuditRow(item) {
    const row = rowTemplate.content.firstElementChild.cloneNode(true);
    const action = String(item.action || '—').toLowerCase();
    const values = {
        action: action.charAt(0).toUpperCase() + action.slice(1),
        description: item.description || '—',
        username: item.username || '—',
        createdAt: formatTimestamp(item.created_at)
    };

    for (const [field, value] of Object.entries(values)) {
        const cell = row.querySelector(`[data-field="${field}"]`);
        cell.textContent = value;
        cell.title = value;
    }

    return row;
}

function showTableMessage(message) {
    const row = document.createElement('tr');
    const cell = document.createElement('td');
    cell.colSpan = 4;
    cell.className = 'empty-row';
    cell.textContent = message;
    row.append(cell);
    tableBody.replaceChildren(row);
}

function renderPagination() {
    pagination.hidden = totalPages <= 1;
    prevBtn.disabled = currentPage <= 1;
    nextBtn.disabled = currentPage >= totalPages;
    pageNumbers.replaceChildren();
    pageTotal.textContent = `${totalPages} ${totalPages === 1 ? 'page' : 'pages'}`;

    const start = Math.max(1, currentPage - 2);
    const end = Math.min(totalPages, currentPage + 2);
    for (let page = start; page <= end; page += 1) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `page-btn${page === currentPage ? ' active' : ''}`;
        button.textContent = String(page);
        button.setAttribute('aria-label', `Page ${page}`);
        if (page === currentPage) button.setAttribute('aria-current', 'page');
        button.addEventListener('click', () => loadAuditTrails(page));
        pageNumbers.append(button);
    }
}

async function loadAuditTrails(page = 1) {
    clearTimeout(timer);
    if (actionSelect.disabled) return;

    controller?.abort();
    const requestController = new AbortController();
    controller = requestController;
    tableBody.setAttribute('aria-busy', 'true');

    const params = new URLSearchParams({
        search: searchInput.value.trim(),
        filter: actionSelect.value,
        sort: sortSelect.value,
        page: String(page)
    });

    try {
        const response = await fetch(`../../api/audit-trail/filter.php?${params}`, {
            signal: requestController.signal
        });
        const result = await response.json();
        if (requestController.signal.aborted) return;

        if (!response.ok) {
            if (result.data?.redirect) {
                window.location.href = result.data.redirect;
                return;
            }

            showTableMessage('Could not load audit trails. Please try again.');
            itemCount.textContent = '— logs';
            pagination.hidden = true;
            await showAlert(false, result.message);
            return;
        }

        const { items, current_page: returnedPage, total_items: totalItems, total_pages: returnedTotalPages } = result.data;
        if (items.length === 0) {
            showTableMessage('No audit trails found.');
        } else {
            tableBody.replaceChildren(...items.map(renderAuditRow));
        }

        currentPage = Number(returnedPage);
        totalPages = Number(returnedTotalPages);
        itemCount.textContent = `${totalItems} ${Number(totalItems) === 1 ? 'log' : 'logs'}`;
        renderPagination();
    } catch (error) {
        if (error.name === 'AbortError' || requestController.signal.aborted) return;

        showTableMessage('Could not load audit trails. Please try again.');
        itemCount.textContent = '— logs';
        pagination.hidden = true;
        await showUnexpectedError(error);
    } finally {
        if (controller === requestController) tableBody.setAttribute('aria-busy', 'false');
    }
}

searchInput.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(() => loadAuditTrails(1), 300);
});
actionSelect.addEventListener('change', () => loadAuditTrails(1));
sortSelect.addEventListener('change', () => loadAuditTrails(1));
prevBtn.addEventListener('click', () => {
    if (currentPage > 1) loadAuditTrails(currentPage - 1);
});
nextBtn.addEventListener('click', () => {
    if (currentPage < totalPages) loadAuditTrails(currentPage + 1);
});

showTableMessage('Loading audit trails…');
loadActions();
