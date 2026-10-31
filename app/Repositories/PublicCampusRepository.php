<?php
declare(strict_types=1);

namespace Repo;

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../app/Support/helper.php';

use PDO;

final class PublicCampusRepository {

    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function getCampus(): array
    {
        $sql = "SELECT name, address, email, contacts, google_maps_url, featured_image, status, active
                FROM campuses
                WHERE status = 'active' AND active = 1
                ORDER BY id ASC";

        $stmt = $this->conn->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countActiveCampuses(): int
    {
        $sql = "SELECT COUNT(*) FROM campuses WHERE status = 'active' AND active = 1";

        return (int) $this->conn->query($sql)->fetchColumn();
    }
}
?>