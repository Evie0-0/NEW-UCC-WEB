<?php
declare(strict_types=1);
namespace Serv;
use Core\Database;
use Core\ImageManager;
use Repo\EServiceRepository;
use Repo\AuditTrailRepository;
use RuntimeException;
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
			jsonResponse(403, 'Your session has expired. Please log in again.');
			exit;
		}
		return (int) $userId;
	}
	private function validateForm(): array
	{
		$name = trim(validateString($_POST['name'] ?? null, 'Service name'));
		$description = trim(validateString($_POST['description'] ?? null, 'Description'));
		$url = trim(validateString($_POST['url'] ?? '', 'Website URL'));
		$status = strtolower(trim(validateString($_POST['status'] ?? null, 'Status')));
		if ($name === '' || mb_strlen($name) > 150) {
			jsonResponse(422, 'Service name must contain 1 to 150 characters.');
			exit;
		}
		if ($description === '' || mb_strlen($description) > 500) {
			jsonResponse(422, 'Description must contain 1 to 500 characters.');
			exit;
		}
		if ($url !== '') {
			$scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
			if (
				strlen($url) > 2048 ||
				filter_var($url, FILTER_VALIDATE_URL) === false ||
				!in_array($scheme, ['http', 'https'], true)
			) {
				jsonResponse(422, 'Please enter a valid HTTP or HTTPS website URL.');
				exit;
			}
		}
		if (!in_array($status, ['active', 'inactive'], true)) {
			jsonResponse(422, 'Invalid E-Service status.');
			exit;
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
			jsonResponse(422, 'Invalid image upload.');
			exit;
		}
		$error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
		if ($error === UPLOAD_ERR_NO_FILE) {
			return null;
		}
		if ($error !== UPLOAD_ERR_OK) {
			$messages = [
				UPLOAD_ERR_INI_SIZE => 'The image exceeds the server upload limit.',
				UPLOAD_ERR_FORM_SIZE => 'The image exceeds the form upload limit.',
				UPLOAD_ERR_PARTIAL => 'The image was only partially uploaded.',
				UPLOAD_ERR_NO_TMP_DIR => 'The server upload temporary folder is missing.',
				UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded image.',
				UPLOAD_ERR_EXTENSION => 'A PHP extension blocked the image upload.'
			];
			jsonResponse(422, $messages[$error] ?? 'Image upload failed.');
			exit;
		}
		if (!isset($file['tmp_name']) || !is_string($file['tmp_name'])) {
			jsonResponse(422, 'Invalid uploaded image.');
			exit;
		}
		if (!is_uploaded_file($file['tmp_name'])) {
			jsonResponse(422, 'The uploaded image could not be verified.');
			exit;
		}
		$size = (int) ($file['size'] ?? 0);
		if ($size <= 0 || $size > 10 * 1024 * 1024) {
			jsonResponse(422, 'The image must not exceed 10 MB.');
			exit;
		}
		$mimeType = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
		if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
			jsonResponse(422, 'Only JPG, PNG, and WebP images are allowed.');
			exit;
		}
		$temporaryFile = $manager->verify($file);
		if ($temporaryFile === false) {
			jsonResponse(422, 'Unable to upload the image. Please choose a valid image.');
			exit;
		}
		$storedImage = $manager->store($temporaryFile);
		if (!is_string($storedImage) || $storedImage === '') {
			throw new RuntimeException('The image could not be stored.');
		}
		return $storedImage;
	}
	public function save(): never
	{
		$id = validateInt($_POST['id'] ?? 0, 0, null);
		if ($id === false) {
			jsonResponse(422, 'Invalid E-Service ID.');
			exit;
		}
		$id = (int) $id;
		$data = $this->validateForm();
		$userId = $this->getUserId();
		$db = Database::connect();
		$repository = new EServiceRepository($db);
		$audit = new AuditTrailRepository($db);
		$imageManager = new ImageManager();
		$existing = null;
		if ($id > 0) {
			$existing = $repository->fetchItemById($id);
			if ($existing === false || $existing === null) {
				jsonResponse(404, 'E-Service not found.');
				exit;
			}
		}
		$oldImage = $existing['featured_image'] ?? null;
		$removeImage = filter_var(
			$_POST['remove_featured_image'] ?? false,
			FILTER_VALIDATE_BOOLEAN
		);
		$newImage = null;
		$committed = false;
		try {
			$newImage = $this->getUploadedImage($imageManager);
			if ($newImage !== null) {
				$data['featured_image'] = $newImage;
			} elseif ($removeImage) {
				$data['featured_image'] = null;
			} else {
				$data['featured_image'] = $oldImage;
			}
			$db->beginTransaction();
			if ($id === 0) {
				$data['author_id'] = $userId;
				$savedId = (int) $repository->insertItem($data);
				if ($savedId <= 0) {
					throw new RuntimeException('Failed to create E-Service.');
				}
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
			$committed = true;
		} catch (Throwable $e) {
			if ($db->inTransaction()) {
				$db->rollBack();
			}
			if (!$committed && $newImage !== null) {
				try {
					$imageManager->delete($newImage);
				} catch (Throwable $cleanupError) {
					logError($cleanupError, 'Unable to clean up failed E-Service image upload.');
				}
			}
			logError($e, 'E-Service save failed.');
			throw $e;
		}
		if (
			$oldImage !== null &&
			$oldImage !== '' &&
			$oldImage !== $data['featured_image']
		) {
			try {
				$imageManager->delete($oldImage);
			} catch (Throwable $e) {
				logError($e, 'Unable to remove previous E-Service image.');
			}
		}
		jsonResponse($statusCode, $message, ['id' => $savedId]);
		exit;
	}
	public function delete(): never
	{
		$id = validateInt($_POST['id'] ?? null, 1, null);
		if ($id === false) {
			jsonResponse(422, 'Invalid E-Service ID.');
			exit;
		}
		$id = (int) $id;
		$userId = $this->getUserId();
		$db = Database::connect();
		$repository = new EServiceRepository($db);
		$audit = new AuditTrailRepository($db);
		$existing = $repository->fetchItemById($id);
		if ($existing === false || $existing === null) {
			jsonResponse(404, 'E-Service not found.');
			exit;
		}
		try {
			$db->beginTransaction();
			$deleted = $repository->deleteItem($id, $userId);
			if (!$deleted) {
				$db->rollBack();
				jsonResponse(409, 'E-Service could not be deleted.');
				exit;
			}
			$audit->insertItem([
				'action' => 'delete',
				'description' => 'Soft-deleted E-Service: ' . $existing['name'],
				'entity_type' => 'eservices',
				'entity_id' => $id,
				'user_id' => $userId
			]);
			$db->commit();
			jsonResponse(200, 'E-Service successfully deleted.');
			exit;
		} catch (Throwable $e) {
			if ($db->inTransaction()) {
				$db->rollBack();
			}
			logError($e, 'E-Service deletion failed.');
			throw $e;
		}
	}
}