<?php
declare(strict_types=1);

use Core\Token;
use Serv\PostServices;

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Support/helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, 'Invalid request method.');
}

# if (!Token::verify($_POST['csrf_key'] ?? null)) {
#    jsonResponse(403, 'Invalid CSRF token.');
#}

try {
    $serv = new PostServices();
} catch (Throwable $e) {
    logError($e, 'Failed to process save request.', 'ERROR', [
        'action' => 'save post',
        'endpoint' => 'posts/save'
    ]);

    jsonResponse(500);
}
