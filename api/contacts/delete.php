<?php
declare(strict_types=1);

use Core\Token;
use Serv\ContactServices;
use Serv\UserServices;

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Support/helper.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
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

if (!Token::verify($_POST[Token::KEY] ?? null)) {
    jsonResponse(403, 'Invalid CSRF token. Refresh the page and try again.');
    exit;
}

try {
    $service = new ContactServices();
    $service->delete();
} catch (Throwable $e) {
    logError($e, 'Failed to delete contact.', 'ERROR', [
        'endpoint' => 'contacts/delete'
    ]);
    jsonResponse(500, 'Unable to delete contact. Please try again.');
    exit;
}