<?php
declare(strict_types=1);

namespace Serv;

use Core\Database;
use Core\ImageManager;
use DateTimeImmutable;
use DateTimeZone;
use Repo\AuditTrailRepository;
use Repo\CategoryRepository;
use Repo\UnitPostRepository;
use Repo\UnitRepository;
use Throwable;

final class UnitPostServices {
    private const STATUS_KEYS = ['draft', 'published'];
    private const FILTER_KEYS = ['all', 'draft', 'published', 'pending', 'expired'];
    private const SORT_KEYS = ['newest', 'oldest', 'title-asc', 'title-desc', 'expiry-asc', 'expiry-desc'];
    private const PER_PAGE = 10;

    // Column limits (unit_posts table) / form limits (unitposts.php)
    private const TITLE_MAX = 150;
    private const EXCERPT_MAX = 500;

    // categories.class used by unit posts
    private const CATEGORY_CLASS = 'unit-posts';

    private const ENTITY_TYPE = 'Unit Post';

    public function save(): void {
        $id = validateInt($_POST['id'] ?? null, 0, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid unit post id.');
        }

        if ($id === 0) {
            $this->create();
        } else {
            $this->update($id);
        }
    }

    private function today(): string {
        return (new DateTimeImmutable('today', new DateTimeZone('Asia/Manila')))->format('Y-m-d');
    }

    /**
     * Validates the form fields shared by create and update.
     * Date rules that depend on the stored post are applied by the caller.
     *
     * @return array{title: string, excerpt: string, content: ?string, status: string, unit_id: int,
     *     category: ?int, published_at: string, expires_at: string}
     */
    private function validateFields(): array {
        $title = trim(validateString($_POST['title'] ?? '', 'Title'));
        $excerpt = trim(validateString($_POST['excerpt'] ?? '', 'Excerpt'));
        $status = strtolower(trim(validateString($_POST['status'] ?? '', 'Status')));

        // The unit posts page has no content field, so content is optional (null = not sent)
        $content = null;

        if (array_key_exists('content', $_POST)) {
            $content = trim(validateString($_POST['content'], 'Content'));
        }

        if ($title === '') {
            jsonResponse(422, 'Title cannot be empty.');
        }

        if (mb_strlen($title) > self::TITLE_MAX) {
            jsonResponse(422, 'Title cannot be longer than ' . self::TITLE_MAX . ' characters.');
        }

        if ($excerpt === '') {
            jsonResponse(422, 'Excerpt cannot be empty.');
        }

        if (mb_strlen($excerpt) > self::EXCERPT_MAX) {
            jsonResponse(422, 'Excerpt cannot be longer than ' . self::EXCERPT_MAX . ' characters.');
        }

        if (!in_array($status, self::STATUS_KEYS, true)) {
            jsonResponse(422, 'Invalid status selection.');
        }

        // Unit must exist and not be deleted. Each failure has its own message so the cause is clear.
        $rawUnit = $_POST['unit_id'] ?? null;

        if ($rawUnit === null || (is_string($rawUnit) && trim($rawUnit) === '')) {
            jsonResponse(422, 'Unit is required. Send the unit id in the unit_id field.');
        }

        $unitId = validateInt($rawUnit, 1, null);

        if ($unitId === false) {
            jsonResponse(422, 'Unit id must be a positive whole number (the unit_id field must hold an id, not a name).');
        }

        $unitRepo = new UnitRepository(Database::connect());

        if ($unitRepo->fetchItemById($unitId) === false) {
            jsonResponse(422, "Unit with id $unitId does not exist or has been deleted.");
        }

        // Category is optional on the form; see resolveCategory()
        $category = null;
        $rawCategory = $_POST['category'] ?? '';

        if (!is_string($rawCategory) || trim($rawCategory) !== '') {
            $category = validateInt($rawCategory, 1, null);

            if ($category === false) {
                jsonResponse(422, 'Invalid category id.');
            }
        }

        $publishDate = validateDate($_POST['publish_date'] ?? null);

        if ($publishDate === false) {
            jsonResponse(422, 'Publish date must be a valid date.');
        }

        // Expiry date is optional ("No expiry" on the page)
        $rawExpiry = $_POST['expiration_date'] ?? '';

        if (!is_string($rawExpiry)) {
            jsonResponse(422, 'Expiration date must be a valid date.');
        }

        if (trim($rawExpiry) === '') {
            $expiresAt = UnitPostRepository::NO_EXPIRY;
        } else {
            $expiresAt = validateDate(trim($rawExpiry));

            if ($expiresAt === false) {
                jsonResponse(422, 'Expiration date must be a valid date.');
            }

            if ($expiresAt < $publishDate) {
                jsonResponse(422, 'Expiration date cannot be earlier than the publish date.');
            }
        }

        return [
            'title' => $title,
            'excerpt' => $excerpt,
            'content' => $content,
            'status' => $status,
            'unit_id' => $unitId,
            'category' => $category,
            'published_at' => $publishDate,
            'expires_at' => $expiresAt
        ];
    }

