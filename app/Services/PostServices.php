<?php
declare(strict_types=1);

namespace Serv;

use Core\Database;
use Core\ImageManager;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Repo\AuditTrailRepository;
use Repo\CategoryRepository;
use Repo\PostRepository;
use Throwable;

final class PostServices {
    private const STATUS_KEYS = ['draft', 'published'];
    private const FILTER_KEYS = ['all', 'draft', 'published', 'pending', 'expired'];
    private const SORT_KEYS = ['create-desc', 'create-asc', 'title-desc', 'title-asc', 'publish-desc', 'publish-asc', 'expiry-desc', 'expiry-asc'];
    private const PER_PAGE = 10;

    public function save(): void {
        $id = validateInt($_POST['id'] ?? null, 0, null);

        if ($id === false) {
            throw new InvalidArgumentException('Invalid post id.');
        }

        if ($id === 0) {
            $this->create();
        } else {
            $this->update();
        }
    }

    private function create(): void {
        $title = trim(validateString($_POST['title'] ?? '', 'Title'));
        $excerpt = trim(validateString($_POST['excerpt'] ?? '', 'Excerpt'));
        $content = trim(validateString($_POST['content'] ?? '', 'Content'));
        $featuredImage = $_FILES['featured_image'] ?? null;
        $category = validateInt($_POST['category'] ?? null, 1, null);
        $status = $_POST['status'] ?? '';
        $publishDate = validateDate($_POST['publish_date'] ?? null);
        $expirationDate = validateDate($_POST['expiration_date'] ?? null);

        if ($title === '') {
            jsonResponse(422, 'Title cannot be empty.');
        }

        if ($excerpt === '') {
            jsonResponse(422, 'Excerpt cannot be empty.');
        }

        if ($content === '') {
            jsonResponse(422, 'Content cannot be empty.');
        }

        $imageHandler = new ImageManager();
        $tempFeaturedImage = null;

        if ($featuredImage !== null && ($featuredImage['error'] ?? null) !== UPLOAD_ERR_NO_FILE) {
            $tempFeaturedImage = $imageHandler->verify($featuredImage);
        }

        if ($category === false) {
            throw new InvalidArgumentException('Invalid category id.');
        }

        $repo = new CategoryRepository(Database::connect());
        $fetchedCategories = $repo->fetchItemsByClass('university-posts');
        $categoryName = array_column($fetchedCategories, 'name', 'id')[$category] ?? null;

        if ($categoryName === null) {
           jsonResponse(422, 'Invalid category selection.');
        }

        if (!in_array($status, self::STATUS_KEYS, true)) {
            jsonResponse(422, 'Invalid status selection.');
        }

        $today = new DateTimeImmutable('today', new DateTimeZone('Asia/Manila'));

        if ($publishDate === false) {
            jsonResponse(422, 'Publish date must be a valid date.');
        }

        if ($publishDate < $today->format('Y-m-d')) {
            jsonResponse(422, 'Publish date cannot be in the past.');
        }

        if ($expirationDate === false) {
            jsonResponse(422, 'Expiration date must be a valid date.');
        }

        if ($expirationDate < $today->format('Y-m-d')) {
            jsonResponse(422, 'Expiration date cannot be in the past.');
        }

        if ($expirationDate < $publishDate) {
            jsonResponse(422, 'Expiration date cannot be earlier than the publish date.');
        }

        $featuredImageFile = null;

       if ($tempFeaturedImage !== null) {
           $featuredImageFile = $imageHandler->store($tempFeaturedImage);
       }

       session_start();

       $authorId = $_SESSION['user_id'];

       session_write_close();

       $data = [
           'title' => $title,
           'excerpt' => $excerpt,
           'content' => $content,
           'featured_image' => $featuredImageFile,
           'category_id' => $category,
           'status' =>  $status,
           'author_id' => $authorId,
           'published_at' => $publishDate,
           'expires_at' => $expirationDate
       ];

       try {
           $conn = Database::connect();
           $conn->beginTransaction();

           $repo = new PostRepository($conn);
           $insertedPost = $repo->insertItem($data);

           $data = [
               'action' => 'CREATE',
               'description' => "Created a new post titled: $title",
               'entity_type' => "University Post - $categoryName",
               'entity_id' => $insertedPost,
               'user_id' => $authorId
           ];

           $repo = new AuditTrailRepository($conn);
           $repo->insertItem($data);

           $conn->commit();
       } catch (Throwable $e) {
           if ($conn->inTransaction()) {
               $conn->rollBack();
           }

           throw $e;
       }

       jsonResponse(201, 'Post successfully created.');
    }

