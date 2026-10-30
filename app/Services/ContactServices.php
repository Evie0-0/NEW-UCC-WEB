<?php
declare(strict_types=1);

namespace Serv;

use Core\Database;
use Repo\ContactRepository;
use Repo\AuditTrailRepository;
use RuntimeException;
use Throwable;

final class ContactServices
{
    private function getUserId(): int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = validateInt(
            $_SESSION[UserServices::USER_ID_KEY] ?? null,
            1,
            null
        );

        if ($userId === false) {
            jsonResponse(403, 'Your session has expired. Please log in again.');
            exit;
        }

        return (int) $userId;
    }

    private function validateForm(): array
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $number = trim((string) ($_POST['phone_number'] ?? ''));
        $category = trim((string) ($_POST['category'] ?? ''));
        $location = trim((string) ($_POST['location'] ?? ''));
        $status = strtolower(trim((string) ($_POST['status'] ?? '')));

        if ($name === '' || mb_strlen($name) > 150) {
            jsonResponse(422, 'Contact name must contain 1 to 150 characters.');
            exit;
        }

        if (
            $email === '' ||
            mb_strlen($email) > 200 ||
            filter_var($email, FILTER_VALIDATE_EMAIL) === false
        ) {
            jsonResponse(422, 'Please enter a valid email address.');
            exit;
        }

        if (mb_strlen($number) > 30) {
            jsonResponse(422, 'Contact number must not exceed 30 characters.');
            exit;
        }

        if (!in_array($category, ['Campus', 'Colleges', 'Offices'], true)) {
            jsonResponse(422, 'Invalid contact category.');
            exit;
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            jsonResponse(422, 'Invalid contact status.');
            exit;
        }

        if ($category !== 'Campus') {
            $location = '';
        }

        if (mb_strlen($location) > 2000) {
            jsonResponse(422, 'Campus location must not exceed 2000 characters.');
            exit;
        }

        if (
            $location !== '' &&
            preg_match('/^https?:\/\//i', $location) &&
            (
                filter_var($location, FILTER_VALIDATE_URL) === false ||
                strtolower((string) parse_url($location, PHP_URL_SCHEME)) !== 'https'
            )
        ) {
            jsonResponse(422, 'Please enter a valid HTTPS map URL or campus address.');
            exit;
        }

        return [
            'name' => $name,
            'email' => $email,
            'phone_number' => $number,
            'category' => $category,
            'location' => $location,
            'status' => $status
        ];
    }

    public function save(): never
    {
        $id = validateInt($_POST['id'] ?? 0, 0, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid contact ID.');
            exit;
        }

        $id = (int) $id;
        $data = $this->validateForm();
        $userId = $this->getUserId();

        $db = Database::connect();
        $repository = new ContactRepository($db);
        $audit = new AuditTrailRepository($db);

        if ($id > 0 && !$repository->fetchItemById($id)) {
            jsonResponse(404, 'Contact not found.');
            exit;
        }

        try {
            $db->beginTransaction();

            if ($id === 0) {
                $data['author_id'] = $userId;
                $savedId = $repository->insertItem($data);

                if ($savedId <= 0) {
                    throw new RuntimeException('Failed to create contact.');
                }

                $audit->insertItem([
                    'action' => 'create',
                    'description' => 'Created Contact: ' . $data['name'],
                    'entity_type' => 'contacts',
                    'entity_id' => $savedId,
                    'user_id' => $userId
                ]);

                $message = 'Contact successfully created.';
                $statusCode = 201;
            } else {
                $data['id'] = $id;
                $data['editor_id'] = $userId;

                if (!$repository->updateItem($data)) {
                    throw new RuntimeException('Failed to update contact.');
                }

                $audit->insertItem([
                    'action' => 'update',
                    'description' => 'Updated Contact: ' . $data['name'],
                    'entity_type' => 'contacts',
                    'entity_id' => $id,
                    'user_id' => $userId
                ]);

                $savedId = $id;
                $message = 'Contact successfully updated.';
                $statusCode = 200;
            }

            $db->commit();

            jsonResponse($statusCode, $message, ['id' => $savedId]);
            exit;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public function delete(): never
    {
        $id = validateInt($_POST['id'] ?? null, 1, null);

        if ($id === false) {
            jsonResponse(422, 'Invalid contact ID.');
            exit;
        }

        $id = (int) $id;
        $userId = $this->getUserId();

        $db = Database::connect();
        $repository = new ContactRepository($db);
        $audit = new AuditTrailRepository($db);

        $existing = $repository->fetchItemById($id);

        if (!$existing) {
            jsonResponse(404, 'Contact not found.');
            exit;
        }

        try {
            $db->beginTransaction();

            if (!$repository->deleteItem($id, $userId)) {
                $db->rollBack();
                jsonResponse(409, 'Contact could not be deleted.');
                exit;
            }

            $audit->insertItem([
                'action' => 'delete',
                'description' => 'Soft-deleted Contact: ' . $existing['name'],
                'entity_type' => 'contacts',
                'entity_id' => $id,
                'user_id' => $userId
            ]);

            $db->commit();

            jsonResponse(200, 'Contact successfully deleted.');
            exit;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}