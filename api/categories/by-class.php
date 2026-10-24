<?php
declare(strict_types=1);

use Core\Database;
use Repo\CategoryRepository;

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Support/helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(405, 'Invalid request method.');
}

$class = trim($_GET['class'] ?? '');

if ($class === '') {
    jsonDataResponse(422);
}

try {
    $serv = new CategoryRepository(Database::connect());
    $fetchedCategories = $serv->fetchItemsByClass($class);

    jsonDataResponse(200, [
        'data' => $fetchedCategories
    ]);
} catch (Throwable $e) {
    logError($e, 'Failed to fetch categories.', 'ERROR', [
        'action' => 'fetch categories',
        'endpoint' => 'categories/by-class'
    ]);

    jsonDataResponse(500);
}
