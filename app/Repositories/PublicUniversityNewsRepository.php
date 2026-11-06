<?php
declare(strict_types=1);

namespace Repo;

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../app/Support/helper.php';

use PDO;
use Throwable;

final class PublicUniversityNewsRepository
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getLatestNews(): array
    {
        $sql = "SELECT p.id, c.name AS category, p.title, p.excerpt, p.content,
                    p.featured_image, p.status, p.published_at, p.active
                FROM posts p
                INNER JOIN categories c ON c.id = p.category_id
                WHERE p.active = 1
                  AND p.status = 'published'
                  AND p.category_id = 1
                  AND (p.expires_at IS NULL OR p.expires_at > NOW())
                  AND p.published_at <= NOW()
                ORDER BY p.published_at DESC
                LIMIT 1";

        return $this->conn->query($sql)->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function getThreeLatestNews(): array
    {
        $sql = "SELECT p.id, p.title, p.excerpt, p.content,
                    p.featured_image, p.status, p.published_at, p.active
                FROM posts p
                WHERE p.active = 1
                  AND p.status = 'published'
                  AND p.category_id = 1
                  AND (p.expires_at IS NULL OR p.expires_at > NOW())
                  AND p.published_at <= NOW()
                ORDER BY p.published_at DESC
                LIMIT 3";

        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllNews(): array
    {
        $sql = "SELECT p.id, c.name AS category, p.title, p.excerpt, p.content,
                    p.featured_image, p.status, p.published_at, p.active
                FROM posts p
                INNER JOIN categories c ON c.id = p.category_id
                WHERE p.active = 1
                  AND p.status = 'published'
                  AND p.category_id = 1
                  AND (p.expires_at IS NULL OR p.expires_at > NOW())
                  AND p.published_at <= NOW()
                ORDER BY p.published_at DESC";

        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNewsById(int $postId): array
{
    if ($postId < 1) {
        return [];
    }

        $sql = "SELECT
                    p.id,
                    c.name AS category,
                    p.title,
                    p.excerpt,
                    p.content,
                    p.featured_image,
                    p.published_at,
                    p.views
                FROM posts p
                INNER JOIN categories c ON c.id = p.category_id
                WHERE p.id = ?
                AND p.active = 1
                AND p.status = 'published'
                AND p.category_id = 1
                AND (p.expires_at IS NULL OR p.expires_at > NOW())
                AND p.published_at <= NOW()
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$postId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    
public function getPostById(int $postId): array
{
    if ($postId < 1) {
        return [];
    }

    $sql = "SELECT
                p.id,
                p.title,
                p.excerpt,
                p.content,
                p.featured_image,
                p.category_id,
                p.published_at,
                p.status,
                p.active,
                p.expires_at,
                COALESCE(c.name, 'University Update') AS category
            FROM posts p
            LEFT JOIN categories c
                ON c.id = p.category_id
            WHERE p.id = ?
            LIMIT 1";

    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$postId]);

    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        return [];
    }

    // Only display active and published posts.
    if (
        (int) $post['active'] !== 1 ||
        $post['status'] !== 'published'
    ) {
        return [];
    }

    // Check whether the article has expired.
    $expiresAt = $post['expires_at'] ?? null;

    if (
        !empty($expiresAt) &&
        $expiresAt !== '0000-00-00 00:00:00' &&
        strtotime($expiresAt) !== false &&
        strtotime($expiresAt) <= time()
    ) {
        return [];
    }

    // Do not display articles scheduled for a future date.
    $publishedAt = $post['published_at'] ?? null;

    if (
        !empty($publishedAt) &&
        strtotime($publishedAt) !== false &&
        strtotime($publishedAt) > time()
    ) {
        return [];
    }

    return $post;
}


    /**
     * Increment a news post's lifetime view count at most once per visitor
     * during each rolling 24-hour period.
     *
     * Requires a unique key on posts_views (post_id, visitor_id).
     * Returns true when this call increments posts.views, false when the
     * visitor's view is still inside the 24-hour window.
     */
    public function recordView(int $postId, string $visitorId): bool
    {
        if ($postId < 1 || preg_match('/\A[a-f0-9]{64}\z/', $visitorId) !== 1) {
            throw new \InvalidArgumentException('Invalid post id or visitor id.');
        }

        $this->conn->beginTransaction();

        try {
            // A unique key makes first-time view insertion safe from duplicate clicks.
            $insert = $this->conn->prepare(
                'INSERT IGNORE INTO posts_views (post_id, visitor_id, viewed_at)
                 VALUES (?, ?, CURRENT_TIMESTAMP)'
            );
            $insert->execute([$postId, $visitorId]);
            $countThisView = $insert->rowCount() === 1;

            if (!$countThisView) {
                // The conditional UPDATE is atomic: only the first request after
                // the 24-hour boundary can refresh this visitor/post pair.
                $refresh = $this->conn->prepare(
                    'UPDATE posts_views
                     SET viewed_at = CURRENT_TIMESTAMP
                     WHERE post_id = ?
                       AND visitor_id = ?
                       AND viewed_at <= CURRENT_TIMESTAMP - INTERVAL 24 HOUR'
                );
                $refresh->execute([$postId, $visitorId]);
                $countThisView = $refresh->rowCount() === 1;
            }

            if ($countThisView) {
                $increment = $this->conn->prepare(
                    'UPDATE posts
                     SET views = COALESCE(views, 0) + 1
                     WHERE id = ?
                       AND active = 1
                       AND status = \'published\'
                       AND category_id = 1'
                );
                $increment->execute([$postId]);

                if ($increment->rowCount() !== 1) {
                    throw new \RuntimeException('Published news post was not found.');
                }
            }

            $this->conn->commit();
            return $countThisView;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }
}