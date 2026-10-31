<?php
declare(strict_types=1);

namespace Repo;

use InvalidArgumentException;
use PDO;

final class EServiceRepository {
    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    /** @param array<string, mixed> $data */
    public function insertItem(array $data): string {
        $query = 'INSERT INTO eservices (name, description, url, featured_image, status, author_id)
            VALUES (?,?,?,?,?,?)';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['url'],
            $data['featured_image'],
            $data['status'],
            $data['author_id']
        ]);

        return $this->conn->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function updateItem(array $data): void {
        $query = 'UPDATE eservices SET name = ?, description = ?, url = ?, featured_image = ?,
            status = ?, editor_id = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
            AND active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['url'],
            $data['featured_image'],
            $data['status'],
            $data['editor_id'],
            $data['id']
        ]);
    }

    public function fetchItemById(int $id): array|false {
        $query = 'SELECT e.id, e.name, e.description, e.url, e.featured_image, e.status,
                e.author_id, e.editor_id, e.created_at, e.updated_at, e.active,
                author.username AS created_by,
                editor.username AS updated_by
            FROM eservices e
            LEFT JOIN users author ON author.id = e.author_id
            LEFT JOIN users editor ON editor.id = e.editor_id
            WHERE e.id = ?
            AND e.active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    /** @param array<string, mixed> $data */
    /** @return array{0: string, 1: array<string, string>} */
    private function buildWhere(array $data): array {
        $conditions = ['e.active = 1'];
        $params = [];

        if ($data['search'] !== '') {
            $conditions[] = '(e.name LIKE :search_name
                OR e.description LIKE :search_description
                OR e.url LIKE :search_url)';

            // Escape LIKE wildcards so they are searched literally
            $escaped = addcslashes($data['search'], '%_\\');
            $params['search_name'] = "%{$escaped}%";
            $params['search_description'] = "%{$escaped}%";
            $params['search_url'] = "%{$escaped}%";
        }

        $filterCondition = match ($data['filter']) {
            'active' => "e.status = 'active'",
            'inactive' => "e.status = 'inactive'",
            'all' => '1 = 1',
            default => throw new InvalidArgumentException('Invalid e-service filter.')
        };

        $conditions[] = $filterCondition;
        $where = implode(' AND ', $conditions);

        return [$where, $params];
    }

    /** @param array<string, mixed> $data */
    public function countFiltered(array $data): int {
        [$where, $params] = $this->buildWhere($data);

        $query = "SELECT COUNT(*)
            FROM eservices e
            WHERE $where
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /** @param array<string, mixed> $data */
    /** @return list<array<string, mixed>> */
    public function filter(array $data): array {
        [$where, $params] = $this->buildWhere($data);

        $orderBy = match ($data['sort']) {
            'created-desc' => 'e.created_at DESC',
            'created-asc' => 'e.created_at ASC',
            'name-desc' => 'e.name DESC',
            'name-asc' => 'e.name ASC',
            default => throw new InvalidArgumentException('Invalid e-service sort.')
        };

        $query = "SELECT e.id, e.name, e.description, e.url, e.featured_image, e.status,
                e.author_id, e.editor_id, e.created_at, e.updated_at, e.active,
                author.username AS created_by,
                editor.username AS updated_by
            FROM eservices e
            LEFT JOIN users author ON author.id = e.author_id
            LEFT JOIN users editor ON editor.id = e.editor_id
            WHERE $where
            ORDER BY $orderBy, e.id DESC
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
        $query = 'UPDATE eservices SET active = 0, editor_id = ?,
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
