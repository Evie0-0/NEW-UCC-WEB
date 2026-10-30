<?php
declare(strict_types=1);

namespace Serv;

use Core\Database;
use Repo\OfficialRepository;

final class OfficialServices {
    private const FILTER_KEYS = ['all', 'active', 'inactive'];
    private const SORT_KEYS = ['order-asc', 'order-desc', 'name-asc', 'name-desc', 'position-asc', 'position-desc', 'added-desc', 'added-asc'];
    private const PER_PAGE = 10;

    public function order(): void {

    }

    public function filter(): void {
        $search = is_string($_GET['search'] ?? null) ? trim($_GET['search']) : '';
        $filter = is_string($_GET['filter'] ?? null) ? $_GET['filter'] : '';
        $sort = is_string($_GET['sort'] ?? null) ? $_GET['sort'] : '';
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

        $repo = new OfficialRepository(Database::connect());
        $totalOfficials = $repo->countFiltered($data);
        $totalPages = ceil($totalOfficials / self::PER_PAGE);

        if ($page > max(1, $totalPages)) {
            jsonResponse(422, 'Requested page does not exist.');
        }

        $officials = $repo->filter([
            ...$data,
            'offset' => ($page - 1) * self::PER_PAGE
        ]);

        jsonResponse(200, 'Successfully filtered official items.', [
            'items' => $officials,
            'current_page' => $page,
            'total_items' => $totalOfficials,
            'total_pages' => $totalPages
        ]);
    }
}
