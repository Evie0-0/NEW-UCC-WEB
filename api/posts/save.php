<?php
declare(strict_types=1);

use Core\Token;
use Serv\PostServices;

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Support/helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, 'Invalid request method.');
}

session_start();

if (!Token::verify($_POST['csrf_key'] ?? null)) {
    jsonResponse(403, 'Invalid CSRF token.');
}

session_write_close();

$postId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if ($postId === false) {
    jsonResponse(422, 'Invalid post id.');
}

try {

    $serv = new PostServices();

    if ($postId !== 0) {
        $serv->update();
    } else {
        $serv->create();
    }
} catch (Throwable $e) {
    logError($e, 'Failed to process save request.', 'ERROR', [
        'action' => 'save post',
        'endpoint' => 'posts/save'
    ]);

    jsonResponse(500);
}