    // The form has no category field, so fall back to the existing category or the class's only category
    private function resolveCategory(?int $requested, ?int $existing): int {
        $repo = new CategoryRepository(Database::connect());
        $ids = array_map('intval', array_column($repo->fetchItemsByClass(self::CATEGORY_CLASS), 'id'));

        if ($requested !== null) {
            if (!in_array($requested, $ids, true)) {
                jsonResponse(422, 'Invalid category selection.');
            }

            return $requested;
        }

        if ($existing !== null) {
            return $existing;
        }

        if (count($ids) === 1) {
            return $ids[0];
        }

        jsonResponse(422, 'Category is required.');
    }

    private function create(): void {
        $fields = $this->validateFields();
        $today = $this->today();

        if ($fields['published_at'] < $today) {
            jsonResponse(422, 'Publish date cannot be in the past.');
        }

        if ($fields['expires_at'] !== UnitPostRepository::NO_EXPIRY && $fields['expires_at'] < $today) {
            jsonResponse(422, 'Expiration date cannot be in the past.');
        }

        $categoryId = $this->resolveCategory($fields['category'], null);
        $featuredImage = $_FILES['featured_image'] ?? null;

        $imageHandler = new ImageManager();
        $tempFeaturedImage = null;

        if ($featuredImage !== null && ($featuredImage['error'] ?? null) !== UPLOAD_ERR_NO_FILE) {
            $tempFeaturedImage = $imageHandler->verify($featuredImage);
        }

        $featuredImageFile = null;

        if ($tempFeaturedImage !== null) {
            $featuredImageFile = $imageHandler->store($tempFeaturedImage);
        }

        session_start();

        $authorId = (int) $_SESSION['user_id'];

        session_write_close();

        $conn = Database::connect();

        try {
            $conn->beginTransaction();

            $repo = new UnitPostRepository($conn);
            $insertedId = $repo->insertItem([
                'title' => $fields['title'],
                'excerpt' => $fields['excerpt'],
                'content' => $fields['content'] ?? '',
                'featured_image' => $featuredImageFile,
                'unit_id' => $fields['unit_id'],
                'category_id' => $categoryId,
                'status' => $fields['status'],
                'author_id' => $authorId,
                'published_at' => $fields['published_at'],
                'expires_at' => $fields['expires_at']
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'CREATE',
                'description' => 'Created a new unit post titled: ' . $fields['title'],
                'entity_type' => self::ENTITY_TYPE,
                'entity_id' => (int) $insertedId,
                'user_id' => $authorId
            ]);

            $conn->commit();
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $this->discardNewImage($imageHandler, $featuredImageFile, 'create');

            throw $e;
        }

        jsonResponse(201, 'Unit post successfully created.');
    }

