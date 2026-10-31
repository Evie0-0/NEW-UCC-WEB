<?php
declare(strict_types=1);

namespace Core;

final class Token {
    public static function generate(): string {
        if (!isset($_SESSION['csrf_key'])) {
            $_SESSION['csrf_key'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_key'];
    }

    public static function verify(?string $token): bool {
        if (!isset($_SESSION['csrf_key']) || $token === null) {
            return false;
        }

        return hash_equals($_SESSION['csrf_key'], $token);
    }
}
