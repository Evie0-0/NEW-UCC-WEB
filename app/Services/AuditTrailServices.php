<?php
declare(strict_types=1);

namespace Serv;

use Core\Database;
use Repo\AuditTrailRepository;

final class AuditTrailServices {
    private const SORT_KEYS = ['added-desc', 'added-asc', 'description-desc', 'description-asc', 'username-asc', 'username-desc'];
    private const PER_PAGE = 10;

    public function actions(): void {
        $repo = new AuditTrailRepository(Database::connect());
        $fetchedActions = $repo->actions();

        jsonResponse(200, 'Actions successfully fetched.', $fetchedActions);
    }

    public function filter(): void {
        $search = is_string($_GET['search'] ?? null) ? trim($_GET['search']) : '';
        $filter = is_string($_GET['filter'] ?? null) ? $_GET['filter'] : '';
        $sort = is_string($_GET['sort'] ?? null) ? $_GET['sort'] : '';
        $page = validateInt($_GET['page'] ?? 1, 1, null);

        $repo = new AuditTrailRepository(Database::connect());
        $fetchedActions = $repo->actions();

        if (!in_array(strtoupper($filter), array_map('strtoupper', $fetchedActions), true)) {
            jsonResponse(422, 'Invalid filter selection.');
        }

        $filter = strtolower($filter) === 'all' ? 'all' : strtoupper($filter);

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

        $totalItems = $repo->countFiltered($data);
        $totalPages = ceil($totalItems / self::PER_PAGE);

        if ($page > max(1, $totalPages)) {
            jsonResponse(422, 'Requested page does not exist.');
        }

        $auditTrails = $repo->filter([
            ...$data,
            'offset' => ($page - 1) * self::PER_PAGE
        ]);

        jsonResponse(200, 'Successfully filtered audit trails.', [
            'items' => $auditTrails,
            'current_page' => $page,
            'total_items' => $totalItems,
            'total_pages' => $totalPages
        ]);
    }
}
