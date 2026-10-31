<?php
declare(strict_types=1);

namespace Repo;

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../app/Support/helper.php';

use PDO;

final class PublicAlumniRepository {

    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function getAlumniPosts(): array
    {
        $sql = "SELECT c.name, p.title, p.excerpt, p.content, p.featured_image, p.status, p.published_at, p.active 
                FROM posts p
                INNER JOIN categories c ON c.id = p.category_id
                WHERE p.active = 1
                AND p.status = 'published'
                AND p.category_id = 5
                AND (p.expires_at IS NULL OR p.expires_at > NOW())
                AND p.published_at <= NOW()
                ORDER BY p.published_at DESC";

        $stmt = $this->conn->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>