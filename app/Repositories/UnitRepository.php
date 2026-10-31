<?php
declare(strict_types=1);

namespace Repo;

use InvalidArgumentException;
use PDO;

final class UnitRepository {
    private const SELECT = 'SELECT u.*, ua.username AS created_by, ue.username AS updated_by
        FROM units u
        LEFT JOIN users ua ON ua.id = u.author_id
        LEFT JOIN users ue ON ue.id = u.editor_id';

    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    /** @param array<string, mixed> $data */
    public function insertItem(array $data): int {
        $query = 'INSERT INTO units (name, logo, dean, dean_image, status, author_id)
            VALUES (?,?,?,?,?,?)';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['name'],
            $data['logo'],
            $data['dean'],
            $data['dean_image'],
            $data['status'],
            $data['author_id']
        ]);

        return (int) $this->conn->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function updateItem(array $data): void {
        $query = 'UPDATE units SET name = ?, logo = ?, dean = ?, dean_image = ?,
            status = ?, editor_id = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
            AND active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['name'],
            $data['logo'],
            $data['dean'],
            $data['dean_image'],
            $data['status'],
            $data['editor_id'],
            $data['id']
        ]);
    }

    public function fetchItemById(int $id): array|false {
        $query = self::SELECT . '
            WHERE u.id = ?
            AND u.active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    // The public website finds a college by its name, so names must stay unique
    public function existsByName(string $name, int $excludeId): bool {
        $query = 'SELECT 1
            FROM units
            WHERE name = ?
            AND id <> ?
            AND active = 1
            LIMIT 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$name, $excludeId]);

        return $stmt->fetchColumn() !== false;
    }

    public function countPrograms(int $unitId): int {
        $query = 'SELECT COUNT(*)
            FROM programs
            WHERE unit_id = ?
            AND active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$unitId]);

        return (int) $stmt->fetchColumn();
    }

    public function countUsers(int $unitId): int {
        $query = 'SELECT COUNT(*)
            FROM users
            WHERE unit_id = ?
            AND active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$unitId]);

        return (int) $stmt->fetchColumn();
    }

    /** @param array<string, mixed> $data */
    /** @return array{0: string, 1: array<string, mixed>} */
    private function buildWhere(array $data): array {
        $conditions = ['u.active = 1'];
        $params = [];

        if ($data['search'] !== '') {
            $conditions[] = '(u.name LIKE :search_name OR u.dean LIKE :search_dean)';
            $params['search_name'] = "%{$data['search']}%";
            $params['search_dean'] = "%{$data['search']}%";
        }

        if ($data['filter'] !== 'all') {
            $conditions[] = 'u.status = :status';
            $params['status'] = $data['filter'];
        }

        $where = implode(' AND ', $conditions);

        return [$where, $params];
    }

    /** @param array<string, mixed> $data */
    public function countFiltered(array $data): int {
        [$where, $params] = $this->buildWhere($data);

        $query = "SELECT COUNT(*)
            FROM units u
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
            'create-desc' => 'u.created_at DESC',
            'create-asc' => 'u.created_at ASC',
            'name-asc' => 'u.name ASC',
            'name-desc' => 'u.name DESC',
            default => throw new InvalidArgumentException('Invalid unit sort.')
        };

        $query = self::SELECT . "
            WHERE $where
            ORDER BY $orderBy, u.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value, PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', $data['limit'], PDO::PARAM_INT);
        $stmt->bindValue(':offset', $data['offset'], PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @param array<string, mixed> $data */
    public function deleteItem(array $data): void {
        $query = 'UPDATE units SET active = 0, editor_id = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
            AND active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['editor_id'],
            $data['id']
        ]);
    }
}
