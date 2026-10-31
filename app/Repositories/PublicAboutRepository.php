<?php
declare(strict_types=1);

namespace Repo;

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../app/Support/helper.php';

use PDO;

final class PublicAboutRepository {

    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function getAbout(): array
    {
        $sql = "SELECT category_id, name, description, featured_image, status, active
                FROM about
                WHERE status = 'active' AND active = 1 AND category_id = 9";

        $stmt = $this->conn->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getHistory(): array
    {
        $sql = "SELECT category_id, name, description, featured_image, status, active
                FROM about
                WHERE status = 'active' AND active = 1 AND category_id = 10
                ORDER BY name ASC";

        $stmt = $this->conn->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNewUCC(): array
    {
        $sql = "SELECT category_id, name, description, featured_image, status, active
                FROM about
                WHERE status = 'active' AND active = 1 AND category_id = 11";

        $stmt = $this->conn->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOpportunities(): array
    {
        $sql = "SELECT category_id, name, description, featured_image, status, active
                FROM about
                WHERE status = 'active' AND active = 1 AND category_id = 12";

        $stmt = $this->conn->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>