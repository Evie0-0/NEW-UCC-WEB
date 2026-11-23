<?php
declare(strict_types=1);

$env = $_ENV['APP_ENV'] ?? 'production';

$allowedEnvironments = [
    'development',
    'production',
];

if (!in_array($env, $allowedEnvironments, true)) {
    throw new RuntimeException('Invalid application environment.');
}

return [
    'name' => $ENV['APP_NAME'] ?? 'University of Caloocan City',
    'base_path' => $_ENV['APP_BASE_PATH'] ?? '',
    'site' => [
        'public_title_suffix' => $_ENV['APP_PUBLIC_TITLE_SUFFIX'] ?? $_ENV['APP_NAME'] ?? 'University of Caloocan City',
        'admin_title_suffix' => $_ENV['APP_ADMIN_TITLE_SUFFIX'] ?? $_ENV['APP_NAME'] ?? 'Content Management System',
    ],
    'env' => $env,
    'debug' => $env === 'development'
        && filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL),
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Asia/Manila',
    'maintenance' => [
        'public' => filter_var($_ENV['APP_PUBLIC_MAINTENANCE'] ?? false, FILTER_VALIDATE_BOOL),
        'admin' => filter_var($_ENV['APP_ADMIN_MAINTENANCE'] ?? false, FILTER_VALIDATE_BOOL),
    ],
    'maintenance_message' => 'The website is currently under maintenance. Please try again later.',
];
