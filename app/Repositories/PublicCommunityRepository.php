<?php
declare(strict_types=1);

namespace Repo;

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../app/Support/helper.php';

use PDO;

final class PublicCommunityRepository {

    private PDO $conn;

    private const COLUMNS = "p.id, c.name, p.title, p.excerpt, p.content,
                             p.featured_image, p.status, p.published_at,
                             p.created_at, p.active";

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    
    public function getLatestCommunityService(): ?array
    {
        $sql = "SELECT " . self::COLUMNS . "
                FROM posts p
                INNER JOIN categories c ON c.id = p.category_id
                WHERE p.active = 1
                AND p.status = 'published'
                AND p.category_id = 3
                AND (p.expires_at IS NULL OR p.expires_at > NOW())
                AND p.published_at <= NOW()
                ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
                LIMIT 1";

        $row = $this->conn->query($sql)->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    
    public function getOtherCommunityServices(int $excludeId): array
    {
        $sql = "SELECT " . self::COLUMNS . "
                FROM posts p
                INNER JOIN categories c ON c.id = p.category_id
                WHERE p.active = 1
                AND p.status = 'published'
                AND p.category_id = 3
                AND p.id <> :exclude_id
                AND (p.expires_at IS NULL OR p.expires_at > NOW())
                AND p.published_at <= NOW()
                ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':exclude_id' => $excludeId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>