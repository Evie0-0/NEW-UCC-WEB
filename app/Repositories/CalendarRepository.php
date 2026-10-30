<?php
declare(strict_types=1);

namespace Repo;

use InvalidArgumentException;
use PDO;

final class CalendarRepository {
    // A published event whose publish date is still ahead is shown as pending
    private const DISPLAY_STATUS = "CASE
            WHEN c.status = 'published'
                AND c.published_at > CURRENT_DATE
            THEN 'pending'
            ELSE c.status
        END";

    private const SELECT = 'SELECT c.*, ' . self::DISPLAY_STATUS . ' AS display_status,
            ua.username AS created_by, ue.username AS updated_by
        FROM calendar c
        LEFT JOIN users ua ON ua.id = c.author_id
        LEFT JOIN users ue ON ue.id = c.editor_id';

    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    /** @param array<string, mixed> $data */
    public function insertItem(array $data): int {
        $query = 'INSERT INTO calendar (title, description, featured_image, status,
            author_id, published_at, starts_at, ends_at)
            VALUES (?,?,?,?,?,?,?,?)';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['title'],
            $data['description'],
            $data['featured_image'],
            $data['status'],
            $data['author_id'],
            $data['published_at'],
            $data['starts_at'],
            $data['ends_at']
        ]);

        return (int) $this->conn->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function updateItem(array $data): void {
        $query = 'UPDATE calendar SET title = ?, description = ?, featured_image = ?,
            status = ?, editor_id = ?, published_at = ?, starts_at = ?, ends_at = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
            AND active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['title'],
            $data['description'],
            $data['featured_image'],
            $data['status'],
            $data['editor_id'],
            $data['published_at'],
            $data['starts_at'],
            $data['ends_at'],
            $data['id']
        ]);
    }

    public function fetchItemById(int $id): array|false {
        $query = self::SELECT . '
            WHERE c.id = ?
            AND c.active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    /** @param array<string, mixed> $data */
    /** @return array{0: string, 1: array<string, mixed>} */
    private function buildWhere(array $data): array {
        $conditions = ['c.active = 1'];
        $params = [];

        if ($data['search'] !== '') {
            $conditions[] = 'c.title LIKE :search_title';
            $params['search_title'] = "%{$data['search']}%";
        }

        $statusCondition = match ($data['filter']) {
            'draft' => "c.status = 'draft'",
            'published' => "c.status = 'published' AND c.published_at <= CURRENT_DATE",
            'pending' => "c.status = 'published' AND c.published_at > CURRENT_DATE",
            default => null
        };

        if ($statusCondition !== null) {
            $conditions[] = $statusCondition;
        }

        if ($data['date'] !== null) {
            $conditions[] = 'DATE(c.published_at) = :publish_date';
            $params['publish_date'] = $data['date'];
        }

        $where = implode(' AND ', $conditions);

        return [$where, $params];
    }

    /** @param array<string, mixed> $data */
    public function countFiltered(array $data): int {
        [$where, $params] = $this->buildWhere($data);

        $query = "SELECT COUNT(*)
            FROM calendar c
            WHERE $where
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /** @param array<string, mixed> $data */
    /** @return array<int, array<string, mixed>> */
    public function filter(array $data): array {
        [$where, $params] = $this->buildWhere($data);

        $orderBy = match ($data['sort']) {
            'create-desc' => 'c.created_at DESC',
            'create-asc' => 'c.created_at ASC',
            'publish-desc' => 'c.published_at DESC',
            'publish-asc' => 'c.published_at ASC',
            'start-asc' => 'c.starts_at ASC',
            'start-desc' => 'c.starts_at DESC',
            'title-asc' => 'c.title ASC',
            'title-desc' => 'c.title DESC',
            default => throw new InvalidArgumentException('Invalid calendar sort.')
        };

        $query = self::SELECT . "
            WHERE $where
            ORDER BY $orderBy, c.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value, PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', $data['limit'], PDO::PARAM_INT);
        $stmt->bindValue(':offset', $data['offset'], PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @param array<string, mixed> $data */
    public function deleteItem(array $data): void {
        $query = 'UPDATE calendar SET active = 0, editor_id = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
            AND active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['editor_id'],
            $data['id']
        ]);
    }
}
