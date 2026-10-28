<?php
declare(strict_types=1);

use Core\Token;
use Serv\PostServices;
use Serv\UserServices;

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Support/helper.php';

if (!UserServices::isAuthorized([UserServices::SUPERADMIN_KEY])) {
    jsonResponse(401, 'Your session has expired. Please log in again.', [
        'redirect' => 'login.php?error=401',
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, 'Invalid request method.');
}

if (!Token::verify($_POST[Token::KEY] ?? null)) {
    jsonResponse(403, 'Invalid CSRF token.');
}

try {
    $serv = new PostServices();
    $serv->save();
} catch (Throwable $e) {
    logError($e, 'Failed to process save request.', 'ERROR', [
        'action' => 'save post',
        'endpoint' => 'posts/save'
    ]);

    jsonResponse(500);
}
