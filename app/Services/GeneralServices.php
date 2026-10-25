<?php
declare(strict_types=1);

namespace Serv;

use Core\Database;
use Repo\CategoryRepository;

final class GeneralServices {
    public function unarchivePost(): void {
        $id = validateId($_POST['id'] ?? null, 'id');
        $category = validateId($_POST['category'] ?? null, 'category id');

        $repo = new CategoryRepository(Database::connect());
        $fetchedCategory = $repo->fetchItemById($category);

        if ($fetchedCategory === false) {
           jsonResponse(422, 'Invalid category.');
        }

        $categoryClass = $fetchedCategory['class'];

        if ($categoryClass === 'university-posts') {
            $serv = new PostServices();
            $serv->unarchive($id);
        } elseif ($categoryName === 'unit-posts') {

        } elseif ($categoryName === 'program-posts') {

        } else {
            jsonResponse(422, 'Invalid category class.');
        }

        jsonResponse(200, 'Post successfully unarchived. Its status has been set to draft by default.');
    }

    public function deletePost(): void {
        $id = validateId($_POST['id'] ?? null, 'id');
        $category = validateId($_POST['category'] ?? null, 'category id');

        $repo = new CategoryRepository(Database::connect());
        $fetchedCategory = $repo->fetchItemById($category);

        if ($fetchedCategory === false) {
           jsonResponse(422, 'Invalid category.');
        }

        $categoryClass = $fetchedCategory['class'];

        if ($categoryClass === 'university-posts') {
            $serv = new PostServices();
            $serv->delete($id);
        } elseif ($categoryName === 'unit-posts') {

        } elseif ($categoryName === 'program-posts') {

        } else {
            jsonResponse(422, 'Invalid category class.');
        }

        jsonResponse(200, 'Post successfully deleted. If this was unintentional, please contact the administrator for assistance.');
    }
}
