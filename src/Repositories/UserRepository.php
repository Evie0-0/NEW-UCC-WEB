<?php
declare(strict_types=1);

namespace Repositories;

use PDO;

final class UserRepository {
    public function __construct(private readonly PDO $connection) {}

    public function getItemByUsername(string $username): array|false {
        $query = "SELECT * FROM users
            WHERE username = ?
            AND active = 1";
        $stmt = $this->connection->prepare($query);
        $stmt->execute([$username]);

        return $stmt->fetch();
    }
}
