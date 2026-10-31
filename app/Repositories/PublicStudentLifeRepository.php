<?php
declare(strict_types=1);

namespace Repo;

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../app/Support/helper.php';

use PDO;

final class PublicStudentLifeRepository {

    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function getLatestPost(): array
    {
        $sql = "SELECT p.id, p.title, p.excerpt, p.content,
                    p.featured_image, p.status, p.published_at, p.active
                FROM posts p
                WHERE p.active = 1
                AND p.status = 'published'
                AND p.category_id = 2
                AND (p.expires_at IS NULL OR p.expires_at > NOW())
                AND p.published_at <= NOW()
                ORDER BY p.published_at DESC
                LIMIT 1";

        return $this->conn->query($sql)->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function getAllPost(): array
    {
        $sql = "SELECT p.id, p.title, p.excerpt, p.content,
                    p.featured_image, p.status, p.published_at, p.active
                FROM posts p
                WHERE p.active = 1
                AND p.status = 'published'
                AND p.category_id = 2
                AND (p.expires_at IS NULL OR p.expires_at > NOW())
                AND p.published_at <= NOW()
                ORDER BY p.published_at DESC";

        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>