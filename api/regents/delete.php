<?php
declare(strict_types=1);

use Core\Token;
use Serv\RegentServices;

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Support/helper.php';

session_start();

if (!isSuperadmin()) {
    jsonResponse(401, 'You are unauthorized to make this request.');
}

session_write_close();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, 'Invalid request method.');
}

session_start();

if (!Token::verify($_POST['csrf_key'] ?? null)) {
    jsonResponse(403, 'Invalid CSRF token.');
}

session_write_close();

try {
    $serv = new RegentServices();
    $serv->delete();
} catch (Throwable $e) {
    logError($e, 'Failed to process delete request.', 'ERROR', [
        'action' => 'delete regent',
        'endpoint' => 'regents/delete'
    ]);

    jsonResponse(500);
}
