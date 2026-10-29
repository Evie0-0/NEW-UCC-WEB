<?php
declare(strict_types=1);

use Serv\AuditTrailServices;
use Serv\UserServices;

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Support/helper.php';

if (!UserServices::isAuthorized([UserServices::SUPERADMIN_KEY])) {
    jsonResponse(403, 'You are unauthorized', [
        'redirect' => 'login.php?error=403'
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(405, 'Invalid request method.');
}

try {
    $serv = new AuditTrailServices();
    $serv->actions();
} catch (Throwable $e) {
    logError($e, 'Failed to fetch actions.', 'ERROR', [
        'action' => 'fetch actions',
        'endpoint' => 'audit-trail/actions'
    ]);

    jsonResponse(500);
}
