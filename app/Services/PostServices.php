<?php
declare(strict_types=1);

namespace Serv;

use Core\Database;
use Core\ImageManager;
use DateTimeImmutable;
use DateTimeZone;
use Repo\AuditTrailRepository;
use Repo\CategoryRepository;
use Repo\PostRepository;
use Throwable;

final class PostServices {
    private const STATUS_KEYS = ['draft', 'published'];

    public function create(): void {
        $title = trim(validateString($_POST['title'] ?? '', 'Title'));
        $excerpt = trim(validateString($_POST['excerpt'] ?? '', 'Excerpt'));
        $content = trim(validateString($_POST['content'] ?? '', 'Content'));
        $featuredImage = $_FILES['featured_image'] ?? null;
        $category = validateId($_POST['category'] ?? null, 'category');
        $status = $_POST['status'] ?? '';
        $publishDate = validateDate($_POST['publish_date'] ?? '', 'Publish date');
        $expirationDate = validateDate($_POST['expiration_date'] ?? '', 'Expiration date');

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

        if ($publishDate < $today->format('Y-m-d')) {
            jsonResponse(422, 'Publish date cannot be in the past.');
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

       $authorId = $_SESSION['user_id'] ?? 1;
       $editorId = $_SESSION['user_id'] ?? 1;

       session_write_close();

       $data = [
           'title' => $title,
           'excerpt' => $excerpt,
           'content' => $content,
           'featured_image' => $featuredImageFile,
           'category_id' => $category,
           'status' =>  $status,
           'author_id' => $authorId,
           'editor_id' => $editorId,
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

    public function update(): void {
        $id = validateId($_POST['id'] ?? null, 'post id');
        $title = trim(validateString($_POST['title'] ?? '', 'Title'));
        $excerpt = trim(validateString($_POST['excerpt'] ?? '', 'Excerpt'));
        $content = trim(validateString($_POST['content'] ?? '', 'Content'));
        $removeFeaturedImage = validateBool($_POST['remove_featured_image'] ?? false, 'Remove featured image flag');
        $featuredImage = $_FILES['featured_image'] ?? null;
        $category = validateId($_POST['category'] ?? null, 'category');
        $status = $_POST['status'] ?? '';
        $publishDate = validateDate($_POST['publish_date'] ?? '', 'Publish date');
        $expirationDate = validateDate($_POST['expiration_date'] ?? '', 'Expiration date');

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

        $hasNewFeaturedImage = ($featuredImage !== null && ($featuredImage['error'] ?? null) !== UPLOAD_ERR_NO_FILE);

        if ($removeFeaturedImage && $hasNewFeaturedImage) {
            jsonResponse(422, 'Cannot remove and replace featured image at the same time.');
        }

        $imageHandler = new ImageManager();
        $tempFeaturedImage = null;

        if ($hasNewFeaturedImage) {
            $tempFeaturedImage = $imageHandler->verify($featuredImage);
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

        if ($publishDate < $today->format('Y-m-d')) {
            jsonResponse(422, 'Publish date cannot be in the past.');
        }

        if ($expirationDate < $today->format('Y-m-d')) {
            jsonResponse(422, 'Expiration date cannot be in the past.');
        }

        if ($expirationDate < $publishDate) {
            jsonResponse(422, 'Expiration date cannot be earlier than the publish date.');
        }

        $featuredImageFile = $fetchedPost['featured_image'] ?? null;

        if ($removeFeaturedImage) {
            $feauredImageFile = null;
        } elseif ($tempFeaturedImage !== null) {
            $featuredImageFile = $imageHandler->store($tempFeaturedImage);
        }

        session_start();

        $editorId = $_SESSION['user_id'] ?? 1;

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

            throw $e;
        }

        jsonResponse(200, 'Post successfully updated.');
    }
}
