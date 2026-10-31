<?php
declare(strict_types=1);

namespace Repo;

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../app/Support/helper.php';

use PDO;

final class PublicCollegesRepository {

    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    // For navbar: get all active colleges
    public function getColleges(): array
    {
        $sql = "SELECT id, name
                FROM units
                WHERE status = 'active' AND active = 1
                ORDER BY name ASC";

        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }


    public function collegesInfo(string $collegeName): array
    {
        $sql = "SELECT u.name         AS college_name,
                    u.logo,
                    u.dean,
                    u.dean_image,
                    ui.name           AS info_name,
                    ui.description
                FROM unit_info ui
                INNER JOIN units u ON ui.unit_id = u.id
                WHERE u.name = :name
                AND u.status = 'active'  AND u.active = 1
                AND ui.status = 'active' AND ui.active = 1
                ORDER BY ui.id ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':name' => $collegeName]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //Get all the college news
    public function collegesNews(string $collegeName): array
    {
        $sql = "SELECT
                    up.id,
                    up.title,
                    up.excerpt,
                    up.content,
                    up.featured_image,
                    up.published_at,
                    c.name AS category_name
                FROM unit_posts up
                INNER JOIN units u
                    ON up.unit_id = u.id
                INNER JOIN categories c
                    ON up.category_id = c.id
                WHERE u.name = :name
                    AND up.category_id = 7
                    AND up.status = 'published'
                    AND up.active = 1
                    AND (up.expires_at IS NULL OR up.expires_at > NOW())
                    AND up.published_at <= NOW()
                    AND u.status = 'active'
                    AND u.active = 1
                ORDER BY up.published_at DESC,
                         up.id DESC;";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':name' => $collegeName]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>