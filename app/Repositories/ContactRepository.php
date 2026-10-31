<?php
declare(strict_types=1);

namespace Repo;

use InvalidArgumentException;
use PDO;

final class ContactRepository {
    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    /** @param array<string, mixed> $data */
    public function insertItem(array $data): string {
        $query = 'INSERT INTO contacts (name, email, phone_number, status, author_id)
            VALUES (?,?,?,?,?)';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone_number'],
            $data['status'],
            $data['author_id']
        ]);

        return $this->conn->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function updateItem(array $data): void {
        $query = 'UPDATE contacts SET name = ?, email = ?, phone_number = ?,
            status = ?, editor_id = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
            AND active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone_number'],
            $data['status'],
            $data['editor_id'],
            $data['id']
        ]);
    }

    public function fetchItemById(int $id): array|false {
        $query = 'SELECT c.id, c.name, c.email, c.phone_number, c.status,
                c.author_id, c.editor_id, c.created_at, c.updated_at, c.active,
                author.username AS created_by,
                editor.username AS updated_by
            FROM contacts c
            LEFT JOIN users author ON author.id = c.author_id
            LEFT JOIN users editor ON editor.id = c.editor_id
            WHERE c.id = ?
            AND c.active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    /** @param array<string, mixed> $data */
    /** @return array{0: string, 1: array<string, string>} */
    private function buildWhere(array $data): array {
        $conditions = ['c.active = 1'];
        $params = [];

        if ($data['search'] !== '') {
            $conditions[] = '(c.name LIKE :search_name
                OR c.email LIKE :search_email
                OR c.phone_number LIKE :search_phone)';

            // Escape LIKE wildcards so they are searched literally
            $escaped = addcslashes($data['search'], '%_\\');
            $params['search_name'] = "%{$escaped}%";
            $params['search_email'] = "%{$escaped}%";
            $params['search_phone'] = "%{$escaped}%";
        }

        $filterCondition = match ($data['filter']) {
            'active' => "c.status = 'active'",
            'inactive' => "c.status = 'inactive'",
            'all' => '1 = 1',
            default => throw new InvalidArgumentException('Invalid contact filter.')
        };

        $conditions[] = $filterCondition;
        $where = implode(' AND ', $conditions);

        return [$where, $params];
    }

    /** @param array<string, mixed> $data */
    public function countFiltered(array $data): int {
        [$where, $params] = $this->buildWhere($data);

        $query = "SELECT COUNT(*)
            FROM contacts c
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
            'created-desc' => 'c.created_at DESC, c.id DESC',
            'created-asc' => 'c.created_at ASC, c.id ASC',
            'name-desc' => 'c.name DESC, c.id DESC',
            'name-asc' => 'c.name ASC, c.id ASC',
            default => throw new InvalidArgumentException('Invalid contact sort.')
        };

        $query = "SELECT c.id, c.name, c.email, c.phone_number, c.status,
                c.author_id, c.editor_id, c.created_at, c.updated_at, c.active,
                author.username AS created_by,
                editor.username AS updated_by
            FROM contacts c
            LEFT JOIN users author ON author.id = c.author_id
            LEFT JOIN users editor ON editor.id = c.editor_id
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
        $query = 'UPDATE contacts SET active = 0, editor_id = ?,
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
