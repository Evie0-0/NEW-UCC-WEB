<?php
declare(strict_types=1);

namespace Repo;

use InvalidArgumentException;
use PDO;

final class ProgramPostRepository {
    // program_posts.expires_at is NOT NULL, so "no expiry" is stored as this far-future date
    public const NO_EXPIRY = '9999-12-31';

    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    /** @param array<string, mixed> $data */
    public function insertItem(array $data): string {
        $query = 'INSERT INTO program_posts (title, excerpt, content, featured_image, program_id,
            category_id, status, author_id, published_at, expires_at)
            VALUES (?,?,?,?,?,?,?,?,?,?)';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['title'],
            $data['excerpt'],
            $data['content'],
            $data['featured_image'],
            $data['program_id'],
            $data['category_id'],
            $data['status'],
            $data['author_id'],
            $data['published_at'],
            $data['expires_at']
        ]);

        return $this->conn->lastInsertId();
    }

    // Leaves archived_at alone, so editing an archived post keeps it archived
    /** @param array<string, mixed> $data */
    public function updateItem(array $data): void {
        $query = 'UPDATE program_posts SET title = ?, excerpt = ?, content = ?, featured_image = ?,
            program_id = ?, category_id = ?, status = ?, editor_id = ?, published_at = ?,
            expires_at = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
            AND active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['title'],
            $data['excerpt'],
            $data['content'],
            $data['featured_image'],
            $data['program_id'],
            $data['category_id'],
            $data['status'],
            $data['editor_id'],
            $data['published_at'],
            $data['expires_at'],
            $data['id']
        ]);
    }

    /** @param array<string, mixed> $data */
    public function archiveItem(array $data): void {
        $query = 'UPDATE program_posts SET archived_at = CURRENT_TIMESTAMP, editor_id = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
            AND active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['editor_id'],
            $data['id']
        ]);
    }

    // The program posts page restores an archived post as a draft
    /** @param array<string, mixed> $data */
    public function unarchiveItem(array $data): void {
        $query = "UPDATE program_posts SET archived_at = NULL, status = 'draft', editor_id = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
            AND active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['editor_id'],
            $data['id']
        ]);
    }

    // Shared by fetchItemById() and filter() so a list row and a single item have the same shape
    private function selectColumns(bool $withContent): string {
        $content = $withContent ? 'pp.content,' : '';

        return "pp.id, pp.title, pp.excerpt, $content pp.featured_image, pp.program_id,
                pp.category_id, pp.views, pp.status, pp.author_id, pp.editor_id,
                pp.published_at, pp.expires_at, pp.archived_at, pp.created_at,
                pp.updated_at, pp.active,
                CASE
                    WHEN pp.archived_at IS NOT NULL
                    THEN 'archived'
                    WHEN pp.status = 'published'
                        AND pp.expires_at < CURRENT_DATE
                    THEN 'expired'
                    WHEN pp.status = 'published'
                        AND pp.published_at > CURRENT_DATE
                    THEN 'pending'
                    ELSE pp.status
                END AS display_status,
                (pp.expires_at < '9999-01-01') AS has_expiry,
                pr.name AS program_name,
                cat.name AS category_name,
                author.username AS created_by,
                editor.username AS updated_by";
    }

    // LEFT JOIN on programs so a post never silently disappears from the admin list
    private function joins(): string {
        return 'LEFT JOIN programs pr ON pr.id = pp.program_id
            LEFT JOIN categories cat ON cat.id = pp.category_id
            LEFT JOIN users author ON author.id = pp.author_id
            LEFT JOIN users editor ON editor.id = pp.editor_id';
    }

    public function fetchItemById(int $id): array|false {
        $columns = $this->selectColumns(true);
        $joins = $this->joins();

        $query = "SELECT $columns
            FROM program_posts pp
            $joins
            WHERE pp.id = ?
            AND pp.active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    /** @param array<string, mixed> $data */
    /** @return array{0: string, 1: array<string, string>} */
    private function buildWhere(array $data): array {
        $conditions = ['pp.active = 1'];
        $params = [];

        if ($data['search'] !== '') {
            $conditions[] = '(pp.title LIKE :search_title
                OR pp.excerpt LIKE :search_excerpt
                OR pr.name LIKE :search_program)';

            // Escape LIKE wildcards so they are searched literally
            $escaped = addcslashes($data['search'], '%_\\');
            $params['search_title'] = "%{$escaped}%";
            $params['search_excerpt'] = "%{$escaped}%";
            $params['search_program'] = "%{$escaped}%";
        }

        if ($data['program_id'] !== null) {
            $conditions[] = 'pp.program_id = :program_id';
            $params['program_id'] = (string) $data['program_id'];
        }

        // 'all' includes archived posts, as on the program posts page; every other filter excludes them
        $filterCondition = match ($data['filter']) {
            'draft' => "pp.archived_at IS NULL AND pp.status = 'draft'",
            'published' => "
                pp.archived_at IS NULL
                AND pp.status = 'published'
                AND pp.published_at <= CURRENT_DATE
                AND pp.expires_at >= CURRENT_DATE
            ",
            'pending' => "
                pp.archived_at IS NULL
                AND pp.status = 'published'
                AND pp.published_at > CURRENT_DATE
            ",
            'expired' => "
                pp.archived_at IS NULL
                AND pp.status = 'published'
                AND pp.expires_at < CURRENT_DATE
            ",
            'archived' => 'pp.archived_at IS NOT NULL',
            'all' => '1 = 1',
            default => throw new InvalidArgumentException('Invalid program post filter.')
        };

        $conditions[] = $filterCondition;
        $where = implode(' AND ', $conditions);

        return [$where, $params];
    }

    /** @param array<string, mixed> $data */
    public function countFiltered(array $data): int {
        [$where, $params] = $this->buildWhere($data);
        $joins = $this->joins();

        $query = "SELECT COUNT(*)
            FROM program_posts pp
            $joins
            WHERE $where
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /** @param array<string, mixed> $data */
    /** @return list<array<string, mixed>> */
    public function filter(array $data): array {
        [$where, $params] = $this->buildWhere($data);

        // Posts without an expiry date always go last, as on the program posts page
        $orderBy = match ($data['sort']) {
            'newest' => 'pp.created_at DESC',
            'oldest' => 'pp.created_at ASC',
            'title' => 'pp.title ASC',
            'expiry-asc' => 'pp.expires_at ASC',
            'expiry-desc' => "(pp.expires_at >= '9999-01-01') ASC, pp.expires_at DESC",
            default => throw new InvalidArgumentException('Invalid program post sort.')
        };

        // The list leaves out the long content column; item() returns it
        $columns = $this->selectColumns(false);
        $joins = $this->joins();

        $query = "SELECT $columns
            FROM program_posts pp
            $joins
            WHERE $where
            ORDER BY $orderBy, pp.id DESC
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
}
