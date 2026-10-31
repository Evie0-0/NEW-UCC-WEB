<?php
declare(strict_types=1);

namespace Serv;

use Core\Database;
use Core\ImageManager;
use Repo\AuditTrailRepository;
use Repo\UnitRepository;
use Throwable;

final class UnitServices {
    private const STATUS_KEYS = ['active', 'inactive'];
    private const FILTER_KEYS = ['all', 'active', 'inactive'];
    private const SORT_KEYS = ['create-desc', 'create-asc', 'name-asc', 'name-desc'];
    private const NAME_MAX_LENGTH = 150;
    private const DEAN_MAX_LENGTH = 150;
    private const PER_PAGE = 10;

    public function save(): void {
        $id = validateInt($_POST['id'] ?? null, 0, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid unit id.');
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
        $logoInput = $this->readImageInput('logo', 'remove_logo', $imageHandler);
        $deanImageInput = $this->readImageInput('dean_image', 'remove_dean_image', $imageHandler);

        $authorId = $this->currentUserId();
        $conn = Database::connect();
        $logoFile = null;
        $deanImageFile = null;

        try {
            // Files are stored last so a failed request leaves nothing behind
            $logoFile = $this->storeImage($logoInput, $imageHandler);
            $deanImageFile = $this->storeImage($deanImageInput, $imageHandler);

            $conn->beginTransaction();

            $repo = new UnitRepository($conn);
            $insertedId = $repo->insertItem([
                'name' => $input['name'],
                'logo' => $logoFile,
                'dean' => $input['dean'],
                'dean_image' => $deanImageFile,
                'status' => $input['status'],
                'author_id' => $authorId
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'CREATE',
                'description' => "Created a new unit named: {$input['name']}",
                'entity_type' => 'Unit',
                'entity_id' => $insertedId,
                'user_id' => $authorId
            ]);

            $conn->commit();
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $this->discardImage($imageHandler, $logoFile, 'create');
            $this->discardImage($imageHandler, $deanImageFile, 'create');

            throw $e;
        }

        jsonResponse(201, 'Unit successfully created.');
    }

    private function update(int $id): void {
        $repo = new UnitRepository(Database::connect());
        $fetchedUnit = $repo->fetchItemById($id);

        if ($fetchedUnit === false) {
            jsonResponse(404, 'Unit does not exist.');
        }

        $input = $this->readInput($id);
        $imageHandler = new ImageManager();
        $logoInput = $this->readImageInput('logo', 'remove_logo', $imageHandler);
        $deanImageInput = $this->readImageInput('dean_image', 'remove_dean_image', $imageHandler);

        $oldLogo = $fetchedUnit['logo'] ?? null;
        $oldDeanImage = $fetchedUnit['dean_image'] ?? null;
        $logoFile = $oldLogo;
        $deanImageFile = $oldDeanImage;
        $newLogoFile = null;
        $newDeanImageFile = null;
        $editorId = $this->currentUserId();
        $conn = Database::connect();

        try {
            [$logoFile, $newLogoFile] = $this->resolveImage($logoInput, $oldLogo, $imageHandler);
            [$deanImageFile, $newDeanImageFile] = $this->resolveImage($deanImageInput, $oldDeanImage, $imageHandler);

            $conn->beginTransaction();

            $repo = new UnitRepository($conn);
            $repo->updateItem([
                'name' => $input['name'],
                'logo' => $logoFile,
                'dean' => $input['dean'],
                'dean_image' => $deanImageFile,
                'status' => $input['status'],
                'editor_id' => $editorId,
                'id' => $id
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'UPDATE',
                'description' => "Updated the unit named: {$input['name']}",
                'entity_type' => 'Unit',
                'entity_id' => $id,
                'user_id' => $editorId
            ]);

            $conn->commit();
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $this->discardImage($imageHandler, $newLogoFile, 'update');
            $this->discardImage($imageHandler, $newDeanImageFile, 'update');

            throw $e;
        }

        // Old files are only removed after the database change succeeded
        $this->discardReplacedImage($imageHandler, $oldLogo, $logoFile, 'update');
        $this->discardReplacedImage($imageHandler, $oldDeanImage, $deanImageFile, 'update');

        jsonResponse(200, 'Unit successfully updated.');
    }

    public function filter(): void {
        $search = $_GET['search'] ?? '';
        $filter = $_GET['filter'] ?? 'all';
        $sort = $_GET['sort'] ?? 'name-asc';
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

        $repo = new UnitRepository(Database::connect());
        $totalUnits = $repo->countFiltered($data);
        $totalPages = (int) ceil($totalUnits / self::PER_PAGE);

        if ($page > max(1, $totalPages)) {
            jsonResponse(422, 'Requested page does not exist.');
        }

        $units = $repo->filter([
            ...$data,
            'limit' => self::PER_PAGE,
            'offset' => ($page - 1) * self::PER_PAGE
        ]);

        jsonResponse(200, 'Successfully filtered unit items.', [
            'units' => $units,
            'current_page' => $page,
            'total_units' => $totalUnits,
            'total_pages' => $totalPages
        ]);
    }

    public function item(): void {
        $id = validateInt($_GET['id'] ?? null, 1, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid unit id.');
        }

        $repo = new UnitRepository(Database::connect());
        $unit = $repo->fetchItemById($id);

        if ($unit === false) {
            jsonResponse(404, 'Unit does not exist.');
        }

        jsonResponse(200, 'Unit successfully fetched.', [
            'unit' => $unit
        ]);
    }

    public function delete(): void {
        $id = validateInt($_POST['id'] ?? null, 1, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid unit id.');
        }

        $repo = new UnitRepository(Database::connect());
        $unit = $repo->fetchItemById($id);

        if ($unit === false) {
            jsonResponse(404, 'Unit does not exist.');
        }

        $programs = $repo->countPrograms($id);
        $users = $repo->countUsers($id);

        if ($programs > 0 || $users > 0) {
            $blockers = [];

            if ($programs > 0) {
                $blockers[] = $programs . ($programs === 1 ? ' program' : ' programs');
            }

            if ($users > 0) {
                $blockers[] = $users . ($users === 1 ? ' user' : ' users');
            }

            jsonResponse(409, 'This unit cannot be deleted because it still has ' . implode(' and ', $blockers) . ' assigned to it.');
        }

        $name = $unit['name'];
        $editorId = $this->currentUserId();
        $conn = Database::connect();

        try {
            $conn->beginTransaction();

            $repo = new UnitRepository($conn);
            $repo->deleteItem([
                'editor_id' => $editorId,
                'id' => $id
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'DELETE',
                'description' => "Deleted the unit named: $name",
                'entity_type' => 'Unit',
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

        jsonResponse(200, 'Unit successfully deleted.');
    }

    // Validates the text fields shared by create and update ($id is 0 when creating)
    /** @return array{name: string, dean: string, status: string} */
    private function readInput(int $id): array {
        $name = trim(validateString($_POST['name'] ?? '', 'Name'));
        $dean = trim(validateString($_POST['dean'] ?? '', 'Dean'));
        $status = $_POST['status'] ?? '';

        if ($name === '') {
            jsonResponse(422, 'Name cannot be empty.');
        }

        if (mb_strlen($name) > self::NAME_MAX_LENGTH) {
            jsonResponse(422, 'Name cannot exceed ' . self::NAME_MAX_LENGTH . ' characters.');
        }

        if ($dean === '') {
            jsonResponse(422, 'Dean cannot be empty.');
        }

        if (mb_strlen($dean) > self::DEAN_MAX_LENGTH) {
            jsonResponse(422, 'Dean cannot exceed ' . self::DEAN_MAX_LENGTH . ' characters.');
        }

        if (!in_array($status, self::STATUS_KEYS, true)) {
            jsonResponse(422, 'Invalid status selection.');
        }

        $repo = new UnitRepository(Database::connect());

        if ($repo->existsByName($name, $id)) {
            jsonResponse(409, 'A unit with this name already exists.');
        }

        return [
            'name' => $name,
            'dean' => $dean,
            'status' => $status
        ];
    }

    // Reads one uploaded image and its remove flag; the file is verified but not stored yet
    /** @return array{temp: string|false|null, remove: bool} */
    private function readImageInput(string $field, string $removeField, ImageManager $imageHandler): array {
        $remove = filter_var($_POST[$removeField] ?? 'false', FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if ($remove === null) {
            jsonResponse(422, 'Invalid remove image indicator.');
        }

        $file = $_FILES[$field] ?? null;
        $hasUpload = is_array($file) && ($file['error'] ?? null) !== UPLOAD_ERR_NO_FILE;

        if ($remove && $hasUpload) {
            jsonResponse(422, 'An image cannot be removed and replaced at the same time.');
        }

        return [
            'temp' => $hasUpload ? $imageHandler->verify($file) : null,
            'remove' => $remove
        ];
    }

    // Stores a newly uploaded image (create); returns the stored filename or null
    /** @param array{temp: string|false|null, remove: bool} $input */
    private function storeImage(array $input, ImageManager $imageHandler): ?string {
        if (is_string($input['temp'])) {
            return $imageHandler->store($input['temp']);
        }

        return null;
    }

    // Decides the final filename on update; returns [filename to save, newly stored filename]
    /** @param array{temp: string|false|null, remove: bool} $input
     *  @return array{0: ?string, 1: ?string} */
    private function resolveImage(array $input, ?string $current, ImageManager $imageHandler): array {
        if ($input['remove']) {
            return [null, null];
        }

        if (is_string($input['temp'])) {
            $stored = $imageHandler->store($input['temp']);

            return [$stored, $stored];
        }

        return [$current, null];
    }

    private function discardReplacedImage(ImageManager $imageHandler, ?string $old, ?string $current, string $action): void {
        if (is_string($old) && $old !== '' && $old !== $current) {
            $this->discardImage($imageHandler, $old, $action);
        }
    }

    // Removes an image file; a failure here is only logged because the database result already stands
    private function discardImage(ImageManager $imageHandler, ?string $filename, string $action): void {
        if ($filename === null || $filename === '') {
            return;
        }

        try {
            $imageHandler->delete($filename);
        } catch (Throwable $e) {
            logError($e, 'Failed to delete a unit image.', 'ERROR', [
                'action' => "save unit - $action",
                'endpoint' => 'units/save',
                'filename' => $filename
            ]);
        }
    }

    private function currentUserId(): int {
        session_start();

        $userId = (int) ($_SESSION['user_id'] ?? 0);

        session_write_close();

        return $userId;
    }
}
