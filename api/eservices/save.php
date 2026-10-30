<?php
declare(strict_types=1);

use Core\Token;
use Serv\EServiceServices;
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
    jsonResponse(
        403,
        'You are unauthorized. Please log in with an authorized account.'
    );
    exit;
}

if (!Token::verify($_POST[Token::KEY] ?? null)) {
    jsonResponse(
        403,
        'Invalid CSRF token. Refresh the page and try again.'
    );
    exit;
}

try {

    // Use the existing E-Service backend
    $service = new EServiceServices();

    // Handles creating and updating E-Services
    $service->save();

} catch (Throwable $e) {
	error_log(
		'E-Service save error: ' .
		$e->getMessage() .
		' | File: ' .
		$e->getFile() .
		' | Line: ' .
		$e->getLine()
	);
	jsonResponse(
		500,
		'DEBUG: ' . $e->getMessage() .
		' | File: ' . basename($e->getFile()) .
		' | Line: ' . $e->getLine()
	);
	exit;
}