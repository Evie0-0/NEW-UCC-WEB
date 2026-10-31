<?php
declare(strict_types=1);

namespace Serv;

final class AuditTrailServices {
    private const FILTER_KEYS = [];

    public function filter(): void {
        $search = trim(validateString($_GET['search'] ?? '', 'Search'));
        $filter = $_GET['filter'] ?? '';
        $sort = $_GET['sort'] ?? '';

        // REMINDER: add offset
    }
}
