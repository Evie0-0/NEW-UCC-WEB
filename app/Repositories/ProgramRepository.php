<?php
declare(strict_types=1);

namespace Repo;

use InvalidArgumentException;
use PDO;

final class ProgramRepository {
    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    /** @param array<string, mixed> $data */
    public function insertItem(array $data): string {
        $query = 'INSERT INTO programs (name, vision, mission, unit_id, status, author_id)
            VALUES (?,?,?,?,?,?)';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['name'],
            $data['vision'],
            $data['mission'],
            $data['unit_id'],
            $data['status'],
            $data['author_id']
        ]);

        return $this->conn->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function updateItem(array $data): void {
        $query = 'UPDATE programs SET name = ?, vision = ?, mission = ?, unit_id = ?,
            status = ?, editor_id = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
            AND active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['name'],
            $data['vision'],
            $data['mission'],
            $data['unit_id'],
            $data['status'],
            $data['editor_id'],
            $data['id']
        ]);
    }

    private function selectColumns(): string {
        return 'p.id, p.name, p.vision, p.mission, p.unit_id, p.status,
                p.author_id, p.editor_id, p.created_at, p.updated_at, p.active,
                u.name AS unit_name,
                author.username AS created_by,
                editor.username AS updated_by';
    }

    // LEFT JOIN on units so a program never silently disappears from the admin list
    private function joins(): string {
        return 'LEFT JOIN units u ON u.id = p.unit_id
            LEFT JOIN users author ON author.id = p.author_id
            LEFT JOIN users editor ON editor.id = p.editor_id';
    }

    public function fetchItemById(int $id): array|false {
        $columns = $this->selectColumns();
        $joins = $this->joins();

        $query = "SELECT $columns
            FROM programs p
            $joins
            WHERE p.id = ?
            AND p.active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    /** @param array<string, mixed> $data */
    /** @return array{0: string, 1: array<string, string>} */
    private function buildWhere(array $data): array {
        $conditions = ['p.active = 1'];
        $params = [];

        if ($data['search'] !== '') {
            $conditions[] = '(p.name LIKE :search_name
                OR p.mission LIKE :search_mission
                OR p.vision LIKE :search_vision
                OR u.name LIKE :search_unit)';

            // Escape LIKE wildcards so they are searched literally
            $escaped = addcslashes($data['search'], '%_\\');
            $params['search_name'] = "%{$escaped}%";
            $params['search_mission'] = "%{$escaped}%";
            $params['search_vision'] = "%{$escaped}%";
            $params['search_unit'] = "%{$escaped}%";
        }

        if ($data['unit_id'] !== null) {
            $conditions[] = 'p.unit_id = :unit_id';
            $params['unit_id'] = (string) $data['unit_id'];
        }

        $filterCondition = match ($data['filter']) {
            'active' => "p.status = 'active'",
            'inactive' => "p.status = 'inactive'",
            'all' => '1 = 1',
            default => throw new InvalidArgumentException('Invalid program filter.')
        };

        $conditions[] = $filterCondition;
        $where = implode(' AND ', $conditions);

        return [$where, $params];
    }

    /** @param array<string, mixed> $data */
    public function countFiltered(array $data): int {
        [$where, $params] = $this->buildWhere($data);
        $joins = $this->joins();

        $query = "SELECT COUNT(*)
            FROM programs p
            $joins
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
            'name-asc' => 'p.name ASC, p.id ASC',
            'name-desc' => 'p.name DESC, p.id DESC',
            'newest' => 'p.created_at DESC, p.id DESC',
            'oldest' => 'p.created_at ASC, p.id ASC',
            default => throw new InvalidArgumentException('Invalid program sort.')
        };

        $columns = $this->selectColumns();
        $joins = $this->joins();

        $query = "SELECT $columns
            FROM programs p
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
        $query = 'UPDATE programs SET active = 0, editor_id = ?,
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
