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
        $category = filter_var($_POST['category'] ?? null, FILTER_VALIDATE_INT);
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

        if ($category === false) {
            jsonResponse(422, 'Invalid category.');
        }

        $repo = new CategoryRepository(Database::connect());
        $fetchedCategories = $repo->fetchItemsByClass('university-posts');

        if (!in_array($category, array_column($fetchedCategories, 'id'), true)) {
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
           'featured_image' => $featuredImage,
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
               'entity_type' => 'University Post',
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

    }
}
