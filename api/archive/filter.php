<?php
declare(strict_types=1);
use Serv\ArchiveServices;

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Support/helper.php';

session_start();

$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'superadmin';

if (!isSuperadmin()) {
    jsonResponse(401, 'You are unauthorized to make this request.');
};

session_write_close();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(405, 'Invalid request method.');
}

try {
    $serv = new ArchiveServices();
    $serv->filter();
} catch (Throwable $e) {
    logError($e, 'Failed to process filter request.', 'ERROR', [
        'action' => 'filter archived items',
        'endpoint' => 'archive/filter'
    ]);

    jsonResponse(500);
}
