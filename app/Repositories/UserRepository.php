<?php
declare(strict_types=1);

namespace Repo;

use PDO;

final class UserRepository {
    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function findByUsername(string $username): array|false {
        $query = 'SELECT * FROM users
            WHERE username = ?
            AND active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$username]);

        return $stmt->fetch();
    }
}
