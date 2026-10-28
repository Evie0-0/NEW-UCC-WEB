<?php
declare(strict_types=1);

namespace Repo;

use InvalidArgumentException;
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

    /** @param array<string, mixed> $data */
    /** @return array{0: string, 1: array<string, mixed>} */
    private function buildWhere(array $data): array {
        $conditions = [
            'active = 1'
        ];

        $params = [];

        if ($data['search'] !== '') {
            $conditions[] = 'description LIKE :search';
            $params['search'] = "%{$data['search']}%";
        }

        if ($data['filter'] !== 'all') {
            $conditions[] = 'action = :action';
            $params['action'] = $data['filter'];
        }

        $where = implode(' AND ', $conditions);

        return [$where, $params];
    }

    /** @param array<string, mixed> $data */
    public function countFiltered(array $data): int {
        [$where, $params] = $this->buildWhere($data);

        $query = "
            SELECT COUNT(*)
            FROM audit_trail
            WHERE $where
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchColumn();
    }

    /** @param array<string, mixed> $data */
    /** @return array<int, array<string, mixed>> */
    public function filter(array $data): array {
        [$where, $params] = $this->buildWhere($data);

        $orderBy = match ($data['sort']) {
            'create-desc' => 'created_at DESC',
            'create-asc' => 'created_at ASC',
            'description-desc' => 'description DESC',
            'description-asc' => 'description ASC',
            default => throw new InvalidArgumentException('Invalid audit trail sort.')
        };

        $query = "
            SELECT *
            FROM audit_trail
            WHERE $where
            ORDER BY $orderBy, id DESC
            LIMIT 10 OFFSET :offset
        ";

        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value, PDO::PARAM_STR);
        }

        $stmt->bindValue(':offset', $data['offset'], PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
