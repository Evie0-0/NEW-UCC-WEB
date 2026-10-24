<?php
declare(strict_types=1);

namespace Core;

use InvalidArgumentException;
use RuntimeException;
use finfo;

final class ImageManager {
    private const UPLOAD_DIRECTORY = __DIR__ . '/../../storage/uploads/';
    private const MAX_SIZE = 10 * 1024 * 1024; // 10 MB
    private const MAX_WIDTH = 5000;
    private const MAX_HEIGHT = 5000;
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    // Verify uploaded image
    /** @param array<string, mixed> $file */
    public function verify(array $file): string|false {
        if (!isset($file['error'], $file['tmp_name'])) {
            throw new RuntimeException('The image file data is missing.');
        }

        $error = $file['error'];

        if ($error !== UPLOAD_ERR_OK) {
            match ($error) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
                    jsonResponse(413, 'The uploaded image is too large. The maximum size is 10 MB.'),
                UPLOAD_ERR_PARTIAL =>
                    jsonResponse(400, 'The image upload was incomplete. Please try again.'),
                UPLOAD_ERR_NO_FILE =>
                    jsonResponse(422, 'Please select an image to upload.'),
                UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION =>
                    throw new RuntimeException('The server could not process the uploaded image.'),
                default =>
                    throw new RuntimeException('The upload failed because of an unknown server error.')
            };
        }

        $tempFile = $file['tmp_name'];

        if (!is_uploaded_file($tempFile)) {
            throw new RuntimeException('The uploaded file could not be verified as a valid HTTP upload.');
        }

        $size = filesize($tempFile);

        if ($size === false) {
            throw new RuntimeException('Could not determine the uploaded image size.');
        }

        if ($size > self::MAX_SIZE) {
            jsonResponse(413, 'The image is too large. The maximum size is 10 MB.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tempFile);

        if ($mime === false) {
            throw new RuntimeException('Could not determine the uploaded image type.');
        }

        if (!in_array($mime, self::ALLOWED_MIME_TYPES, true)) {
            jsonResponse(415, 'The image format is not supported. Please use JPEG, PNG, or WebP.');
        }

        $imageInfo = getimagesize($tempFile);

        if ($imageInfo === false) {
            jsonResponse(422, 'The uploaded file is not a valid image.');
        }

        $imageMime = $imageInfo['mime'] ?? '';

        if ($imageMime !== $mime) {
            jsonResponse(422, 'The uploaded image could not be verified.');
        }

        $width = $imageInfo[0] ?? 0;
        $height = $imageInfo[1] ?? 0;

        if ($width > self::MAX_WIDTH || $height > self::MAX_HEIGHT) {
            jsonResponse(422, 'The image dimensions are too large. The maximum is 5000 × 5000 pixels.');
        }

        return $tempFile;
    }


    // Move temporary image to storage
    public function store(string $tempFile): string {
        if (!is_uploaded_file($tempFile)) {
            throw new RuntimeException('The validated uploaded image is no longer a valid uploaded file.');
        }

        if (!is_dir(self::UPLOAD_DIRECTORY)) {
            if (!mkdir(self::UPLOAD_DIRECTORY, 0755, true) && !is_dir(self::UPLOAD_DIRECTORY)) {
                throw new RuntimeException('Failed to create thes storage uploads directory.');
            }
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tempFile);

        if ($mime === false) {
            throw new RuntimeException('Could not determine the validated image type.');
        }

        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw new RuntimeException('The validated image has an unsupported MIME type.'),
        };

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = self::UPLOAD_DIRECTORY . $filename;

        if (!move_uploaded_file($tempFile, $destination)) {
            throw new RuntimeException('Failed to move the uploaded image into permanent storage.');
        }

        return $filename;
    }

    // Delete an image from storage uploads
    public function delete(string $filename): void {
        if (!preg_match('/\A[a-f0-9]{32}\.(?:jpg|png|webp)\z/', $filename)) {
            throw new InvalidArgumentException('The image filename is invalid.');
        }

        $path = self::UPLOAD_DIRECTORY . $filename;

        // Treat already deleted or missing image as a successful deletion
        if (!file_exists($path)) {
            return;
        }

        if (!is_file($path)) {
            throw new RuntimeException('The image path does not reference a regular file.');
        }

        if (!unlink($path)) {
            throw new RuntimeException('Failed to delete the image from storage.');
        }
    }
}
