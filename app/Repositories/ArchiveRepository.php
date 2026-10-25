<?php
declare(strict_types=1);

namespace Repo;

use InvalidArgumentException;
use PDO;

final class ArchiveRepository {
    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    /** @param array<string, mixed> $data */
    /** @return list<array<string, mixed>> */
    public function filter(array $data): array {
        $conditions = [
            'active = 1',
            'archived_at IS NOT NULL'
        ];

        $params = [];

        if ($data['search'] !== '') {
            $conditions[] = 'title LIKE :search';
            $params['search'] = "%{$data['search']}%";
        }

        if ($data['filter'] !== 'all') {
            $conditions[] = 'category_id = :category_id';
            $params['category_id'] = $data['filter'];
        }

        $orderBy = match ($data['sort']) {
            'archive-desc' => 'archived_at DESC',
            'archive-asc' => 'archived_at ASC',
            'title-desc' => 'title DESC',
            'title-asc' => 'title ASC',
            'publish-desc' => 'published_at DESC',
            'publish-asc' => 'published_at ASC',
            'expiry-desc' => 'expires_at DESC',
            'expiry-asc' => 'expires_at ASC',
            default => throw new InvalidArgumentException('Invalid post sort.')
        };

        $where = implode(' AND ', $conditions);
        $query = "SELECT *
            FROM (
                SELECT id, title, excerpt, content, featured_image, category_id, views, status,
                    author_id, editor_id, published_at, expires_at, archived_at, created_at, updated_at, active,
                    'posts' AS source_table
                FROM posts
                UNION ALL
                SELECT id, title, excerpt, content, featured_image, category_id, views, status,
                    author_id, editor_id, published_at, expires_at, archived_at, created_at, updated_at, active,
                    'unit_posts' AS source_table
                FROM unit_posts
                UNION ALL
                SELECT id, title, excerpt, content, featured_image, category_id, views, status,
                    author_id, editor_id, published_at, expires_at, archived_at, created_at, updated_at, active,
                    'program_posts' AS source_table
                FROM program_posts
            ) AS combined_posts
            WHERE $where
            ORDER BY $orderBy
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
