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

    /** @param array<string, mixed> $data */
    public function insertItem(array $data): string {
        $query = 'INSERT INTO officials (name, position, biography, featured_image, order_number, status, author_id)
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

        return $this->conn->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function updateItem(array $data): void {
        $query = 'UPDATE officials SET name = ?, position = ?, biography = ?, featured_image = ?,
            order_number = ?, status = ?, editor_id = ?, updated_at = CURRENT_TIMESTAMP
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

    private function selectColumns(): string {
        return 'o.id, o.name, o.position, o.biography, o.featured_image, o.order_number, o.status,
                o.author_id, o.editor_id, o.created_at, o.updated_at, o.active,
                author.username AS created_by,
                editor.username AS updated_by';
    }

    private function joins(): string {
        return 'LEFT JOIN users author ON author.id = o.author_id
            LEFT JOIN users editor ON editor.id = o.editor_id';
    }

    public function fetchItemById(int $id): array|false {
        $columns = $this->selectColumns();
        $joins = $this->joins();

        $query = "SELECT $columns
            FROM officials o
            $joins
            WHERE o.id = ?
            AND o.active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    /** @param array<string, mixed> $data */
    /** @return array{0: string, 1: array<string, string>} */
    private function buildWhere(array $data): array {
        $conditions = ['o.active = 1'];
        $params = [];

        if ($data['search'] !== '') {
            $conditions[] = '(o.name LIKE :search_name
                OR o.position LIKE :search_position
                OR o.biography LIKE :search_biography)';

            // Escape LIKE wildcards so they are searched literally
            $escaped = addcslashes($data['search'], '%_\\');
            $params['search_name'] = "%{$escaped}%";
            $params['search_position'] = "%{$escaped}%";
            $params['search_biography'] = "%{$escaped}%";
        }

        $filterCondition = match ($data['filter']) {
            'active' => "o.status = 'active'",
            'inactive' => "o.status = 'inactive'",
            'all' => '1 = 1',
            default => throw new InvalidArgumentException('Invalid official filter.')
        };

        $conditions[] = $filterCondition;
        $where = implode(' AND ', $conditions);

        return [$where, $params];
    }

    /** @param array<string, mixed> $data */
    public function countFiltered(array $data): int {
        [$where, $params] = $this->buildWhere($data);

        $query = "SELECT COUNT(*)
            FROM officials o
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
            'ascending' => 'o.order_number ASC, o.id ASC',
            'descending' => 'o.order_number DESC, o.id DESC',
            'name' => 'o.name ASC, o.id ASC',
            default => throw new InvalidArgumentException('Invalid official sort.')
        };

        $columns = $this->selectColumns();
        $joins = $this->joins();

        $query = "SELECT $columns
            FROM officials o
            $joins
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
        $query = 'UPDATE officials SET active = 0, editor_id = ?,
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
