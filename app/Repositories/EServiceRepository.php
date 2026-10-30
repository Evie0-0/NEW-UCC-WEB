<?php
declare(strict_types=1);

namespace Repo;

use PDO;

final class EServiceRepository
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function insertItem(array $data): string
    {
        $query = "
            INSERT INTO eservices (
                name,
                description,
                url,
                featured_image,
                status,
                author_id,
                active
            )
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['url'],
            $data['featured_image'] ?? null,
            $data['status'],
            $data['author_id']
        ]);

        return $this->conn->lastInsertId();
    }

    // Update 
    public function updateItem(array $data): bool
    {
        $query = "
            UPDATE eservices
            SET
                name = ?,
                description = ?,
                url = ?,
                featured_image = ?,
                status = ?,
                editor_id = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
                AND active = 1
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['url'],
            $data['featured_image'] ?? null,
            $data['status'],
            $data['editor_id'],
            $data['id']
        ]);

        return $stmt->rowCount() > 0;
    }

    // Fetch an E-Service by ID (excluding soft-deleted records)
    public function fetchItemById(int $id): array|false
    {
        $query = "
            SELECT
                e.*,
                NULL AS author_username,
                NULL AS editor_username
            FROM eservices AS e
            WHERE e.id = ?
                AND e.active = 1
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Fetch all E-Services that have not been soft-deleted
    public function fetchAll(): array
    {
        $query = "
            SELECT
                e.*,
                NULL AS author_username,
                NULL AS editor_username
            FROM eservices AS e
            WHERE e.active = 1
            ORDER BY e.created_at DESC, e.id DESC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Soft-delete an E-Service
    public function deleteItem(int $id, int $editorId): bool
    {
        $query = "
            UPDATE eservices
            SET
                status = 'inactive',
                active = 0,
                editor_id = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
                AND active = 1
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $editorId,
            $id
        ]);

        return $stmt->rowCount() > 0;
    }
}