<?php
declare(strict_types=1);

return [
    'driver' => 'mysql',
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'database' => $_ENV['DB_DATABASE'] ?? throw new RuntimeException('Database name is not configured'),
    'username' => $_ENV['DB_USERNAME'] ?? throw new RuntimeException('Database username is not configured.'),
    'password' => $_ENV['DB_PASSWORD'] ?? throw new RuntimeException('Database password is not configured.'),
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]
];
