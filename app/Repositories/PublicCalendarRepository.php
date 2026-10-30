<?php
declare(strict_types=1);

namespace Repo;

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../app/Support/helper.php';

use PDO;

final class PublicCalendarRepository {

    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function getOverview(): array
    {
        $sql = "SELECT id, title, starts_at, ends_at
                FROM calendar
                WHERE status = 'published'
                AND active = 1
                AND NOW() >= published_at
                ORDER BY
                    (COALESCE(ends_at, starts_at) < CURDATE()) ASC,
                    CASE WHEN COALESCE(ends_at, starts_at) >= CURDATE() THEN starts_at END ASC,
                    CASE WHEN COALESCE(ends_at, starts_at) < CURDATE() THEN starts_at END DESC";

        $stmt = $this->conn->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllCalendarInfo(): array
    {
        $sql = "SELECT id, title, description, featured_image, starts_at, ends_at
                FROM calendar
                WHERE status = 'published'
                AND active = 1
                AND NOW() >= published_at
                ORDER BY
                    (COALESCE(ends_at, starts_at) < CURDATE()) ASC,
                    CASE WHEN COALESCE(ends_at, starts_at) >= CURDATE() THEN starts_at END ASC,
                    CASE WHEN COALESCE(ends_at, starts_at) < CURDATE() THEN starts_at END DESC";

        $stmt1 = $this->conn->query($sql);

        return $stmt1->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>