<?php
declare(strict_types=1);

namespace Serv;

use Core\Database;
use Core\ImageManager;
use Repo\AuditTrailRepository;
use Repo\OfficialRepository;
use Throwable;

final class OfficialServices {
    private const STATUS_KEYS = ['active', 'inactive'];
    private const FILTER_KEYS = ['all', 'active', 'inactive'];
    private const SORT_KEYS = ['ascending', 'descending', 'name'];
    private const PER_PAGE = 10;

    // Column limits (officials table)
    private const NAME_MAX = 150;
    private const POSITION_MAX = 150;
    private const ORDER_MAX = 4294967295; // INT UNSIGNED
    // The form has no limit on the biography; this only keeps requests sensible
    private const BIOGRAPHY_MAX = 5000;

    private const ENTITY_TYPE = 'Official';

    // The executive page labels the inactive state "Hidden"
    private function normalizeStatus(string $value): string {
        $value = strtolower(trim($value));

        return $value === 'hidden' ? 'inactive' : $value;
    }

    public function save(): void {
        $id = validateInt($_POST['id'] ?? null, 0, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid official id.');
        }

        if ($id === 0) {
            $this->create();
        } else {
            $this->update($id);
        }
    }

    /** @return array{name: string, position: string, biography: ?string, order_number: int, status: string} */
    private function validateFields(): array {
        $name = trim(validateString($_POST['name'] ?? '', 'Name'));
        $position = trim(validateString($_POST['position'] ?? '', 'Position'));
        $biography = trim(validateString($_POST['biography'] ?? '', 'Biography'));
        $status = $this->normalizeStatus(validateString($_POST['status'] ?? '', 'Status'));
        $orderNumber = validateInt($_POST['order_number'] ?? null, 1, self::ORDER_MAX);

        if ($name === '') {
            jsonResponse(422, 'Name cannot be empty.');
        }

        if (mb_strlen($name) > self::NAME_MAX) {
            jsonResponse(422, 'Name cannot be longer than ' . self::NAME_MAX . ' characters.');
        }

        if ($position === '') {
            jsonResponse(422, 'Position cannot be empty.');
        }

        if (mb_strlen($position) > self::POSITION_MAX) {
            jsonResponse(422, 'Position cannot be longer than ' . self::POSITION_MAX . ' characters.');
        }

        // Biography is optional; a blank value is stored as NULL
        if (mb_strlen($biography) > self::BIOGRAPHY_MAX) {
            jsonResponse(422, 'Biography cannot be longer than ' . self::BIOGRAPHY_MAX . ' characters.');
        }

        if ($orderNumber === false) {
            jsonResponse(422, 'Display order must be a whole number of 1 or more.');
        }

        if (!in_array($status, self::STATUS_KEYS, true)) {
            jsonResponse(422, 'Invalid status selection.');
        }

        return [
            'name' => $name,
            'position' => $position,
            'biography' => $biography === '' ? null : $biography,
            'order_number' => $orderNumber,
            'status' => $status
        ];
    }

    private function create(): void {
        $fields = $this->validateFields();
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

            $repo = new OfficialRepository($conn);
            $insertedId = $repo->insertItem([
                ...$fields,
                'featured_image' => $featuredImageFile,
                'author_id' => $authorId
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'CREATE',
                'description' => 'Created a new official named: ' . $fields['name'],
                'entity_type' => self::ENTITY_TYPE,
                'entity_id' => (int) $insertedId,
                'user_id' => $authorId
            ]);

            $conn->commit();
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $this->discardImage($imageHandler, $featuredImageFile, 'create');

            throw $e;
        }

