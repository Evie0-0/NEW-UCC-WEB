<?php
declare(strict_types=1);

namespace Serv;

use Core\Database;
use Repo\CategoryRepository;

final class ArchiveServices {
    public function filter(): void {
    }

    public function unarchive(): void {
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
        } elseif ($categoryClass === 'unit-posts') {

        } elseif ($categoryClass === 'program-posts') {

        } else {
            jsonResponse(422, 'Invalid category class.');
        }

        jsonResponse(200, 'Post successfully unarchived. Its status has been set to draft by default.');
    }

    public function delete(): void {
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
        } elseif ($categoryClass === 'unit-posts') {

        } elseif ($categoryClass === 'program-posts') {

        } else {
            jsonResponse(422, 'Invalid category class.');
        }

        jsonResponse(200, 'Post successfully deleted.');
    }
}
