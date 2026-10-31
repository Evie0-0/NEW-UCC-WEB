<?php
declare(strict_types=1);

namespace Serv;

use Core\Database;
use Core\ImageManager;
use DateTimeImmutable;
use DateTimeZone;
use Repo\AuditTrailRepository;
use Repo\CalendarRepository;
use Throwable;

final class CalendarServices {
    private const STATUS_KEYS = ['draft', 'published'];
    private const FILTER_KEYS = ['all', 'draft', 'published', 'pending'];
    private const SORT_KEYS = ['create-desc', 'create-asc', 'publish-desc', 'publish-asc', 'start-asc', 'start-desc', 'title-asc', 'title-desc'];
    private const TITLE_MAX_LENGTH = 150;
    private const PER_PAGE = 10;

    public function save(): void {
        $id = validateInt($_POST['id'] ?? null, 0, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid event id.');
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

            $repo = new CalendarRepository($conn);
            $insertedId = $repo->insertItem([
                'title' => $input['title'],
                'description' => $input['description'],
                'featured_image' => $featuredImageFile,
                'status' => $input['status'],
                'author_id' => $authorId,
                'published_at' => $input['publish_date'],
                'starts_at' => $input['start_date'],
                'ends_at' => $input['end_date']
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'CREATE',
                'description' => "Created a new calendar event titled: {$input['title']}",
                'entity_type' => 'Calendar Event',
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

        jsonResponse(201, 'Event successfully created.');
    }

    private function update(int $id): void {
        $repo = new CalendarRepository(Database::connect());
        $fetchedEvent = $repo->fetchItemById($id);

        if ($fetchedEvent === false) {
            jsonResponse(404, 'Event does not exist.');
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

        $oldFeaturedImage = $fetchedEvent['featured_image'] ?? null;
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

            $repo = new CalendarRepository($conn);
            $repo->updateItem([
                'title' => $input['title'],
                'description' => $input['description'],
                'featured_image' => $featuredImageFile,
                'status' => $input['status'],
                'editor_id' => $editorId,
                'published_at' => $input['publish_date'],
                'starts_at' => $input['start_date'],
                'ends_at' => $input['end_date'],
                'id' => $id
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'UPDATE',
                'description' => "Updated the calendar event titled: {$input['title']}",
                'entity_type' => 'Calendar Event',
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

        jsonResponse(200, 'Event successfully updated.');
    }

    public function filter(): void {
        $search = $_GET['search'] ?? '';
        $filter = $_GET['filter'] ?? 'all';
        $date = $_GET['date'] ?? '';
        $sort = $_GET['sort'] ?? 'create-desc';
        $page = validateInt($_GET['page'] ?? 1, 1, null);

        if (!is_string($search)) {
            jsonResponse(422, 'Search must be a valid string.');
        }

        $search = trim($search);

        if (!in_array($filter, self::FILTER_KEYS, true)) {
            jsonResponse(422, 'Invalid filter selection.');
        }

        if ($date === '') {
            $date = null;
        } else {
            $date = validateDate($date);

            if ($date === false) {
                jsonResponse(422, 'Date must be a valid date (YYYY-MM-DD).');
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
            'date' => $date,
            'sort' => $sort
        ];

        $repo = new CalendarRepository(Database::connect());
        $totalEvents = $repo->countFiltered($data);
        $totalPages = (int) ceil($totalEvents / self::PER_PAGE);

        if ($page > max(1, $totalPages)) {
            jsonResponse(422, 'Requested page does not exist.');
        }

        $events = $repo->filter([
            ...$data,
            'limit' => self::PER_PAGE,
            'offset' => ($page - 1) * self::PER_PAGE
        ]);

        jsonResponse(200, 'Successfully filtered calendar items.', [
            'events' => $events,
            'current_page' => $page,
            'total_events' => $totalEvents,
            'total_pages' => $totalPages
        ]);
    }

    public function item(): void {
        $id = validateInt($_GET['id'] ?? null, 1, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid event id.');
        }

        $repo = new CalendarRepository(Database::connect());
        $event = $repo->fetchItemById($id);

        if ($event === false) {
            jsonResponse(404, 'Event does not exist.');
        }

        jsonResponse(200, 'Event successfully fetched.', [
            'event' => $event
        ]);
    }

    public function delete(): void {
        $id = validateInt($_POST['id'] ?? null, 1, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid event id.');
        }

        $repo = new CalendarRepository(Database::connect());
        $event = $repo->fetchItemById($id);

        if ($event === false) {
            jsonResponse(404, 'Event does not exist.');
        }

        $title = $event['title'];
        $editorId = $this->currentUserId();
        $conn = Database::connect();

        try {
            $conn->beginTransaction();

            $repo = new CalendarRepository($conn);
            $repo->deleteItem([
                'editor_id' => $editorId,
                'id' => $id
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'DELETE',
                'description' => "Deleted the calendar event titled: $title",
                'entity_type' => 'Calendar Event',
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

        jsonResponse(200, 'Event successfully deleted.');
    }

    // Validates the fields shared by create and update
    /** @return array{title: string, description: string, status: string, publish_date: string, start_date: string, end_date: string} */
    private function readInput(): array {
        $title = trim(validateString($_POST['title'] ?? '', 'Title'));
        $description = trim(validateString($_POST['description'] ?? '', 'Description'));
        $status = $_POST['status'] ?? '';
        $rawPublishDate = $_POST['publish_date'] ?? '';

        if ($title === '') {
            jsonResponse(422, 'Title cannot be empty.');
        }

        if (mb_strlen($title) > self::TITLE_MAX_LENGTH) {
            jsonResponse(422, 'Title cannot exceed ' . self::TITLE_MAX_LENGTH . ' characters.');
        }

        if ($description === '') {
            jsonResponse(422, 'Description cannot be empty.');
        }

        if (!in_array($status, self::STATUS_KEYS, true)) {
            jsonResponse(422, 'Invalid status selection.');
        }

        // A missing publish date means the event is published today
        if ($rawPublishDate === '') {
            $publishDate = (new DateTimeImmutable('today', new DateTimeZone('Asia/Manila')))->format('Y-m-d');
        } else {
            $publishDate = validateDate($rawPublishDate);
        }

        $startDate = validateDate($_POST['start_date'] ?? null);
        $endDate = validateDate($_POST['end_date'] ?? null);

        if ($publishDate === false) {
            jsonResponse(422, 'Publish date must be a valid date (YYYY-MM-DD).');
        }

        if ($startDate === false) {
            jsonResponse(422, 'Start date must be a valid date (YYYY-MM-DD).');
        }

        if ($endDate === false) {
            jsonResponse(422, 'End date must be a valid date (YYYY-MM-DD).');
        }

        if ($endDate < $startDate) {
            jsonResponse(422, 'End date cannot be earlier than the start date.');
        }

        return [
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'publish_date' => $publishDate,
            'start_date' => $startDate,
            'end_date' => $endDate
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
            logError($e, 'Failed to delete a calendar featured image.', 'ERROR', [
                'action' => "save calendar event - $action",
                'endpoint' => 'calendar/save',
                'filename' => $filename
            ]);
        }
    }
}
