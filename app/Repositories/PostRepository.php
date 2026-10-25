<?php
declare(strict_types=1);

namespace Repo;

use InvalidArgumentException;
use PDO;

final class PostRepository {
    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    /** @param array<string, mixed> $data  */
    public function insertItem(array $data): string {
        $query = 'INSERT INTO posts (title, excerpt, content, featured_image, category_id,
            status, author_id, editor_id, published_at, expires_at)
            VALUES (?,?,?,?,?,?,?,?,?,?)';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['title'],
            $data['excerpt'],
            $data['content'],
            $data['featured_image'],
            $data['category_id'],
            $data['status'],
            $data['author_id'],
            $data['editor_id'],
            $data['published_at'],
            $data['expires_at']
        ]);

        return $this->conn->lastInsertId();
    }

    /** @param array<string, mixed> $data  */
    public function updateItem($data): void {
        $query = 'UPDATE posts SET title = ?, excerpt = ?, content = ?, featured_image = ?,
            category_id = ?, status = ?, editor_id = ?, published_at = ?, expires_at = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
            AND active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['title'],
            $data['excerpt'],
            $data['content'],
            $data['featured_image'],
            $data['category_id'],
            $data['status'],
            $data['editor_id'],
            $data['published_at'],
            $data['expires_at'],
            $data['id']
        ]);
    }

    public function fetchItemById(int $id): array|false {
        $query = "SELECT *,
            CASE
                WHEN status = 'published'
                    AND expires_at <= CURRENT_TIMESTAMP
                THEN 'expired'

                WHEN status = 'published'
                    AND published_at > CURRENT_TIMESTAMP
                THEN 'pending'

                ELSE status
            END AS display_status
            FROM posts
            WHERE id = ?
            AND active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    /** @param array<string, mixed> $data */
    /** @return list<array<string, mixed>> */
    public function filter(array $data): array {
        $conditions = [
            'active = 1',
            'archived_at IS NULL'
        ];

        $params = [];

        if ($data['search'] !== '') {
            $conditions[] = 'title LIKE :search';
            $params['search'] = "%{$data['search']}%";
        }

        $filterCondition = match ($data['filter']) {
            'draft' => "status = 'draft'",
            'published' => "
                status = 'published'
                AND published_at <= CURRENT_TIMESTAMP
                AND expires_at > CURRENT_TIMESTAMP
            ",
            'pending' => "
                status = 'published'
                AND published_at > CURRENT_TIMESTAMP,
                AND expires_at > CURRENT_TIMESTAMP
            ",
            'expired' => "
                status = 'published'
                AND expires_at <= CURRENT_TIMESTAMP
            ",
            'all' => '1 = 1',
            default => throw new InvalidArgumentException('Invalid post filter.')
        };

        $conditions[] = $filterCondition;

        $orderBy = match ($data['sort']) {
            'created-desc' => 'created_at DESC',
            'created-asc' => 'created_at ASC',
            'title-desc' => 'title DESC',
            'title-asc' => 'title ASC',
            'publish-desc' => 'published_at DESC',
            'publish-asc' => 'published_at ASC',
            'expiration-desc' => 'expires_at DESC',
            'expiration-asc' => 'expires_at ASC',

            default => throw new InvalidArgumentException('Invalid post sort.')
        };

        $query = "SELECT *,
            CASE
                WHEN status = 'published'
                    AND expires_at <= CURRENT_TIMESTAMP
                THEN 'expired'
                WHEN status = 'published'
                    AND published_at > CURRENT_TIMESTAMP
                THEN 'pending'
                ELSE status
            END AS display_status
            FROM posts
            WHERE " . implode(' AND ', $conditions) . '
            ORDER BY ' . $orderBy;

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @param array<string, mixed> $data  */
    public function archiveItem(array $data): void {
        $query = 'UPDATE posts SET archived_at = CURRENT_TIMESTAMP, editor_id = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
            AND active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['editor_id'],
            $data['id']
        ]);
    }

    /** @param array<string, mixed> $data  */
    public function unarchiveItem(array $data): void {
        $query = 'UPDATE posts SET archived_at = NULL, editor_id = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
            AND active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['editor_id'],
            $data['id']
        ]);
    }

    /** @param array<string, mixed> $data  */
    public function deleteItem(array $data): void {
        $query = 'UPDATE posts SET active = 0, editor_id = ?,
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