    private function update(int $id): void {
        $repo = new UnitPostRepository(Database::connect());
        $fetchedItem = $repo->fetchItemById($id);

        if ($fetchedItem === false) {
            jsonResponse(404, 'Unit post does not exist.');
        }

        $fields = $this->validateFields();
        $today = $this->today();

        // Dates are only checked against today when they were changed, so an old post can still be edited
        $storedPublishDate = substr((string) $fetchedItem['published_at'], 0, 10);
        $storedExpiryDate = substr((string) $fetchedItem['expires_at'], 0, 10);

        if ($fields['published_at'] !== $storedPublishDate && $fields['published_at'] < $today) {
            jsonResponse(422, 'Publish date cannot be in the past.');
        }

        if (
            $fields['expires_at'] !== UnitPostRepository::NO_EXPIRY
            && $fields['expires_at'] !== $storedExpiryDate
            && $fields['expires_at'] < $today
        ) {
            jsonResponse(422, 'Expiration date cannot be in the past.');
        }

        $categoryId = $this->resolveCategory($fields['category'], (int) $fetchedItem['category_id']);
        $featuredImage = $_FILES['featured_image'] ?? null;

        $removeFeaturedImage = filter_var(
            $_POST['remove_featured_image'] ?? false,
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE
        );

        if ($removeFeaturedImage === null) {
            jsonResponse(422, 'Invalid remove featured image indicator.');
        }

        $hasNewFeaturedImage = ($featuredImage !== null && ($featuredImage['error'] ?? null) !== UPLOAD_ERR_NO_FILE);

        if ($removeFeaturedImage && $hasNewFeaturedImage) {
            jsonResponse(422, 'Cannot remove and replace featured image at the same time.');
        }

        $imageHandler = new ImageManager();
        $tempFeaturedImage = null;

        if ($hasNewFeaturedImage) {
            $tempFeaturedImage = $imageHandler->verify($featuredImage);
        }

        $oldFeaturedImage = $fetchedItem['featured_image'] ?? null;
        $featuredImageFile = $oldFeaturedImage;
        $newFeaturedImageFile = null;

        if ($removeFeaturedImage) {
            $featuredImageFile = null;
        } elseif ($tempFeaturedImage !== null) {
            $newFeaturedImageFile = $imageHandler->store($tempFeaturedImage);
            $featuredImageFile = $newFeaturedImageFile;
        }

        session_start();

        $editorId = (int) $_SESSION['user_id'];

        session_write_close();

        $conn = Database::connect();

        try {
            $conn->beginTransaction();

            $repo = new UnitPostRepository($conn);
            $repo->updateItem([
                'title' => $fields['title'],
                'excerpt' => $fields['excerpt'],
                // Content is kept as-is when the request does not send it
                'content' => $fields['content'] ?? (string) $fetchedItem['content'],
                'featured_image' => $featuredImageFile,
                'unit_id' => $fields['unit_id'],
                'category_id' => $categoryId,
                'status' => $fields['status'],
                'editor_id' => $editorId,
                'published_at' => $fields['published_at'],
                'expires_at' => $fields['expires_at'],
                'id' => $id
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'UPDATE',
                'description' => 'Updated the unit post titled: ' . $fields['title'],
                'entity_type' => self::ENTITY_TYPE,
                'entity_id' => $id,
                'user_id' => $editorId
            ]);

            $conn->commit();
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $this->discardNewImage($imageHandler, $newFeaturedImageFile, 'update', $id);

            throw $e;
        }

        // The old image is only removed once the database change has been committed
        if (is_string($oldFeaturedImage) && $oldFeaturedImage !== '' && $oldFeaturedImage !== $featuredImageFile) {
            $this->discardNewImage($imageHandler, $oldFeaturedImage, 'update (old image)', $id);
        }

        jsonResponse(200, 'Unit post successfully updated.');
    }