    private function update(): void {
        $id = validateInt($_POST['id'] ?? null, 1, null);
        $title = trim(validateString($_POST['title'] ?? '', 'Title'));
        $excerpt = trim(validateString($_POST['excerpt'] ?? '', 'Excerpt'));
        $content = trim(validateString($_POST['content'] ?? '', 'Content'));
        $removeFeaturedImage = validateBool($_POST['remove_featured_image'] ?? false);
        $featuredImage = $_FILES['featured_image'] ?? null;
        $category = validateInt($_POST['category'] ?? null, 1, null);
        $status = $_POST['status'] ?? '';
        $publishDate = validateDate($_POST['publish_date'] ?? null);
        $expirationDate = validateDate($_POST['expiration_date'] ?? null);

        if ($id === false) {
            throw new InvalidArgumentException('Invalid post id.');
        }

        $repo = new PostRepository(Database::connect());
        $fetchedPost = $repo->fetchItemById($id);

        if ($fetchedPost === false) {
            jsonResponse(404, 'Post does not exist.');
        }

        if ($title === '') {
            jsonResponse(422, 'Title cannot be empty.');
        }

        if ($excerpt === '') {
            jsonResponse(422, 'Excerpt cannot be empty.');
        }

        if ($content === '') {
            jsonResponse(422, 'Content cannot be empty.');
        }

        if ($removeFeaturedImage === null) {
            throw new InvalidArgumentException('Invalid remove featured image indicator.');
        }

        $hasNewFeaturedImage = ($featuredImage !== null && ($featuredImage['error'] ?? null) !== UPLOAD_ERR_NO_FILE);

        if ($removeFeaturedImage && $hasNewFeaturedImage) {
            jsonResponse(422, 'Cannot remove and replace featured image at the same time.');
        }

        $imageHandler = new ImageManager();
        $tempFeaturedImage = null;

        if ($hasNewFeaturedImage) {
            $tempFeaturedImage = $imageHandler->verify($featuredImage);
        }

        if ($category === false) {
            throw new InvalidArgumentException('Invalid category id.');
        }

        $repo = new CategoryRepository(Database::connect());
        $fetchedCategories = $repo->fetchItemsByClass('university-posts');
        $categoryName = array_column($fetchedCategories, 'name', 'id')[$category] ?? null;

        if ($categoryName === null) {
           jsonResponse(422, 'Invalid category selection.');
        }

        if (!in_array($status, self::STATUS_KEYS, true)) {
            jsonResponse(422, 'Invalid status selection.');
        }

        $today = new DateTimeImmutable('today', new DateTimeZone('Asia/Manila'));

        if ($publishDate === false) {
            jsonResponse(422, 'Publish date must be a valid date.');
        }

        if ($publishDate < $today->format('Y-m-d')) {
            jsonResponse(422, 'Publish date cannot be in the past.');
        }

        if ($expirationDate === false) {
            jsonResponse(422, 'Expiration date must be a valid date.');
        }

        if ($expirationDate < $today->format('Y-m-d')) {
            jsonResponse(422, 'Expiration date cannot be in the past.');
        }

        if ($expirationDate < $publishDate) {
            jsonResponse(422, 'Expiration date cannot be earlier than the publish date.');
        }

        $oldFeaturedImage = $fetchedPost['featured_image'] ?? null;
        $featuredImageFile = $oldFeaturedImage;
        $newFeaturedImageFile = null;

        if ($removeFeaturedImage === true) {
            $featuredImageFile = null;
        } elseif ($tempFeaturedImage !== null) {
            $newFeaturedImageFile = $imageHandler->store($tempFeaturedImage);
            $featuredImageFile = $newFeaturedImageFile;
        }

        session_start();

        $editorId = $_SESSION['user_id'];

        session_write_close();

        $data = [
            'title' => $title,
            'excerpt' => $excerpt,
            'content' => $content,
            'featured_image' => $featuredImageFile,
            'category_id' => $category,
            'status' =>  $status,
            'editor_id' => $editorId,
            'published_at' => $publishDate,
            'expires_at' => $expirationDate,
            'id' => $id
        ];

        try {
            $conn = Database::connect();
            $conn->beginTransaction();

            $repo = new PostRepository($conn);
            $repo->updateItem($data);

            $data = [
                'action' => 'UPDATE',
                'description' => "Updated the post titled: $title",
                'entity_type' => "University Post - $categoryName",
                'entity_id' => $id,
                'user_id' => $editorId
            ];

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem($data);

            $conn->commit();
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            if ($newFeaturedImageFile !== null) {
                try {
                    $imageHandler->delete($newFeaturedImageFile);
                } catch (Throwable $e1) {
                    logError($e1, 'Failed to delete new featured image after a database failure.', 'ERROR', [
                        'action' => 'save post - update',
                        'endpoint' => 'posts/save',
                        'id' => $id
                    ]);
                }
            }

            throw $e;
        }

        if (is_string($oldFeaturedImage) && $oldFeaturedImage !== '' && $oldFeaturedImage !== $featuredImageFile) {
            try {
                $imageHandler->delete($oldFeaturedImage);
            } catch (Throwable $e) {
                logError($e, 'Failed to delete old featured image.', 'ERROR', [
                    'action' => 'save post - update',
                    'endpoint' => 'posts/save',
                    'id' => $id
                ]);
            }
        }

        jsonResponse(200, 'Post successfully updated.');
    }

