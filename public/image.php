<?php
declare(strict_types=1);

// Serves an uploaded image from storage/uploads, which sits outside the web root.
// Usage: image.php?f=<filename>   e.g. image.php?f=UCC2.png

const UPLOAD_DIRECTORY = __DIR__ . '/../storage/uploads/';

const MIME_TYPES = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'webp' => 'image/webp',
];

$filename = $_GET['f'] ?? '';

// Plain filenames only: no slashes, no leading dot, so path traversal is not possible
if (!is_string($filename) || preg_match('/\A[A-Za-z0-9_-][A-Za-z0-9._-]{0,254}\z/', $filename) !== 1) {
    http_response_code(404);
    exit;
}

$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

if (!isset(MIME_TYPES[$extension])) {
    http_response_code(404);
    exit;
}

$path = UPLOAD_DIRECTORY . $filename;

if (!is_file($path)) {
    http_response_code(404);
    exit;
}

$size = filesize($path);
$modified = filemtime($path);

if ($size === false || $modified === false) {
    http_response_code(404);
    exit;
}

$etag = '"' . md5($filename . '|' . $modified . '|' . $size) . '"';

header('Content-Type: ' . MIME_TYPES[$extension]);
header('X-Content-Type-Options: nosniff');
// Revalidate each time so a replaced file with the same name (like UCC2.png) shows up
header('Cache-Control: public, max-age=3600, must-revalidate');
header('ETag: ' . $etag);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $modified) . ' GMT');

// Let the browser reuse its cached copy
if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
    http_response_code(304);
    exit;
}

header('Content-Length: ' . $size);
readfile($path);
exit;