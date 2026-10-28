<?php
declare(strict_types=1);

namespace Repo;

use InvalidArgumentException;
use PDO;

final class RegentRepository {
    private const SELECT = 'SELECT r.*, ua.username AS created_by, ue.username AS updated_by
        FROM regents r
        LEFT JOIN users ua ON ua.id = r.author_id
        LEFT JOIN users ue ON ue.id = r.editor_id';

    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    /** @param array<string, mixed> $data */
    public function insertItem(array $data): int {
        $query = 'INSERT INTO regents (name, position, biography, featured_image,
            order_number, status, author_id)
            VALUES (?,?,?,?,?,?,?)';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['name'],
            $data['position'],
            $data['biography'],
            $data['featured_image'],
            $data['order_number'],
            $data['status'],
            $data['author_id']
        ]);

        return (int) $this->conn->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function updateItem(array $data): void {
        $query = 'UPDATE regents SET name = ?, position = ?, biography = ?,
            featured_image = ?, order_number = ?, status = ?, editor_id = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
            AND active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['name'],
            $data['position'],
            $data['biography'],
            $data['featured_image'],
            $data['order_number'],
            $data['status'],
            $data['editor_id'],
            $data['id']
        ]);
    }

    public function fetchItemById(int $id): array|false {
        $query = self::SELECT . '
            WHERE r.id = ?
            AND r.active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    // Another regent already using the given order number (the public site shows order 1 as the Chairman)
    public function fetchByOrderNumber(int $orderNumber, int $excludeId): array|false {
        $query = 'SELECT id, name
            FROM regents
            WHERE order_number = ?
            AND id <> ?
            AND active = 1
            LIMIT 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$orderNumber, $excludeId]);

        return $stmt->fetch();
    }

    // Suggested display order for a new regent
    public function fetchNextOrderNumber(): int {
        $query = 'SELECT COALESCE(MAX(order_number), 0) + 1
            FROM regents
            WHERE active = 1';
        $stmt = $this->conn->query($query);

        return (int) $stmt->fetchColumn();
    }

    /** @param array<string, mixed> $data */
    /** @return array{0: string, 1: array<string, mixed>} */
    private function buildWhere(array $data): array {
        $conditions = ['r.active = 1'];
        $params = [];

        if ($data['search'] !== '') {
            $conditions[] = '(r.name LIKE :search_name
                OR r.position LIKE :search_position
                OR r.biography LIKE :search_biography)';
            $params['search_name'] = "%{$data['search']}%";
            $params['search_position'] = "%{$data['search']}%";
            $params['search_biography'] = "%{$data['search']}%";
        }

        if ($data['filter'] !== 'all') {
            $conditions[] = 'r.status = :status';
            $params['status'] = $data['filter'];
        }

        $where = implode(' AND ', $conditions);

        return [$where, $params];
    }

    /** @param array<string, mixed> $data */
    public function countFiltered(array $data): int {
        [$where, $params] = $this->buildWhere($data);

        $query = "SELECT COUNT(*)
            FROM regents r
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
            'order-asc' => 'r.order_number ASC, r.id ASC',
            'order-desc' => 'r.order_number DESC, r.id DESC',
            'name-asc' => 'r.name ASC, r.id ASC',
            'name-desc' => 'r.name DESC, r.id DESC',
            'create-desc' => 'r.created_at DESC, r.id DESC',
            'create-asc' => 'r.created_at ASC, r.id ASC',
            default => throw new InvalidArgumentException('Invalid regent sort.')
        };

        $query = self::SELECT . "
            WHERE $where
            ORDER BY $orderBy
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
        $query = 'UPDATE regents SET active = 0, editor_id = ?,
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
