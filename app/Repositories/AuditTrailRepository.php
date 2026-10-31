<?php
declare(strict_types=1);

namespace Repo;

use PDO;

final class AuditTrailRepository {
    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    /** @param array<string, mixed> $data */
    public function insertItem(array $data): void {
        $query = 'INSERT INTO audit_trail (action, description, entity_type,
            entity_id, user_id)
            VALUES (?,?,?,?,?)';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['action'],
            $data['description'],
            $data['entity_type'],
            $data['entity_id'],
            $data['user_id']
        ]);
    }
}
