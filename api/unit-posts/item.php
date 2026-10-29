<?php
declare(strict_types=1);

use Serv\UnitPostServices;

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
    $serv = new UnitPostServices();
    $serv->item();
} catch (Throwable $e) {
    logError($e, 'Failed to process item request.', 'ERROR', [
        'action' => 'get unit post item',
        'endpoint' => 'unit-posts/item'
    ]);

    jsonResponse(500);
}
