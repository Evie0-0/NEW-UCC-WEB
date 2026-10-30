<?php
declare(strict_types=1);

use Core\Database;
use Repo\ContactRepository;
use Serv\UserServices;

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Support/helper.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');
    jsonResponse(405, 'Invalid request method.');
    exit;
}

if (!UserServices::isAuthorized([
    UserServices::SUPERADMIN_KEY,
    UserServices::DEAN_KEY,
    UserServices::PROGRAM_HEAD_KEY
])) {
    jsonResponse(403, 'Unauthorized access.');
    exit;
}

try {
    $db = Database::connect();
    $repository = new ContactRepository($db);
    $contacts = $repository->fetchAll();

    echo json_encode([
        'success' => true,
        'message' => 'Contacts fetched successfully.',
        'data' => $contacts
    ], JSON_THROW_ON_ERROR);
    exit;
} catch (Throwable $e) {
    logError($e, 'Failed to fetch contacts.', 'ERROR', [
        'endpoint' => 'contacts/fetch'
    ]);
    jsonResponse(500, 'Unable to fetch contacts.');
    exit;
}