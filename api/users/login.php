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
    $serv->login();
} catch (Throwable $e) {
    logError($e, 'Failed to process login request.', 'ERROR', [
        'action' => 'login user',
        'endpoint' => 'users/login',
    ]);

    jsonResponse(500);
}