    public function filter(): void {
        $search = $_GET['search'] ?? '';
        $filter = $_GET['filter'] ?? '';
        $sort = $_GET['sort'] ?? '';
        $page = validateInt($_GET['page'] ?? 1, 1, null);

        if (!is_string($search)) {
            jsonResponse(422, 'Search must be a valid string.');
        }

        $search = trim($search);

        if (!in_array($filter, self::FILTER_KEYS, true)) {
            jsonResponse(422, 'Invalid filter selection.');
        }

        if (!in_array($sort, self::SORT_KEYS, true)) {
            jsonResponse(422, 'Invalid sort selection.');
        }

        if ($page === false) {
            jsonResponse(422, 'Invalid page number.');
        }

        $data = [
            'search' => $search,
            'filter' => $filter,
            'sort' => $sort
        ];

        $repo = new PostRepository(Database::connect());
        $totalPosts = $repo->countFiltered($data);
        $totalPages = (int) ceil($totalPosts / self::PER_PAGE);

        if ($page > max(1, $totalPages)) {
            jsonResponse(422, 'Requested page does not exist.');
        }

        $offset = ($page - 1) * self::PER_PAGE;
        $posts = $repo->filter([
            ...$data,
            'offset' => $offset
        ]);

        jsonResponse(200, 'Successfully filtered post items.', [
            'posts' => $posts,
            'current_page' => $page,
            'total_posts' => $totalPosts,
            'total_pages' => $totalPages
        ]);
    }

    public function item(): void {
        $id = validateInt($_GET['id'] ?? null, 1, null);

        if ($id === false) {
            throw new InvalidArgumentException('Invalid post id.');
        }

        $repo = new PostRepository(Database::connect());
        $post = $repo->fetchItemById($id);

        if ($post === false) {
            jsonResponse(404, 'Post does not exist.');
        }

        jsonResponse(200, 'Post successfully fetched.', [
            'post' => $post
        ]);
    }

