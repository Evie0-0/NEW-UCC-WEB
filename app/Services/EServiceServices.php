<?php
declare(strict_types=1);

namespace Serv;

use Core\Database;
use Core\ImageManager;
use Repo\EServiceRepository;
use Repo\AuditTrailRepository;
use Throwable;

final class EServiceServices
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
            jsonResponse(
                403,
                'Your session has expired. Please log in again.'
            );
        }

        return (int) $userId;
    }

    //VALIDATIONS
    private function validateForm(): array
    {
        $name = trim(
            validateString(
                $_POST['name'] ?? null,
                'Service name'
            )
        );

        $description = trim(
            validateString(
                $_POST['description'] ?? null,
                'Description'
            )
        );

        $url = trim(
            validateString(
                $_POST['url'] ?? '',
                'Website URL'
            )
        );

        $status = strtolower(
            trim(
                validateString(
                    $_POST['status'] ?? null,
                    'Status'
                )
            )
        );

        if ($name === '' || mb_strlen($name) > 150) {
            jsonResponse(
                422,
                'Service name must contain 1 to 150 characters.'
            );
        }

        if ($description === '' || mb_strlen($description) > 500) {
            jsonResponse(
                422,
                'Description must contain 1 to 500 characters.'
            );
        }

        if ($url !== '') {
            $scheme = strtolower(
                (string) parse_url($url, PHP_URL_SCHEME)
            );

            if (
                strlen($url) > 2048 ||
                filter_var($url, FILTER_VALIDATE_URL) === false ||
                !in_array($scheme, ['http', 'https'], true)
            ) {
                jsonResponse(
                    422,
                    'Please enter a valid HTTP or HTTPS website URL.'
                );
            }
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            jsonResponse(
                422,
                'Invalid E-Service status.'
            );
        }

        return [
            'name' => $name,
            'description' => $description,
            'url' => $url,
            'status' => $status
        ];
    }

    private function getUploadedImage(ImageManager $manager): ?string
    {
        $file = $_FILES['featured_image'] ?? null;
        if ($file === null) {
            return null;
        }

        if (!is_array($file)) {
            jsonResponse(
                422,
                'Invalid image upload.'
            );
        }
        if (
            ($file['error'] ?? UPLOAD_ERR_NO_FILE)
            === UPLOAD_ERR_NO_FILE
        ) {
            return null;
        }

        $temporaryFile = $manager->verify($file);
        if ($temporaryFile === false) {
            jsonResponse(
                422,
                'Unable to upload the image. Please choose a valid image.'
            );
        }
        return $manager->store($temporaryFile);
    }

    public function save(): never
    {
        // ID = 0 means creating a new E-Service
        // ID > 0 means updating an existing E-Servic
        $id = validateInt(
            $_POST['id'] ?? 0,
            0,
            null
        );

        if ($id === false) {
            jsonResponse(
                422,
                'Invalid E-Service ID.'
            );
        }

        $id = (int) $id;
        $data = $this->validateForm();
        $userId = $this->getUserId();
        $db = Database::connect();
        $repository = new EServiceRepository($db);
        $audit = new AuditTrailRepository($db);
        $imageManager = new ImageManager();

        $existing = null;

        // Retrieve existing record when editing
        if ($id > 0) {
            $existing = $repository->fetchItemById($id);

            if ($existing === false || $existing === null) {
                jsonResponse(
                    404,
                    'E-Service not found.'
                );
            }
        }

        $oldImage = $existing['featured_image'] ?? null;

        $removeImage = filter_var(
            $_POST['remove_featured_image'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        $newImage = null;

        try {
            $newImage = $this->getUploadedImage($imageManager);

            if ($newImage !== null) {
                // Replace image with newly uploaded image
                $data['featured_image'] = $newImage;

            } elseif ($removeImage) {
                // Remove existing image
                $data['featured_image'] = null;

            } else {
                // Keep existing image
                $data['featured_image'] = $oldImage;
            }

            // Start database transaction
            $db->beginTransaction();

            if ($id === 0) {

                $data['author_id'] = $userId;

                $savedId = (int) $repository->insertItem($data);

                if ($savedId <= 0) {
                    throw new \RuntimeException(
                        'Failed to create E-Service.'
                    );
                }

                // Record creation in audit trail
                $audit->insertItem([
                    'action' => 'create',
                    'description' => 'Created E-Service: ' . $data['name'],
                    'entity_type' => 'eservices',
                    'entity_id' => $savedId,
                    'user_id' => $userId
                ]);

                $message = 'E-Service successfully created.';
                $statusCode = 201;

            } else {

                $data['id'] = $id;
                $data['editor_id'] = $userId;

                $repository->updateItem($data);

                // Record update in audit trail
                $audit->insertItem([
                    'action' => 'update',
                    'description' => 'Updated E-Service: ' . $data['name'],
                    'entity_type' => 'eservices',
                    'entity_id' => $id,
                    'user_id' => $userId
                ]);

                $savedId = $id;

                $message = 'E-Service successfully updated.';
                $statusCode = 200;
            }

            $db->commit();

            //IMAGE CLEANU
            if (
                $oldImage !== null &&
                $oldImage !== '' &&
                $oldImage !== $data['featured_image']
            ) {
                try {
                    $imageManager->delete($oldImage);

                } catch (Throwable $e) {
                    logError(
                        $e,
                        'Unable to remove previous E-Service image.'
                    );
                }
            }

            jsonResponse(
                $statusCode,
                $message,
                [
                    'id' => $savedId
                ]
            );

        } catch (Throwable $e) {

            // Undo database changes if something fails
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            /*Remove a newly uploaded image if
             * saving to the database failed*/
            if ($newImage !== null) {
                try {
                    $imageManager->delete($newImage);

                } catch (Throwable $cleanupError) {
                    logError(
                        $cleanupError,
                        'Unable to clean up failed E-Service image upload.'
                    );
                }
            }

            // Pass the error to save.php
            throw $e;
        }
    }

    //Soft-delete an E-Service
    public function delete(): never
    {
        $id = validateInt(
            $_POST['id'] ?? null,
            1,
            null
        );

        if ($id === false) {
            jsonResponse(
                422,
                'Invalid E-Service ID.'
            );
        }

        $id = (int) $id;

        $userId = $this->getUserId();

        $db = Database::connect();

        $repository = new EServiceRepository($db);
        $audit = new AuditTrailRepository($db);

        $existing = $repository->fetchItemById($id);

        if ($existing === false || $existing === null) {
            jsonResponse(
                404,
                'E-Service not found.'
            );
        }

        try {
            // Begin transaction
            $db->beginTransaction();

            // Soft-delete the E-Service
            $deleted = $repository->deleteItem(
                $id,
                $userId
            );

            if (!$deleted) {
                $db->rollBack();

                jsonResponse(
                    409,
                    'E-Service could not be deleted.'
                );
            }

            // Record deletion in audit trail
            $audit->insertItem([
                'action' => 'delete',
                'description' => 'Soft-deleted E-Service: ' . $existing['name'],
                'entity_type' => 'eservices',
                'entity_id' => $id,
                'user_id' => $userId
            ]);

            // Commit database changes
            $db->commit();

            jsonResponse(
                200,
                'E-Service successfully deleted.'
            );

        } catch (Throwable $e) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }
}