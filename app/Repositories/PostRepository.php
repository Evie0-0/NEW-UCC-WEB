<?php
declare(strict_types=1);

namespace Repo;

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
        $query = 'SELECT * FROM posts
            WHERE id = ?
            AND active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetch();
    }
}