    public function archive(): void {
        $id = validateInt($_POST['id'] ?? null, 1, null);

        if ($id === false) {
            throw new InvalidArgumentException('Invalid post id.');
        }

        $repo = new PostRepository(Database::connect());
        $post = $repo->fetchItemById($id);

        if ($post === false) {
            jsonResponse(404, 'Post does not exist.');
        }

        $archivedAt = $post['archived_at'];

        if ($archivedAt !== null) {
            jsonResponse(422, 'Post is already archived.');
        }

        $title = $post['title'];
        $category = $post['category_id'];

        $repo = new CategoryRepository(Database::connect());
        $fetchedCategories = $repo->fetchItemsByClass('university-posts');
        $categoryName = array_column($fetchedCategories, 'name', 'id')[$category] ?? null;

        session_start();

        $editorId = $_SESSION['user_id'];

        session_write_close();

        $data = [
            'editor_id' => $editorId,
            'id' => $id
        ];

        try {
            $conn = Database::connect();
            $conn->beginTransaction();

            $repo = new PostRepository($conn);
            $repo->archiveItem($data);

            $data = [
                'action' => 'ARCHIVE',
                'description' => "Archived the post titled: $title",
                'entity_type' => "University Post - $categoryName",
                'entity_id' => $id,
                'user_id' => $editorId
            ];

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem($data);

            $conn->commit();
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            throw $e;
        }

        jsonResponse(200, 'Post successfully archived.');
    }

    public function unarchive(): void {
        $id = validateId($_POST['id'] ?? null, 'post id');
        $category = validateId($_POST['category'] ?? null, 'category id');

        $repo = new PostRepository(Database::connect());
        $post = $repo->fetchItemById($id);

        if ($post === false) {
            jsonResponse(404, 'Post does not exist.');
        }

        $archivedAt = $post['archived_at'];

        if ($archivedAt === null) {
            jsonResponse(422, 'Post is already unarchived.');
        }

        $title = $post['title'];

        if ($category !== $post['category_id']) {
            jsonResponse(422, 'Misleading category.');
        }

        $repo = new CategoryRepository(Database::connect());
        $fetchedCategories = $repo->fetchItemsByClass('university-posts');
        $categoryName = array_column($fetchedCategories, 'name', 'id')[$category] ?? null;

        session_start();

        $editorId = $_SESSION['user_id'];

        session_write_close();

        $data = [
            'editor_id' => $editorId,
            'id' => $id
        ];

        try {
            $conn = Database::connect();
            $conn->beginTransaction();

            $repo = new PostRepository($conn);
            $repo->unarchiveItem($data);

            $data = [
                'action' => 'UNARCHIVE',
                'description' => "Unarchived the post titled: $title",
                'entity_type' => "University Post - $categoryName",
                'entity_id' => $id,
                'user_id' => $editorId
            ];

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem($data);

            $conn->commit();
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            throw $e;
        }
    }

    public function delete(): void {
        $id = validateId($_POST['id'] ?? null, 'post id');
        $category = validateId($_POST['category'] ?? null, 'category id');

        $repo = new PostRepository(Database::connect());
        $post = $repo->fetchItemById($id);

        if ($post === false) {
            jsonResponse(404, 'Post does not exist.');
        }

        $archivedAt = $post['archived_at'];

        if ($archivedAt === null) {
            jsonResponse(422, 'Post is not archived. Cannot be deleted.');
        }

        $title = $post['title'];

        if ($category !== $post['category_id']) {
            jsonResponse(422, 'Misleading category.');
        }

        $repo = new CategoryRepository(Database::connect());
        $fetchedCategories = $repo->fetchItemsByClass('university-posts');
        $categoryName = array_column($fetchedCategories, 'name', 'id')[$category] ?? null;

        session_start();

        $editorId = $_SESSION['user_id'] ?? 1;

        session_write_close();

        $data = [
            'editor_id' => $editorId,
            'id' => $id
        ];

        try {
            $conn = Database::connect();
            $conn->beginTransaction();

            $repo = new PostRepository($conn);
            $repo->deleteItem($data);

            $data = [
                'action' => 'DELETE',
                'description' => "Deleted the post titled: $title",
                'entity_type' => "University Post - $categoryName",
                'entity_id' => $id,
                'user_id' => $editorId
            ];

            $repo = new AuditTrailRepository($conn);
            $repo->insertItem($data);

            $conn->commit();
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            throw $e;
        }
    }
}
