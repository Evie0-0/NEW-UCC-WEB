<?php
declare(strict_types=1);

namespace Repo;

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../app/Support/helper.php';

use PDO;

final class PublicEServicesRepository {

    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function getAllEServices(): array
    {
        $sql = "SELECT name, description, url, featured_image FROM `eservices` 
        WHERE status = 'active' AND active = 1;";

        $stmt = $this->conn->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //For homepage
    public function getQuickLinks(): array
    {
        $sql = "SELECT url, featured_image FROM `eservices` 
        WHERE status = 'active' AND active = 1;";

        $stmt = $this->conn->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>