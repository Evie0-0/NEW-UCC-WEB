<?php
declare(strict_types=1);

namespace Repo;

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../app/Support/helper.php';

use PDO;

final class PublicExecutiveOfficialsRepository {

    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function getUniversityPresident(): ?array
    {
        $sql = "SELECT name, position, biography, featured_image, order_number,
                       status, active
                FROM officials
                WHERE active = 1
                  AND position = 'University President'
                  AND id = 1
                  AND status = 'active'
                LIMIT 1";

        $stmt = $this->conn->query($sql);
        $official = $stmt->fetch(PDO::FETCH_ASSOC);

        return $official === false ? null : $official;
    }

    public function getAllExecOfficials(): array
    {
        $sql = "SELECT name, position, biography, featured_image, order_number,
                       status, active
                FROM officials
                WHERE active = 1
                  AND position != 'University President'
                ORDER BY order_number ASC";

        $stmt = $this->conn->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>