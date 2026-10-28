<?php
declare(strict_types=1);

use Serv\UserServices;

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Support/helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, 'Invalid request method.');
}

try {
    $serv = new UserServices();
    $serv->logout();
} catch (Throwable $e) {
    logError($e, 'Failed to process logout request.', 'ERROR', [
        'action' => 'logout user',
        'endpoint' => 'users/logout',
    ]);

    jsonResponse(500);
}
