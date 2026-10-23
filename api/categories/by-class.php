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
    jsonDataResponse([]);
}

$serv = new CategoryRepository(Database::connect());
$fetchedCategories = $serv->fetchItemsByClass($class);

jsonDataResponse($fetchedCategories);
