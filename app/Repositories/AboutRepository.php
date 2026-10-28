<?php
declare(strict_types=1);

namespace Repo;

use InvalidArgumentException;
use PDO;

final class AboutRepository {
    private const SELECT = 'SELECT a.*, c.name AS category_name,
            ua.username AS created_by, ue.username AS updated_by
        FROM about a
        LEFT JOIN categories c ON c.id = a.category_id
        LEFT JOIN users ua ON ua.id = a.author_id
        LEFT JOIN users ue ON ue.id = a.editor_id';

    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    /** @param array<string, mixed> $data */
    public function insertItem(array $data): int {
        $query = 'INSERT INTO about (name, description, featured_image, category_id,
            status, author_id)
            VALUES (?,?,?,?,?,?)';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['featured_image'],
            $data['category_id'],
            $data['status'],
            $data['author_id']
        ]);

        return (int) $this->conn->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function updateItem(array $data): void {
        $query = 'UPDATE about SET name = ?, description = ?, featured_image = ?,
            category_id = ?, status = ?, editor_id = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
            AND active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['featured_image'],
            $data['category_id'],
            $data['status'],
            $data['editor_id'],
            $data['id']
        ]);
    }

    public function fetchItemById(int $id): array|false {
        $query = self::SELECT . '
            WHERE a.id = ?
            AND a.active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    /** @param array<string, mixed> $data */
    /** @return array{0: string, 1: array<string, mixed>} */
    private function buildWhere(array $data): array {
        $conditions = ['a.active = 1'];
        $params = [];

        if ($data['search'] !== '') {
            $conditions[] = '(a.name LIKE :search_name OR a.description LIKE :search_description)';
            $params['search_name'] = "%{$data['search']}%";
            $params['search_description'] = "%{$data['search']}%";
        }

        if ($data['filter'] !== 'all') {
            $conditions[] = 'a.status = :status';
            $params['status'] = $data['filter'];
        }

        if ($data['category'] !== null) {
            $conditions[] = 'a.category_id = :category_id';
            $params['category_id'] = $data['category'];
        }

        $where = implode(' AND ', $conditions);

        return [$where, $params];
    }

    /** @param array<string, mixed> $data */
    public function countFiltered(array $data): int {
        [$where, $params] = $this->buildWhere($data);

        $query = "SELECT COUNT(*)
            FROM about a
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
            'create-desc' => 'a.created_at DESC',
            'create-asc' => 'a.created_at ASC',
            'name-asc' => 'a.name ASC',
            'name-desc' => 'a.name DESC',
            default => throw new InvalidArgumentException('Invalid about sort.')
        };

        $query = self::SELECT . "
            WHERE $where
            ORDER BY $orderBy, a.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', $data['limit'], PDO::PARAM_INT);
        $stmt->bindValue(':offset', $data['offset'], PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @param array<string, mixed> $data */
    public function deleteItem(array $data): void {
        $query = 'UPDATE about SET active = 0, editor_id = ?,
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
