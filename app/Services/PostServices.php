<?php
declare(strict_types=1);

namespace Serv;

use Core\Database;
use Core\ImageManager;
use DateTimeImmutable;
use DateTimeZone;
use Repo\PostRepository;

final class PostServices {
    private const STATUS_KEYS = ['draft', 'published'];

    public function create(): void {
        $title = trim(validateString($_POST['title'] ?? '', 'Title'));
        $excerpt = trim(validateString($_POST['excerpt'] ?? '', 'Excerpt'));
        $content = trim(validateString($_POST['content'] ?? '', 'Content'));
        $featuredImage = $_FILES['featured_image'] ?? null;
        $category = $_POST['category'] ?? '';
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

        $repo = new PostRepository(Database::connect());
        $fetchedCategories = $repo->fetchCategories();

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

       // REMINDER: store in repo
    }

    public function update(): void {

    }
}
