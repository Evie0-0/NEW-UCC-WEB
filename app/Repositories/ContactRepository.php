<?php
declare(strict_types=1);

namespace Repo;

use PDO;

final class ContactRepository
{
    public function __construct(private PDO $conn)
    {
    }

    public function fetchAll(): array
    {
        $stmt = $this->conn->prepare("
            SELECT
                c.*,
                NULL AS author_username,
                NULL AS editor_username
            FROM contacts AS c
            WHERE c.active = 1
            ORDER BY c.created_at DESC, c.id DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchItemById(int $id): array|false
    {
        $stmt = $this->conn->prepare("
            SELECT *
            FROM contacts
            WHERE id = ? AND active = 1
            LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insertItem(array $data): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO contacts (
                name, email, phone_number, category,
                location, status, author_id, active
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone_number'],
            $data['category'],
            $data['location'],
            $data['status'],
            $data['author_id']
        ]);
        return (int) $this->conn->lastInsertId();
    }

    public function updateItem(array $data): bool
    {
        $stmt = $this->conn->prepare("
            UPDATE contacts
            SET
                name = ?,
                email = ?,
                phone_number = ?,
                category = ?,
                location = ?,
                status = ?,
                editor_id = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND active = 1
        ");
        return $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone_number'],
            $data['category'],
            $data['location'],
            $data['status'],
            $data['editor_id'],
            $data['id']
        ]);
    }

    public function deleteItem(int $id, int $editorId): bool
    {
        $stmt = $this->conn->prepare("
            UPDATE contacts
            SET
                status = 'inactive',
                active = 0,
                editor_id = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND active = 1
        ");
        $stmt->execute([$editorId, $id]);
        return $stmt->rowCount() > 0;
    }
}