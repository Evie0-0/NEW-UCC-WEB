<?php
declare(strict_types=1);

namespace Serv;

use Core\Database;
use Repo\ArchiveRepository;
use Repo\CategoryRepository;

final class ArchiveServices {
    private const SORT_KEYS = ['archive-desc', 'archive-asc', 'title-desc', 'title-asc', 'publish-desc', 'publish-asc', 'expiry-desc', 'expiry-asc'];

    public function filter(): void {
        $search = trim(validateString($_GET['search'] ?? '', 'Search'));
        $filter = $_GET['filter'] ?? '';
        $sort = $_GET['sort'] ?? '';

        $repo = new CategoryRepository(Database::connect());
        $fetchedCategories = $repo->fetchItems();

        if ($fetchedCategories === [] && $filter === 'all') {
            jsonResponse(422, 'Invalid filter selection.');
        }

        if (!in_array($sort, self::SORT_KEYS, true)) {
            jsonResponse(422, 'Invalid sort selection.');
        }

        $data = [
            'search' => $search,
            'filter' => $filter,
            'sort' => $sort
        ];

        $repo = new ArchiveRepository(Database::connect());
        $posts = $repo->filter($data);

        jsonResponse(200, 'Successfully filtered posts items.', $posts);
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
