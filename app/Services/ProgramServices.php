<?php
declare(strict_types=1);

namespace Serv;

use Core\Database;
use Repo\AuditTrailRepository;
use Repo\ProgramRepository;
use Repo\UnitRepository;
use Throwable;

final class ProgramServices {
    private const STATUS_KEYS = ['active', 'inactive'];
    private const FILTER_KEYS = ['all', 'active', 'inactive'];
    private const SORT_KEYS = ['name-asc', 'name-desc', 'newest', 'oldest'];
    private const PER_PAGE = 10;

    // Column limit (programs.name)
    private const NAME_MAX = 150;
    // The form has no limit on mission / vision; this only keeps requests sensible
    private const TEXT_MAX = 5000;

    private const ENTITY_TYPE = 'Program';

    // The programs page labels the inactive state "Hidden"
    private function normalizeStatus(string $value): string {
        $value = strtolower(trim($value));

        return $value === 'hidden' ? 'inactive' : $value;
    }

    public function save(): void {
        $id = validateInt($_POST['id'] ?? null, 0, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid program id.');
        }

        if ($id === 0) {
            $this->create();
        } else {
            $this->update($id);
        }
    }

    /** @return array{name: string, mission: string, vision: string, status: string, unit_id: int} */
    private function validateFields(): array {
        $name = trim(validateString($_POST['name'] ?? '', 'Name'));
        $mission = trim(validateString($_POST['mission'] ?? '', 'Mission'));
        $vision = trim(validateString($_POST['vision'] ?? '', 'Vision'));
        $status = $this->normalizeStatus(validateString($_POST['status'] ?? '', 'Status'));

        if ($name === '') {
            jsonResponse(422, 'Name cannot be empty.');
        }

        if (mb_strlen($name) > self::NAME_MAX) {
            jsonResponse(422, 'Name cannot be longer than ' . self::NAME_MAX . ' characters.');
        }

        if ($mission === '') {
            jsonResponse(422, 'Mission cannot be empty.');
        }

        if (mb_strlen($mission) > self::TEXT_MAX) {
            jsonResponse(422, 'Mission cannot be longer than ' . self::TEXT_MAX . ' characters.');
        }

        if ($vision === '') {
            jsonResponse(422, 'Vision cannot be empty.');
        }

        if (mb_strlen($vision) > self::TEXT_MAX) {
            jsonResponse(422, 'Vision cannot be longer than ' . self::TEXT_MAX . ' characters.');
        }

        if (!in_array($status, self::STATUS_KEYS, true)) {
            jsonResponse(422, 'Invalid status selection.');
        }

        return [
            'name' => $name,
            'mission' => $mission,
            'vision' => $vision,
            'status' => $status,
            'unit_id' => $this->validateUnit()
        ];
    }

    // programs.unit_id must be the id of an existing, non-deleted unit (programs.unit_id -> units.id)
    private function validateUnit(): int {
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

        return $unitId;
    }

    private function create(): void {
        $fields = $this->validateFields();

        session_start();

        $authorId = (int) $_SESSION['user_id'];

        session_write_close();

        $conn = Database::connect();

        try {
            $conn->beginTransaction();

            $repo = new ProgramRepository($conn);
            $insertedId = $repo->insertItem([
                ...$fields,
                'author_id' => $authorId
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'CREATE',
                'description' => 'Created a new program named: ' . $fields['name'],
                'entity_type' => self::ENTITY_TYPE,
                'entity_id' => (int) $insertedId,
                'user_id' => $authorId
            ]);

            $conn->commit();
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            throw $e;
        }

        jsonResponse(201, 'Program successfully created.');
    }

    private function update(int $id): void {
        $repo = new ProgramRepository(Database::connect());
        $fetchedItem = $repo->fetchItemById($id);

        if ($fetchedItem === false) {
            jsonResponse(404, 'Program does not exist.');
        }

        $fields = $this->validateFields();

        session_start();

        $editorId = (int) $_SESSION['user_id'];

        session_write_close();

        $conn = Database::connect();

        try {
            $conn->beginTransaction();

            $repo = new ProgramRepository($conn);
            $repo->updateItem([
                ...$fields,
                'editor_id' => $editorId,
                'id' => $id
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'UPDATE',
                'description' => 'Updated the program named: ' . $fields['name'],
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

        jsonResponse(200, 'Program successfully updated.');
    }

    public function filter(): void {
        $search = $_GET['search'] ?? '';
        $filter = $_GET['filter'] ?? 'all';
        $sort = $_GET['sort'] ?? 'name-asc';
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
            'filter' => $filter,
            'sort' => $sort,
            'unit_id' => $unitId
        ];

        $repo = new ProgramRepository(Database::connect());
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

        jsonResponse(200, 'Successfully filtered program items.', [
            'programs' => $items,
            'current_page' => $page,
            'total_programs' => $totalItems,
            'total_pages' => $totalPages
        ]);
    }

    public function item(): void {
        $id = validateInt($_GET['id'] ?? null, 1, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid program id.');
        }

        $repo = new ProgramRepository(Database::connect());
        $item = $repo->fetchItemById($id);

        if ($item === false) {
            jsonResponse(404, 'Program does not exist.');
        }

        jsonResponse(200, 'Program successfully fetched.', [
            'program' => $item
        ]);
    }

    public function delete(): void {
        $id = validateInt($_POST['id'] ?? null, 1, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid program id.');
        }

        $repo = new ProgramRepository(Database::connect());
        $item = $repo->fetchItemById($id);

        if ($item === false) {
            jsonResponse(404, 'Program does not exist.');
        }

        $name = $item['name'];

        session_start();

        $editorId = (int) $_SESSION['user_id'];

        session_write_close();

        $conn = Database::connect();

        try {
            $conn->beginTransaction();

            $repo = new ProgramRepository($conn);
            $repo->deleteItem([
                'editor_id' => $editorId,
                'id' => $id
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'DELETE',
                'description' => "Deleted the program named: $name",
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

        jsonResponse(200, 'Program successfully deleted.');
    }
}
