<?php
declare(strict_types=1);

namespace Repo;

use PDO;

final class CategoryRepository {
    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    /** @return list<array<string, mixed>> */
    public function fetchItemsByClass(string $class): array {
        $query = 'SELECT * FROM categories
            WHERE class = ?
            AND active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$class]);

        return $stmt->fetchAll();
    }

    public function fetchItemById(int $id): array|false {
        $query = 'SELECT * FROM categories
            WHERE id = ?
            AND active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    /** @return list<array<string, mixed>> */
    public function fetchItems(): array {
        $query = 'SELECT * FROM categories
            WHERE active = 1';
        $stmt = $this->conn->query($query);

        return $stmt->fetchAll();
    }
}
