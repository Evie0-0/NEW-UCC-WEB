<?php
declare(strict_types=1);

namespace Serv;

use Core\Database;
use Repo\AuditTrailRepository;
use Repo\ContactRepository;
use Throwable;

final class ContactServices {
    private const STATUS_KEYS = ['active', 'inactive'];
    private const FILTER_KEYS = ['all', 'active', 'inactive'];
    private const SORT_KEYS = ['created-desc', 'created-asc', 'name-desc', 'name-asc'];
    private const PER_PAGE = 10;

    // Column limits (contacts table)
    private const NAME_MAX = 150;
    private const EMAIL_MAX = 200;
    private const PHONE_MAX = 30;

    private const ENTITY_TYPE = 'Contact';

    // The contacts page labels the inactive state "Hidden"
    private function normalizeStatus(string $value): string {
        $value = strtolower(trim($value));

        return $value === 'hidden' ? 'inactive' : $value;
    }

    public function save(): void {
        $id = validateInt($_POST['id'] ?? null, 0, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid contact id.');
        }

        if ($id === 0) {
            $this->create();
        } else {
            $this->update($id);
        }
    }

    /** @return array{name: string, email: string, phone_number: string, status: string} */
    private function validateFields(): array {
        $name = trim(validateString($_POST['name'] ?? '', 'Name'));
        $email = trim(validateString($_POST['email'] ?? '', 'Email'));
        $phone = trim(validateString($_POST['phone_number'] ?? '', 'Phone number'));
        $status = $this->normalizeStatus(validateString($_POST['status'] ?? '', 'Status'));

        if ($name === '') {
            jsonResponse(422, 'Name cannot be empty.');
        }

        if (mb_strlen($name) > self::NAME_MAX) {
            jsonResponse(422, 'Name cannot be longer than ' . self::NAME_MAX . ' characters.');
        }

        if ($email === '') {
            jsonResponse(422, 'Email cannot be empty.');
        }

        if (strlen($email) > self::EMAIL_MAX) {
            jsonResponse(422, 'Email cannot be longer than ' . self::EMAIL_MAX . ' characters.');
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            jsonResponse(422, 'Email must be a valid email address.');
        }

        if ($phone === '') {
            jsonResponse(422, 'Phone number cannot be empty.');
        }

        if (strlen($phone) > self::PHONE_MAX) {
            jsonResponse(422, 'Phone number cannot be longer than ' . self::PHONE_MAX . ' characters.');
        }

        // Digits plus the usual separators, e.g. (02) 5310 1234 or +63 2 5310 1234 loc. 12
        if (!preg_match('/^[0-9+().\-\s\/#,]+$/', $phone) || !preg_match('/\d/', $phone)) {
            jsonResponse(422, 'Phone number may only contain digits, spaces and + ( ) - . / # ,');
        }

        if (!in_array($status, self::STATUS_KEYS, true)) {
            jsonResponse(422, 'Invalid status selection.');
        }

        return [
            'name' => $name,
            'email' => $email,
            'phone_number' => $phone,
            'status' => $status
        ];
    }

    private function create(): void {
        $fields = $this->validateFields();

        session_start();

        $authorId = (int) $_SESSION['user_id'];

        session_write_close();

        $conn = Database::connect();

        try {
            $conn->beginTransaction();

            $repo = new ContactRepository($conn);
            $insertedId = $repo->insertItem([
                ...$fields,
                'author_id' => $authorId
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'CREATE',
                'description' => 'Created a new contact named: ' . $fields['name'],
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

        jsonResponse(201, 'Contact successfully created.');
    }

    private function update(int $id): void {
        $repo = new ContactRepository(Database::connect());
        $fetchedItem = $repo->fetchItemById($id);

        if ($fetchedItem === false) {
            jsonResponse(404, 'Contact does not exist.');
        }

        $fields = $this->validateFields();

        session_start();

        $editorId = (int) $_SESSION['user_id'];

        session_write_close();

        $conn = Database::connect();

        try {
            $conn->beginTransaction();

            $repo = new ContactRepository($conn);
            $repo->updateItem([
                ...$fields,
                'editor_id' => $editorId,
                'id' => $id
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'UPDATE',
                'description' => 'Updated the contact named: ' . $fields['name'],
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

        jsonResponse(200, 'Contact successfully updated.');
    }

    public function filter(): void {
        $search = $_GET['search'] ?? '';
        $filter = $_GET['filter'] ?? 'all';
        $sort = $_GET['sort'] ?? 'created-asc';
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

        $repo = new ContactRepository(Database::connect());
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

        jsonResponse(200, 'Successfully filtered contact items.', [
            'contacts' => $items,
            'current_page' => $page,
            'total_contacts' => $totalItems,
            'total_pages' => $totalPages
        ]);
    }

    public function item(): void {
        $id = validateInt($_GET['id'] ?? null, 1, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid contact id.');
        }

        $repo = new ContactRepository(Database::connect());
        $item = $repo->fetchItemById($id);

        if ($item === false) {
            jsonResponse(404, 'Contact does not exist.');
        }

        jsonResponse(200, 'Contact successfully fetched.', [
            'contact' => $item
        ]);
    }

    public function delete(): void {
        $id = validateInt($_POST['id'] ?? null, 1, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid contact id.');
        }

        $repo = new ContactRepository(Database::connect());
        $item = $repo->fetchItemById($id);

        if ($item === false) {
            jsonResponse(404, 'Contact does not exist.');
        }

        $name = $item['name'];

        session_start();

        $editorId = (int) $_SESSION['user_id'];

        session_write_close();

        $conn = Database::connect();

        try {
            $conn->beginTransaction();

            $repo = new ContactRepository($conn);
            $repo->deleteItem([
                'editor_id' => $editorId,
                'id' => $id
            ]);

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem([
                'action' => 'DELETE',
                'description' => "Deleted the contact named: $name",
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

        jsonResponse(200, 'Contact successfully deleted.');
    }
}
