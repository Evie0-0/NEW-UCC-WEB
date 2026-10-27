<?php
declare(strict_types=1);

namespace Serv;

use Core\Database;
use Repo\AuditTrailRepository;

final class AuditTrailServices {
    private const FILTER_KEYS = ['all', 'CREATE', 'UPDATE', 'LOGIN'];
    private const SORT_KEYS = ['create-desc', 'create-asc', 'description-desc', 'description-asc'];
    private const PER_PAGE = 10;

    public function filter(): void {
        $search = trim(validateString($_GET['search'] ?? '', 'Search'));
        $filter = $_GET['filter'] ?? '';
        $sort = $_GET['sort'] ?? '';
        $page = validateInt($_GET['page'] ?? 1, 1, null);

        if (!in_array($filter, self::FILTER_KEYS, true)) {
            jsonResponse(422, 'Invalid filter selection.');
        }

        if (!in_array($sort, self::SORT_KEYS, true)) {
            jsonResponse(422, 'Invalid sort selection.');
        }

        if ($page === false) {
            jsonResponse(422, 'Invalid page number.');
        }

        $data = [
            'search' => $search,
            'filter' => $filter,
            'sort' => $sort
        ];

        $repo = new AuditTrailRepository(Database::connect());
        $totalItems = $repo->countFiltered($data);
        $totalPages = ceil($totalItems / self::PER_PAGE);

        if ($page > max(1, $totalPages)) {
            jsonResponse(422, 'Requested page does not exist.');
        }

        $offset = ($page - 1) * self::PER_PAGE;

        $auditTrails = $repo->filter([
            ...$data,
            'offset' => $offset
        ]);

        // REMINDER: add user_display in result
        jsonResponse(200, 'Successfully filtered audit trails.', [
            'items' => $auditTrails,
            'current_page' => $page,
            'total_items' => $totalItems,
            'total_pages' => $totalPages
        ]);
    }
}
