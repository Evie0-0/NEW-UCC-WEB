<?php
declare(strict_types=1);

namespace Serv;

use Core\Database;
use Core\ImageManager;
use Repo\AuditTrailRepository;
use Repo\RegentRepository;
use Throwable;

final class RegentServices {
    private const STATUS_KEYS = ['active', 'inactive'];
    private const FILTER_KEYS = ['all', 'active', 'inactive'];
    private const SORT_KEYS = ['order-asc', 'order-desc', 'name-asc', 'name-desc', 'create-desc', 'create-asc'];
    private const NAME_MAX_LENGTH = 150;
    private const POSITION_MAX_LENGTH = 150;
    private const ORDER_MIN = 1;
    private const ORDER_MAX = 9999;
    // The public website shows the regent with this order number as the Chairman
    private const CHAIRMAN_ORDER = 1;
    private const PER_PAGE = 10;

    public function save(): void {
        $id = validateInt($_POST['id'] ?? null, 0, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid regent id.');
        }

        if ($id === 0) {
            $this->create();
        } else {
            $this->update($id);
        }
    }

    private function create(): void {
        $input = $this->readInput(0);
        $imageHandler = new ImageManager();
        $tempFeaturedImage = null;

        if ($this->hasNewImage()) {
            $tempFeaturedImage = $imageHandler->verify($_FILES['featured_image']);
        }

        $authorId = $this->currentUserId();
        $conn = Database::connect();
        $featuredImageFile = null;

        if (is_string($tempFeaturedImage)) {
            $featuredImageFile = $imageHandler->store($tempFeaturedImage);
        }

        try {
            $conn->beginTransaction();

            $repo = new RegentRepository($conn);
            $insertedId = $repo->insertItem([
                'name' => $input['name'],
                'position' => $input['position'],
                'biography' => $input['biography'],
                'featured_image' => $featuredImageFile,
                'order_number' => $input['order_number'],
                'status' => $input['status'],
                'author_id' => $authorId
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'CREATE',
                'description' => "Created a new regent named: {$input['name']}",
                'entity_type' => 'Regent',
                'entity_id' => $insertedId,
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

        jsonResponse(201, 'Regent successfully created.');
    }

    private function update(int $id): void {
        $repo = new RegentRepository(Database::connect());
        $fetchedRegent = $repo->fetchItemById($id);

        if ($fetchedRegent === false) {
            jsonResponse(404, 'Regent does not exist.');
        }

        $input = $this->readInput($id);
        $removeFeaturedImage = filter_var($_POST['remove_featured_image'] ?? 'false', FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if ($removeFeaturedImage === null) {
            jsonResponse(422, 'Invalid remove featured image indicator.');
        }

        $hasNewFeaturedImage = $this->hasNewImage();

        if ($removeFeaturedImage && $hasNewFeaturedImage) {
            jsonResponse(422, 'Cannot remove and replace featured image at the same time.');
        }

        $imageHandler = new ImageManager();
        $tempFeaturedImage = null;

        if ($hasNewFeaturedImage) {
            $tempFeaturedImage = $imageHandler->verify($_FILES['featured_image']);
        }

        $oldFeaturedImage = $fetchedRegent['featured_image'] ?? null;
        $featuredImageFile = $oldFeaturedImage;
        $newFeaturedImageFile = null;
        $editorId = $this->currentUserId();
        $conn = Database::connect();

        if ($removeFeaturedImage) {
            $featuredImageFile = null;
        } elseif (is_string($tempFeaturedImage)) {
            $newFeaturedImageFile = $imageHandler->store($tempFeaturedImage);
            $featuredImageFile = $newFeaturedImageFile;
        }

        try {
            $conn->beginTransaction();

            $repo = new RegentRepository($conn);
            $repo->updateItem([
                'name' => $input['name'],
                'position' => $input['position'],
                'biography' => $input['biography'],
                'featured_image' => $featuredImageFile,
                'order_number' => $input['order_number'],
                'status' => $input['status'],
                'editor_id' => $editorId,
                'id' => $id
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'UPDATE',
                'description' => "Updated the regent named: {$input['name']}",
                'entity_type' => 'Regent',
                'entity_id' => $id,
                'user_id' => $editorId
            ]);

            $conn->commit();
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $this->discardImage($imageHandler, $newFeaturedImageFile, 'update');

            throw $e;
        }

        // The old image is only removed after the database change succeeded
        if (is_string($oldFeaturedImage) && $oldFeaturedImage !== '' && $oldFeaturedImage !== $featuredImageFile) {
            $this->discardImage($imageHandler, $oldFeaturedImage, 'update');
        }

        jsonResponse(200, 'Regent successfully updated.');
    }

    public function filter(): void {
        $search = $_GET['search'] ?? '';
        $filter = $_GET['filter'] ?? 'all';
        $sort = $_GET['sort'] ?? 'order-asc';
        $page = validateInt($_GET['page'] ?? 1, 1, null);

        if (!is_string($search)) {
            jsonResponse(422, 'Search must be a valid string.');
        }

        $search = trim($search);

        if (!in_array($filter, self::FILTER_KEYS, true)) {
            jsonResponse(422, 'Invalid filter selection.');
        }

        if (!in_array($sort, self::SORT_KEYS, true)) {
            jsonResponse(422, 'Invalid sort selection.');
        }

        if ($page === false) {
            jsonResponse(422, 'Invalid page number.');
        }

        $data = [
            'search' => $search,
            'filter' => $filter,
            'sort' => $sort
        ];

        $repo = new RegentRepository(Database::connect());
        $totalRegents = $repo->countFiltered($data);
        $totalPages = (int) ceil($totalRegents / self::PER_PAGE);

        if ($page > max(1, $totalPages)) {
            jsonResponse(422, 'Requested page does not exist.');
        }

        $regents = $repo->filter([
            ...$data,
            'limit' => self::PER_PAGE,
            'offset' => ($page - 1) * self::PER_PAGE
        ]);

        jsonResponse(200, 'Successfully filtered regent items.', [
            'regents' => $regents,
            'current_page' => $page,
            'total_regents' => $totalRegents,
            'total_pages' => $totalPages,
            'next_order' => $repo->fetchNextOrderNumber()
        ]);
    }

    public function item(): void {
        $id = validateInt($_GET['id'] ?? null, 1, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid regent id.');
        }

        $repo = new RegentRepository(Database::connect());
        $regent = $repo->fetchItemById($id);

        if ($regent === false) {
            jsonResponse(404, 'Regent does not exist.');
        }

        jsonResponse(200, 'Regent successfully fetched.', [
            'regent' => $regent
        ]);
    }

    public function delete(): void {
        $id = validateInt($_POST['id'] ?? null, 1, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid regent id.');
        }

        $repo = new RegentRepository(Database::connect());
        $regent = $repo->fetchItemById($id);

        if ($regent === false) {
            jsonResponse(404, 'Regent does not exist.');
        }

        $name = $regent['name'];
        $editorId = $this->currentUserId();
        $conn = Database::connect();

        try {
            $conn->beginTransaction();

            $repo = new RegentRepository($conn);
            $repo->deleteItem([
                'editor_id' => $editorId,
                'id' => $id
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'DELETE',
                'description' => "Deleted the regent named: $name",
                'entity_type' => 'Regent',
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

        jsonResponse(200, 'Regent successfully deleted.');
    }

    // Validates the text fields shared by create and update ($id is 0 when creating)
    /** @return array{name: string, position: string, biography: ?string, order_number: int, status: string} */
    private function readInput(int $id): array {
        $name = trim(validateString($_POST['name'] ?? '', 'Name'));
        $position = trim(validateString($_POST['position'] ?? '', 'Position'));
        $biography = trim(validateString($_POST['biography'] ?? '', 'Biography'));
        $orderNumber = validateInt($_POST['order_number'] ?? null, self::ORDER_MIN, self::ORDER_MAX);
        $status = $_POST['status'] ?? '';

        if ($name === '') {
            jsonResponse(422, 'Name cannot be empty.');
        }

        if (mb_strlen($name) > self::NAME_MAX_LENGTH) {
            jsonResponse(422, 'Name cannot exceed ' . self::NAME_MAX_LENGTH . ' characters.');
        }

        if ($position === '') {
            jsonResponse(422, 'Position cannot be empty.');
        }

        if (mb_strlen($position) > self::POSITION_MAX_LENGTH) {
            jsonResponse(422, 'Position cannot exceed ' . self::POSITION_MAX_LENGTH . ' characters.');
        }

        if ($orderNumber === false) {
            jsonResponse(422, 'Order number must be a whole number from ' . self::ORDER_MIN . ' to ' . self::ORDER_MAX . '.');
        }

        if (!in_array($status, self::STATUS_KEYS, true)) {
            jsonResponse(422, 'Invalid status selection.');
        }

        // Only one regent can be the Chairman
        if ($orderNumber === self::CHAIRMAN_ORDER) {
            $repo = new RegentRepository(Database::connect());
            $chairman = $repo->fetchByOrderNumber($orderNumber, $id);

            if ($chairman !== false) {
                jsonResponse(409, "Order number 1 is the Chairman and is already used by {$chairman['name']}. Change that regent's order number first.");
            }
        }

        return [
            'name' => $name,
            'position' => $position,
            'biography' => $biography === '' ? null : $biography,
            'order_number' => $orderNumber,
            'status' => $status
        ];
    }

    private function hasNewImage(): bool {
        $file = $_FILES['featured_image'] ?? null;

        return is_array($file) && ($file['error'] ?? null) !== UPLOAD_ERR_NO_FILE;
    }

    private function currentUserId(): int {
        session_start();

        $userId = (int) ($_SESSION['user_id'] ?? 0);

        session_write_close();

        return $userId;
    }

    // Removes an image file; a failure here is only logged because the database result already stands
    private function discardImage(ImageManager $imageHandler, ?string $filename, string $action): void {
        if ($filename === null || $filename === '') {
            return;
        }

        try {
            $imageHandler->delete($filename);
        } catch (Throwable $e) {
            logError($e, 'Failed to delete a regent photo.', 'ERROR', [
                'action' => "save regent - $action",
                'endpoint' => 'regents/save',
                'filename' => $filename
            ]);
        }
    }
}
