<?php
declare(strict_types=1);

use Core\Token;
use Serv\EServiceServices;
use Serv\UserServices;

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Support/helper.php';

if (!UserServices::isAuthorized([UserServices::SUPERADMIN_KEY])) {
    jsonResponse(403, 'You are unauthorized.', [
        'redirect' => 'login.php?error=403'
    ]);
}

// Only POST requests are allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, 'Invalid request method.');
}

// Verify the CSRF token
if (!Token::verify($_POST[Token::KEY] ?? null)) {
    jsonResponse(403, 'Invalid CSRF token.');
}

try {
    $service = new EServiceServices();
    $service->delete();
} catch (Throwable $e) {
    logError($e, 'Failed to delete E-Service.', 'ERROR', [
        'action' => 'delete e-service',
        'endpoint' => 'eservices/delete'
    ]);

    jsonResponse(500, 'Unable to delete E-Service. Please try again.');
}