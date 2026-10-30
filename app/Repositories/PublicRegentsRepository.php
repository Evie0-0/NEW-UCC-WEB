<?php
declare(strict_types=1);

namespace Repo;

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../app/Support/helper.php';

use PDO;

final class PublicRegentsRepository {

    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function getChairman(): ?array
    {
        $sql = "SELECT name, position, biography, featured_image, order_number,
                       status, active
                FROM regents
                WHERE active = 1
                  AND status = 'active'
                  AND order_number = 1
                  AND active = 1
                ORDER BY order_number ASC
                LIMIT 1";

        $stmt = $this->conn->query($sql);
        $chairman = $stmt->fetch(PDO::FETCH_ASSOC);

        return $chairman ?: null;
    }

    public function getMembersOfBOR(): array
    {
        $sql = "SELECT name, position, biography, featured_image, order_number,
                       status, active
                FROM regents
                WHERE active = 1
                  AND status = 'active'
                  AND order_number != 1
                ORDER BY order_number ASC";

        $stmt = $this->conn->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    ///For the homepage part
    public function getChairmanImage(): ?array
    {
    $sql = "SELECT featured_image
            FROM regents
            WHERE active = 1
              AND status = 'active'
              AND order_number = 1
            ORDER BY order_number ASC
            LIMIT 1";

    $stmt = $this->conn->query($sql);
    $chairman = $stmt->fetch(PDO::FETCH_ASSOC);

    return $chairman ?: null;
    }
}
?>