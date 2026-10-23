<?php
declare(strict_types=1);

namespace Repo;

use PDO;

final class PostRepository {
    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    /** @return list<array<string, mixed>> */
    public function fetchCategories(): array {
        $query = "SELECT * FROM categories
            WHERE class = 'university-posts'
            AND active = 1";
        $stmt = $this->conn->query($query);

        return $stmt->fetchAll();
    }
}
