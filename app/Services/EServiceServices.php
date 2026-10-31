<?php
declare(strict_types=1);

namespace Serv;

use Core\Database;
use Core\ImageManager;
use Repo\AuditTrailRepository;
use Repo\EServiceRepository;
use Throwable;

final class EServiceServices {
    private const STATUS_KEYS = ['active', 'inactive'];
    private const FILTER_KEYS = ['all', 'active', 'inactive'];
    private const SORT_KEYS = ['created-desc', 'created-asc', 'name-desc', 'name-asc'];
    private const PER_PAGE = 10;

    // Column limits (eservices table) / form limits (eservices.php)
    private const NAME_MAX = 150;
    private const DESCRIPTION_MAX = 500;
    private const URL_MAX = 2048;

    private const ENTITY_TYPE = 'E-Service';

    public function save(): void {
        $id = validateInt($_POST['id'] ?? null, 0, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid e-service id.');
        }

        if ($id === 0) {
            $this->create();
        } else {
            $this->update($id);
        }
    }

    /**
     * Validates the shared text fields of the form (create and update).
     *
     * @return array{name: string, description: string, url: string, status: string}
     */
    private function validateFields(): array {
        $name = trim(validateString($_POST['name'] ?? '', 'Name'));
        $description = trim(validateString($_POST['description'] ?? '', 'Description'));
        $url = trim(validateString($_POST['url'] ?? '', 'URL'));
        $status = strtolower(trim(validateString($_POST['status'] ?? '', 'Status')));

        if ($name === '') {
            jsonResponse(422, 'Name cannot be empty.');
        }

        if (mb_strlen($name) > self::NAME_MAX) {
            jsonResponse(422, 'Name cannot be longer than ' . self::NAME_MAX . ' characters.');
        }

        if ($description === '') {
            jsonResponse(422, 'Description cannot be empty.');
        }

        if (mb_strlen($description) > self::DESCRIPTION_MAX) {
            jsonResponse(422, 'Description cannot be longer than ' . self::DESCRIPTION_MAX . ' characters.');
        }

        // URL is optional (the form allows it to be blank), but must be a valid web address when given
        if ($url !== '') {
            if (strlen($url) > self::URL_MAX) {
                jsonResponse(422, 'URL cannot be longer than ' . self::URL_MAX . ' characters.');
            }

            $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

            if (filter_var($url, FILTER_VALIDATE_URL) === false || !in_array($scheme, ['http', 'https'], true)) {
                jsonResponse(422, 'URL must be a valid address starting with http:// or https://.');
            }
        }

        if (!in_array($status, self::STATUS_KEYS, true)) {
            jsonResponse(422, 'Invalid status selection.');
        }

        return [
            'name' => $name,
            'description' => $description,
            'url' => $url,
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

            $repo = new EServiceRepository($conn);
            $insertedId = $repo->insertItem([
                ...$fields,
                'featured_image' => $featuredImageFile,
                'author_id' => $authorId
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'CREATE',
                'description' => 'Created a new e-service named: ' . $fields['name'],
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

        jsonResponse(201, 'E-service successfully created.');
    }

    private function update(int $id): void {
        $repo = new EServiceRepository(Database::connect());
        $fetchedItem = $repo->fetchItemById($id);

        if ($fetchedItem === false) {
            jsonResponse(404, 'E-service does not exist.');
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

            $repo = new EServiceRepository($conn);
            $repo->updateItem([
                ...$fields,
                'featured_image' => $featuredImageFile,
                'editor_id' => $editorId,
                'id' => $id
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'UPDATE',
                'description' => 'Updated the e-service named: ' . $fields['name'],
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
            try {
                $imageHandler->delete($oldFeaturedImage);
            } catch (Throwable $e) {
                logError($e, 'Failed to delete old featured image.', 'ERROR', [
                    'action' => 'save e-service - update',
                    'endpoint' => 'eservices/save',
                    'id' => $id
                ]);
            }
        }

        jsonResponse(200, 'E-service successfully updated.');
    }

    public function filter(): void {
        $search = $_GET['search'] ?? '';
        $filter = $_GET['filter'] ?? 'all';
        $sort = $_GET['sort'] ?? 'created-desc';
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

        $data = [
            'search' => trim($search),
            'filter' => strtolower($filter),
            'sort' => $sort
        ];

        $repo = new EServiceRepository(Database::connect());
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

        jsonResponse(200, 'Successfully filtered e-service items.', [
            'eservices' => $items,
            'current_page' => $page,
            'total_eservices' => $totalItems,
            'total_pages' => $totalPages
        ]);
    }

    public function item(): void {
        $id = validateInt($_GET['id'] ?? null, 1, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid e-service id.');
        }

        $repo = new EServiceRepository(Database::connect());
        $item = $repo->fetchItemById($id);

        if ($item === false) {
            jsonResponse(404, 'E-service does not exist.');
        }

        jsonResponse(200, 'E-service successfully fetched.', [
            'eservice' => $item
        ]);
    }

    public function delete(): void {
        $id = validateInt($_POST['id'] ?? null, 1, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid e-service id.');
        }

        $repo = new EServiceRepository(Database::connect());
        $item = $repo->fetchItemById($id);

        if ($item === false) {
            jsonResponse(404, 'E-service does not exist.');
        }

        $name = $item['name'];

        session_start();

        $editorId = (int) $_SESSION['user_id'];

        session_write_close();

        $conn = Database::connect();

        try {
            $conn->beginTransaction();

            $repo = new EServiceRepository($conn);
            $repo->deleteItem([
                'editor_id' => $editorId,
                'id' => $id
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'DELETE',
                'description' => "Deleted the e-service named: $name",
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

        jsonResponse(200, 'E-service successfully deleted.');
    }

    // Removes a freshly stored image when the database change that used it failed
    private function discardNewImage(ImageManager $imageHandler, ?string $filename, string $action, ?int $id = null): void {
        if ($filename === null) {
            return;
        }

        try {
            $imageHandler->delete($filename);
        } catch (Throwable $e) {
            logError($e, 'Failed to delete new featured image after a database failure.', 'ERROR', [
                'action' => "save e-service - $action",
                'endpoint' => 'eservices/save',
                'id' => $id
            ]);
        }
    }
}
