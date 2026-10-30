<?php
declare(strict_types=1);

use Core\Token;
use Serv\EServiceServices;
use Serv\UserServices;

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Support/helper.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Only POST requests are allowed.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');

    jsonResponse(405, 'Invalid request method.');
    exit;
}

// Check whether the user is authorized.
if (!UserServices::isAuthorized([
    UserServices::SUPERADMIN_KEY,
    UserServices::DEAN_KEY,
    UserServices::PROGRAM_HEAD_KEY
])) {
    jsonResponse(
        403,
        'You are unauthorized. Please log in with an authorized account.'
    );
    exit;
}

// Verify the CSRF token.
if (!Token::verify($_POST[Token::KEY] ?? null)) {
    jsonResponse(
        403,
        'Invalid CSRF token. Refresh the page and try again.'
    );
    exit;
}

try {

    // Use the existing E-Service backend.
    $service = new EServiceServices();

    // Handles creating and updating E-Services.
    $service->save();

} catch (Throwable $e) {

    logError(
        $e,
        'Failed to save E-Service.',
        'ERROR',
        [
            'action' => 'save e-service',
            'endpoint' => 'eservices/save'
        ]
    );

    jsonResponse(
        500,
        'Unable to save E-Service. Please try again.'
    );
    exit;
}