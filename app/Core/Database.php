<?php
declare(strict_types=1);

namespace Core;

use PDO;

final class Database {
    public static function connect(): PDO {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $name = $_ENV['DB_NAME'] ?? 'ucc';
        $user = $_ENV['DB_USERNAME'] ?? 'mky';
        $password = $_ENV['DB_PASSWORD'] ?? 'admin';

        $pdo = new PDO('mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4', $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec("SET time_zone = '+08:00'");

        return $pdo;
    }
}