        jsonResponse(201, 'Official successfully created.');
    }

    private function update(int $id): void {
        $repo = new OfficialRepository(Database::connect());
        $fetchedItem = $repo->fetchItemById($id);

        if ($fetchedItem === false) {
            jsonResponse(404, 'Official does not exist.');
        }

        $fields = $this->validateFields();
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

            $repo = new OfficialRepository($conn);
            $repo->updateItem([
                ...$fields,
                'featured_image' => $featuredImageFile,
                'editor_id' => $editorId,
                'id' => $id
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'UPDATE',
                'description' => 'Updated the official named: ' . $fields['name'],
                'entity_type' => self::ENTITY_TYPE,
                'entity_id' => $id,
                'user_id' => $editorId
            ]);

            $conn->commit();
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $this->discardImage($imageHandler, $newFeaturedImageFile, 'update', $id);

            throw $e;
        }

        // The old image is only removed once the database change has been committed
        if (is_string($oldFeaturedImage) && $oldFeaturedImage !== '' && $oldFeaturedImage !== $featuredImageFile) {
            $this->discardImage($imageHandler, $oldFeaturedImage, 'update (old image)', $id);
        }

        jsonResponse(200, 'Official successfully updated.');
    }

    public function filter(): void {
        $search = $_GET['search'] ?? '';
        $filter = $_GET['filter'] ?? 'all';
        $sort = $_GET['sort'] ?? 'ascending';
        $page = validateInt($_GET['page'] ?? 1, 1, null);

        if (!is_string($search)) {
            jsonResponse(422, 'Search must be a valid string.');
        }

        if (!is_string($filter)) {
            jsonResponse(422, 'Invalid filter selection.');
        }

        $filter = $this->normalizeStatus($filter);

        if (!in_array($filter, self::FILTER_KEYS, true)) {
            jsonResponse(422, 'Invalid filter selection.');
        }

        if (!is_string($sort) || !in_array($sort, self::SORT_KEYS, true)) {
            jsonResponse(422, 'Invalid sort selection.');
        }

        if ($page === false) {
            jsonResponse(422, 'Invalid page number.');
        }

        $data = [
            'search' => trim($search),
            'filter' => $filter,
            'sort' => $sort
        ];

        $repo = new OfficialRepository(Database::connect());
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

        jsonResponse(200, 'Successfully filtered official items.', [
            'officials' => $items,
            'current_page' => $page,
            'total_officials' => $totalItems,
            'total_pages' => $totalPages
        ]);
    }

    public function item(): void {
        $id = validateInt($_GET['id'] ?? null, 1, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid official id.');
        }

        $repo = new OfficialRepository(Database::connect());
        $item = $repo->fetchItemById($id);

        if ($item === false) {
            jsonResponse(404, 'Official does not exist.');
        }

        jsonResponse(200, 'Official successfully fetched.', [
            'official' => $item
        ]);
    }

    public function delete(): void {
        $id = validateInt($_POST['id'] ?? null, 1, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid official id.');
        }

        $repo = new OfficialRepository(Database::connect());
        $item = $repo->fetchItemById($id);

        if ($item === false) {
            jsonResponse(404, 'Official does not exist.');
        }

        $name = $item['name'];

        session_start();

        $editorId = (int) $_SESSION['user_id'];

        session_write_close();

        $conn = Database::connect();

        try {
            $conn->beginTransaction();

            $repo = new OfficialRepository($conn);
            $repo->deleteItem([
                'editor_id' => $editorId,
                'id' => $id
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'DELETE',
                'description' => "Deleted the official named: $name",
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

        jsonResponse(200, 'Official successfully deleted.');
    }

    // Deletes a stored image file, logging (not throwing) on failure
    private function discardImage(ImageManager $imageHandler, ?string $filename, string $action, ?int $id = null): void {
        if ($filename === null || $filename === '') {
            return;
        }

        try {
            $imageHandler->delete($filename);
        } catch (Throwable $e) {
            logError($e, 'Failed to delete official photo.', 'ERROR', [
                'action' => "save official - $action",
                'endpoint' => 'officials/save',
                'id' => $id
            ]);
        }
    }
}
