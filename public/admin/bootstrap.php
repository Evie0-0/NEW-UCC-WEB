<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

date_default_timezone_set('Asia/Manila');

// Error log configuration
$logDirectory = __DIR__ . '/storage/logs/';

if (!is_dir($logDirectory)) {
    if (!mkdir($logDirectory, 0755, true) && !is_dir($logDirectory)) {
        throw new RuntimeException('Failed to create the log directory.');
    }
}

ini_set('log_errors', '1');
ini_set('error_log', $logDirectory . 'error.log');
error_reporting(E_ALL);

// REMINDER: Delete these before going live
ini_set('display_error', '0');
ini_set('display_startup_errors', 1);
