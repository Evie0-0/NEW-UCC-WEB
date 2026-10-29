<?php
declare(strict_types=1);

use Serv\CategoryServices;
use Serv\UserServices;

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Support/helper.php';

if (!UserServices::isAuthorized()) {
    jsonResponse(403, 'You are unauthorized', [
        'redirect' => 'login.php?error=403'
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(405, 'Invalid request method.');
}

try {
    $serv = new CategoryServices();
    $serv->byClass();
} catch (Throwable $e) {
    logError($e, 'Failed to fetch categories.', 'ERROR', [
        'action' => 'fetch categories',
        'endpoint' => 'categories/by-class'
    ]);

    jsonResponse(500);
}
