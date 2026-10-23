<?php
declare(strict_types=1);

namespace Repo;

use PDO;

final class AuditTrailRepository {
    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }
}
