<?php
declare(strict_types=1);

namespace Repo;

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../app/Support/helper.php';

use PDO;

final class PublicProgramsRepository {

    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    //This is for homepage
    public function getPrograms(): array
    {
        $sql = "SELECT id, name
                FROM programs
                WHERE status = 'active' AND active = 1
                ORDER BY name ASC";

        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }


    //This is for colleges section
    public function getProgramsByCollegeName(string $collegeName): array
    {
        $sql = "SELECT p.id, p.name, p.vision, p.mission
                FROM programs p
                INNER JOIN units u ON p.unit_id = u.id
                WHERE u.name = :college
                AND u.status = 'active' AND u.active = 1
                AND p.status = 'active' AND p.active = 1
                ORDER BY p.name ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':college' => $collegeName]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function programsNews(int $programId): array
    {
        $sql = "SELECT
                    pp.id,
                    pp.title,
                    pp.excerpt,
                    pp.content,
                    pp.featured_image,
                    pp.published_at,
                    c.name  AS category_name,
                    pr.name AS program_name
                FROM program_posts pp
                INNER JOIN programs pr
                    ON pp.program_id = pr.id
                INNER JOIN categories c
                    ON pp.category_id = c.id
                WHERE pr.id = :id
                    AND pp.status = 'published'
                    AND pp.category_id = 8
                    AND pp.active = 1
                    AND (pp.expires_at IS NULL OR pp.expires_at > NOW())
                    AND pr.status = 'active'
                    AND pr.active = 1
                ORDER BY pp.published_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $programId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findProgram(int $programId): ?array
    {
        $sql = "SELECT p.id, p.name
                FROM programs p
                INNER JOIN units u ON p.unit_id = u.id
                WHERE p.id = :id
                AND p.status = 'active' AND p.active = 1
                AND u.status = 'active' AND u.active = 1
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $programId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
?>