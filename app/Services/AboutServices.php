<?php
declare(strict_types=1);

namespace Serv;

use Core\Database;
use Core\ImageManager;
use Repo\AboutRepository;
use Repo\AuditTrailRepository;
use Repo\CategoryRepository;
use Throwable;

final class AboutServices {
    private const STATUS_KEYS = ['active', 'inactive'];
    private const FILTER_KEYS = ['all', 'active', 'inactive'];
    private const SORT_KEYS = ['create-desc', 'create-asc', 'name-asc', 'name-desc'];
    private const CATEGORY_CLASS = 'about';
    private const NAME_MAX_LENGTH = 150;
    private const PER_PAGE = 10;

    public function save(): void {
        $id = validateInt($_POST['id'] ?? null, 0, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid about post id.');
        }

        if ($id === 0) {
            $this->create();
        } else {
            $this->update($id);
        }
    }

    private function create(): void {
        $input = $this->readInput();
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

            $repo = new AboutRepository($conn);
            $insertedId = $repo->insertItem([
                'name' => $input['name'],
                'description' => $input['description'],
                'featured_image' => $featuredImageFile,
                'category_id' => $input['category'],
                'status' => $input['status'],
                'author_id' => $authorId
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'CREATE',
                'description' => "Created a new About UCC post named: {$input['name']}",
                'entity_type' => "About UCC - {$input['category_name']}",
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

        jsonResponse(201, 'About post successfully created.');
    }

    private function update(int $id): void {
        $repo = new AboutRepository(Database::connect());
        $fetchedAbout = $repo->fetchItemById($id);

        if ($fetchedAbout === false) {
            jsonResponse(404, 'About post does not exist.');
        }

        $input = $this->readInput();
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

        $oldFeaturedImage = $fetchedAbout['featured_image'] ?? null;
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

            $repo = new AboutRepository($conn);
            $repo->updateItem([
                'name' => $input['name'],
                'description' => $input['description'],
                'featured_image' => $featuredImageFile,
                'category_id' => $input['category'],
                'status' => $input['status'],
                'editor_id' => $editorId,
                'id' => $id
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'UPDATE',
                'description' => "Updated the About UCC post named: {$input['name']}",
                'entity_type' => "About UCC - {$input['category_name']}",
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

        jsonResponse(200, 'About post successfully updated.');
    }

    public function filter(): void {
        $search = $_GET['search'] ?? '';
        $filter = $_GET['filter'] ?? 'all';
        $category = $_GET['category'] ?? 'all';
        $sort = $_GET['sort'] ?? 'create-asc';
        $page = validateInt($_GET['page'] ?? 1, 1, null);

        if (!is_string($search)) {
            jsonResponse(422, 'Search must be a valid string.');
        }

        $search = trim($search);

        if (!in_array($filter, self::FILTER_KEYS, true)) {
            jsonResponse(422, 'Invalid filter selection.');
        }

        if ($category === 'all') {
            $category = null;
        } else {
            $category = validateInt($category, 1, null);

            if ($category === false) {
                jsonResponse(422, 'Invalid category selection.');
            }
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
            'category' => $category,
            'sort' => $sort
        ];

        $repo = new AboutRepository(Database::connect());
        $totalAbout = $repo->countFiltered($data);
        $totalPages = (int) ceil($totalAbout / self::PER_PAGE);

        if ($page > max(1, $totalPages)) {
            jsonResponse(422, 'Requested page does not exist.');
        }

        $about = $repo->filter([
            ...$data,
            'limit' => self::PER_PAGE,
            'offset' => ($page - 1) * self::PER_PAGE
        ]);

        jsonResponse(200, 'Successfully filtered about items.', [
            'about' => $about,
            'current_page' => $page,
            'total_about' => $totalAbout,
            'total_pages' => $totalPages
        ]);
    }

    public function item(): void {
        $id = validateInt($_GET['id'] ?? null, 1, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid about post id.');
        }

        $repo = new AboutRepository(Database::connect());
        $about = $repo->fetchItemById($id);

        if ($about === false) {
            jsonResponse(404, 'About post does not exist.');
        }

        jsonResponse(200, 'About post successfully fetched.', [
            'about' => $about
        ]);
    }

    public function delete(): void {
        $id = validateInt($_POST['id'] ?? null, 1, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid about post id.');
        }

        $repo = new AboutRepository(Database::connect());
        $about = $repo->fetchItemById($id);

        if ($about === false) {
            jsonResponse(404, 'About post does not exist.');
        }

        $name = $about['name'];
        $categoryName = $about['category_name'] ?? 'Uncategorized';
        $editorId = $this->currentUserId();
        $conn = Database::connect();

        try {
            $conn->beginTransaction();

            $repo = new AboutRepository($conn);
            $repo->deleteItem([
                'editor_id' => $editorId,
                'id' => $id
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'DELETE',
                'description' => "Deleted the About UCC post named: $name",
                'entity_type' => "About UCC - $categoryName",
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

        jsonResponse(200, 'About post successfully deleted.');
    }

    // Validates the text fields shared by create and update
    /** @return array{name: string, description: string, category: int, category_name: string, status: string} */
    private function readInput(): array {
        $name = trim(validateString($_POST['name'] ?? '', 'Name'));
        $description = trim(validateString($_POST['description'] ?? '', 'Description'));
        $category = validateInt($_POST['category'] ?? null, 1, null);
        $status = $_POST['status'] ?? '';

        if ($name === '') {
            jsonResponse(422, 'Name cannot be empty.');
        }

        if (mb_strlen($name) > self::NAME_MAX_LENGTH) {
            jsonResponse(422, 'Name cannot exceed ' . self::NAME_MAX_LENGTH . ' characters.');
        }

        if ($description === '') {
            jsonResponse(422, 'Description cannot be empty.');
        }

        if ($category === false) {
            jsonResponse(422, 'Invalid category selection.');
        }

        $repo = new CategoryRepository(Database::connect());
        $fetchedCategories = $repo->fetchItemsByClass(self::CATEGORY_CLASS);
        $categoryName = array_column($fetchedCategories, 'name', 'id')[$category] ?? null;

        if ($categoryName === null) {
            jsonResponse(422, 'Invalid category selection.');
        }

        if (!in_array($status, self::STATUS_KEYS, true)) {
            jsonResponse(422, 'Invalid status selection.');
        }

        return [
            'name' => $name,
            'description' => $description,
            'category' => $category,
            'category_name' => $categoryName,
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
            logError($e, 'Failed to delete an about featured image.', 'ERROR', [
                'action' => "save about - $action",
                'endpoint' => 'about/save',
                'filename' => $filename
            ]);
        }
    }
}
