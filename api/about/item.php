<?php
declare(strict_types=1);

use Serv\AboutServices;

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Support/helper.php';

session_start();

if (!isSuperadmin()) {
    jsonResponse(401, 'You are unauthorized to make this request.');
}

session_write_close();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(405, 'Invalid request method.');
}

try {
    $serv = new AboutServices();
    $serv->item();
} catch (Throwable $e) {
    logError($e, 'Failed to process item request.', 'ERROR', [
        'action' => 'get about item',
        'endpoint' => 'about/item'
    ]);

    jsonResponse(500);
}
