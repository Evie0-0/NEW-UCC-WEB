<?php
declare(strict_types=1);

namespace Repo;

use InvalidArgumentException;
use PDO;

final class OfficialRepository {
    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function fetchLatestOrder(): int {
    }

    /** @param array<string, mixed> $data */
    /** @return array{0: string, 1: array<string, mixed>} */
    private function buildWhere(array $data): array {
        $conditions = [
            'active = 1'
        ];

        $params = [];

        if ($data['search'] !== '') {
            $conditions[] = '(name LIKE :search_name OR position LIKE :search_position)';
            $params['search_name'] = "%{$data['search']}%";
            $params['search_position'] = "%{$data['search']}%";
        }

        if ($data['filter'] !== 'all') {
            $conditions[] = 'status = :status';
            $params['status'] = $data['filter'];
        }

        return [implode(' AND ', $conditions), $params];
    }

    /** @param array<string, mixed> $data */
    public function countFiltered(array $data): int {
        [$where, $params] = $this->buildWhere($data);

        $query = "SELECT COUNT(*)
            FROM officials
            WHERE $where
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /** @param array<string, mixed> $data */
    /** @return array<int, array<string, mixed>> */
    public function filter(array $data): array {
        [$where, $params] = $this->buildWhere($data);

        $orderBy = match ($data['sort']) {
            'order-asc' => 'order_number ASC',
            'order-desc' => 'order_number DESC',
            'name-asc' => 'name ASC',
            'name-desc' => 'name DESC',
            'position-asc' => 'position ASC',
            'position-desc' => 'position DESC',
            'added-desc' => 'created_at DESC',
            'added-asc' => 'created_at ASC',
            default => throw new InvalidArgumentException('Invalid official sort.')
        };

        $query = "SELECT officials.*
            FROM officials
            WHERE $where
            ORDER BY $orderBy, id DESC
            LIMIG 10 OFFSET :offset
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