    public function filter(): void {
        $search = $_GET['search'] ?? '';
        $filter = $_GET['filter'] ?? 'all';
        $sort = $_GET['sort'] ?? 'newest';
        $page = validateInt($_GET['page'] ?? 1, 1, null);

        if (!is_string($search)) {
            jsonResponse(422, 'Search must be a valid string.');
        }

        if (!is_string($filter) || !in_array(strtolower($filter), self::FILTER_KEYS, true)) {
            jsonResponse(422, 'Invalid filter selection.');
        }

        if (!is_string($sort) || !in_array($sort, self::SORT_KEYS, true)) {
            jsonResponse(422, 'Invalid sort selection.');
        }

        if ($page === false) {
            jsonResponse(422, 'Invalid page number.');
        }

        // Optional unit filter (absent or empty = all units)
        $unitId = null;
        $rawUnit = $_GET['unit_id'] ?? '';

        if (!is_string($rawUnit) || trim($rawUnit) !== '') {
            $unitId = validateInt($rawUnit, 1, null);

            if ($unitId === false) {
                jsonResponse(422, 'Invalid unit selection.');
            }
        }

        $data = [
            'search' => trim($search),
            'filter' => strtolower($filter),
            'sort' => $sort,
            'unit_id' => $unitId
        ];

        $repo = new UnitPostRepository(Database::connect());
        $totalItems = $repo->countFiltered($data);
        $totalPages = (int) ceil($totalItems / self::PER_PAGE);

        if ($page > max(1, $totalPages)) {
            jsonResponse(422, 'Requested page does not exist.');
        }

        $items = $repo->filter([
            ...$data,
            'limit' => self::PER_PAGE,
            'offset' => ($page - 1) * self::PER_PAGE
        ]);

        jsonResponse(200, 'Successfully filtered unit post items.', [
            'unit_posts' => $items,
            'current_page' => $page,
            'total_unit_posts' => $totalItems,
            'total_pages' => $totalPages
        ]);
    }

    public function item(): void {
        $id = validateInt($_GET['id'] ?? null, 1, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid unit post id.');
        }

        $repo = new UnitPostRepository(Database::connect());
        $item = $repo->fetchItemById($id);

        if ($item === false) {
            jsonResponse(404, 'Unit post does not exist.');
        }

        jsonResponse(200, 'Unit post successfully fetched.', [
            'unit_post' => $item
        ]);
    }

    public function delete(): void {
        $id = validateInt($_POST['id'] ?? null, 1, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid unit post id.');
        }

        $repo = new UnitPostRepository(Database::connect());
        $item = $repo->fetchItemById($id);

        if ($item === false) {
            jsonResponse(404, 'Unit post does not exist.');
        }

        $title = $item['title'];

        session_start();

        $editorId = (int) $_SESSION['user_id'];

        session_write_close();

        $conn = Database::connect();

        try {
            $conn->beginTransaction();

            $repo = new UnitPostRepository($conn);
            $repo->deleteItem([
                'editor_id' => $editorId,
                'id' => $id
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'DELETE',
                'description' => "Deleted the unit post titled: $title",
                'entity_type' => self::ENTITY_TYPE,
                'entity_id' => $id,
                'user_id' => $editorId
            ]);

            $conn->commit();
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            throw $e;
        }

        jsonResponse(200, 'Unit post successfully deleted.');
    }

    // Deletes a stored image file, logging (not throwing) on failure
    private function discardNewImage(ImageManager $imageHandler, ?string $filename, string $action, ?int $id = null): void {
        if ($filename === null || $filename === '') {
            return;
        }

        try {
            $imageHandler->delete($filename);
        } catch (Throwable $e) {
            logError($e, 'Failed to delete unit post featured image.', 'ERROR', [
                'action' => "save unit post - $action",
                'endpoint' => 'unit-posts/save',
                'id' => $id
            ]);
        }
    }
}
