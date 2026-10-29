<?php
declare(strict_types=1);

namespace Repo;

use InvalidArgumentException;
use PDO;

final class UnitPostRepository {
    // unit_posts.expires_at is NOT NULL, so "no expiry" is stored as this far-future date
    public const NO_EXPIRY = '9999-12-31';

    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    /** @param array<string, mixed> $data */
    public function insertItem(array $data): string {
        $query = 'INSERT INTO unit_posts (title, excerpt, content, featured_image, unit_id,
            category_id, status, author_id, published_at, expires_at)
            VALUES (?,?,?,?,?,?,?,?,?,?)';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['title'],
            $data['excerpt'],
            $data['content'],
            $data['featured_image'],
            $data['unit_id'],
            $data['category_id'],
            $data['status'],
            $data['author_id'],
            $data['published_at'],
            $data['expires_at']
        ]);

        return $this->conn->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function updateItem(array $data): void {
        $query = 'UPDATE unit_posts SET title = ?, excerpt = ?, content = ?, featured_image = ?,
            unit_id = ?, category_id = ?, status = ?, editor_id = ?, published_at = ?,
            expires_at = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
            AND active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['title'],
            $data['excerpt'],
            $data['content'],
            $data['featured_image'],
            $data['unit_id'],
            $data['category_id'],
            $data['status'],
            $data['editor_id'],
            $data['published_at'],
            $data['expires_at'],
            $data['id']
        ]);
    }

    // Shared by fetchItemById() and filter() so a list row and a single item have the same shape
    private function selectColumns(bool $withContent): string {
        $content = $withContent ? 'up.content,' : '';

        return "up.id, up.title, up.excerpt, $content up.featured_image, up.unit_id,
                up.category_id, up.views, up.status, up.author_id, up.editor_id,
                up.published_at, up.expires_at, up.archived_at, up.created_at,
                up.updated_at, up.active,
                CASE
                    WHEN up.status = 'published'
                        AND up.expires_at < CURRENT_DATE
                    THEN 'expired'
                    WHEN up.status = 'published'
                        AND up.published_at > CURRENT_DATE
                    THEN 'pending'
                    ELSE up.status
                END AS display_status,
                (up.expires_at < '9999-01-01') AS has_expiry,
                u.name AS unit_name,
                cat.name AS category_name,
                author.username AS created_by,
                editor.username AS updated_by";
    }

    private function joins(): string {
        return 'INNER JOIN units u ON u.id = up.unit_id AND u.active = 1
            LEFT JOIN categories cat ON cat.id = up.category_id
            LEFT JOIN users author ON author.id = up.author_id
            LEFT JOIN users editor ON editor.id = up.editor_id';
    }

    public function fetchItemById(int $id): array|false {
        $columns = $this->selectColumns(true);
        $joins = $this->joins();

        $query = "SELECT $columns
            FROM unit_posts up
            $joins
            WHERE up.id = ?
            AND up.active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    /** @param array<string, mixed> $data */
    /** @return array{0: string, 1: array<string, string>} */
    private function buildWhere(array $data): array {
        $conditions = [
            'up.active = 1',
            'up.archived_at IS NULL'
        ];

        $params = [];

        if ($data['search'] !== '') {
            $conditions[] = '(up.title LIKE :search_title
                OR up.excerpt LIKE :search_excerpt
                OR u.name LIKE :search_unit)';

            // Escape LIKE wildcards so they are searched literally
            $escaped = addcslashes($data['search'], '%_\\');
            $params['search_title'] = "%{$escaped}%";
            $params['search_excerpt'] = "%{$escaped}%";
            $params['search_unit'] = "%{$escaped}%";
        }

        if ($data['unit_id'] !== null) {
            $conditions[] = 'up.unit_id = :unit_id';
            $params['unit_id'] = (string) $data['unit_id'];
        }

        $filterCondition = match ($data['filter']) {
            'draft' => "up.status = 'draft'",
            'published' => "
                up.status = 'published'
                AND up.published_at <= CURRENT_DATE
                AND up.expires_at >= CURRENT_DATE
            ",
            'pending' => "
                up.status = 'published'
                AND up.published_at > CURRENT_DATE
            ",
            'expired' => "
                up.status = 'published'
                AND up.expires_at < CURRENT_DATE
            ",
            'all' => '1 = 1',
            default => throw new InvalidArgumentException('Invalid unit post filter.')
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
            FROM unit_posts up
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

        // Posts without an expiry date always go last, as on the unit posts page
        $orderBy = match ($data['sort']) {
            'newest' => 'up.created_at DESC',
            'oldest' => 'up.created_at ASC',
            'title-asc' => 'up.title ASC',
            'title-desc' => 'up.title DESC',
            'expiry-asc' => 'up.expires_at ASC',
            'expiry-desc' => "(up.expires_at >= '9999-01-01') ASC, up.expires_at DESC",
            default => throw new InvalidArgumentException('Invalid unit post sort.')
        };

        // The list leaves out the long content column; item() returns it
        $columns = $this->selectColumns(false);
        $joins = $this->joins();

        $query = "SELECT $columns
            FROM unit_posts up
            $joins
            WHERE $where
            ORDER BY $orderBy, up.id DESC
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
        $query = 'UPDATE unit_posts SET active = 0, editor_id = ?,
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
