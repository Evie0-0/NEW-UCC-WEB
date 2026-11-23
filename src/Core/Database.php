<?php
declare(strict_types=1);

namespace Core;

use PDO;

final class Database {
    private ?PDO $conn = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(private readonly array $config) {}

    public function connect(): PDO {
        if ($this->conn !== null) {
            return $this->conn;
        }

        $dsn = sprintf(
            '%s:host=%s;dbname=%s;charset=%s',
            $this->config['driver'],
            $this->config['host'],
            $this->config['database'],
            $this->config['charset'] ?? 'utf8mb4'
        );

        $this->conn = new PDO(
            $dsn,
            $this->config['username'],
            $this->config['password'],
            $this->config['options']
        );

        return $this->conn;
    }
}
