<?php
declare(strict_types=1);

namespace Serv;

use Core\Database;
use Repo\CategoryRepository;

final class CategoryServices {
    public function byClass(): void {
        $class = trim(validateString($_GET['class'] ?? '', 'Class'));

        if ($class === '') {
            jsonResponse(422, 'Class cannot be empty.');
        }

        $repo = new CategoryRepository(Database::connect());
        $fetchedCategories = $repo->fetchItemsByClass($class);

        jsonResponse(200, 'Categories successfully fetched.', $fetchedCategories);
    }
}
