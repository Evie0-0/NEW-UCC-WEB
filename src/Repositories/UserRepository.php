<?php
declare(strict_types=1);

namespace Repositories;

use PDO;

final class UserRepository {
    public function __construct(
        private readonly PDO $connection
    ) {}

    public function getItemByUsername(string $username): array|false {
        $query = "SELECT * FROM users
            WHERE username = ?
            AND active = 1";
        $statement = $this->connection->prepare($query);
        $statement->execute([$username]);

        return $statement->fetch();
    }

    public function updateLastLoggedIn(int $id): void {
        $query = "UPDATE users
            SET last_logged_in = CURRENT_TIMESTAMP
            WHERE id = ?";
        $statement = $this->connection->prepare($query);
        $statement->execute([$id]);
    }
}
