<?php
declare(strict_types=1);

namespace Core;

final class Token {
    public const KEY = 'csrf_key';

    public static function generate(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            return $_SESSION[self::KEY] ??= bin2hex(random_bytes(32));
        } finally {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
        }
    }

    public static function verify(?string $token): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            $currentToken = $_SESSION[self::KEY] ?? null;

            return is_string($currentToken) && $token !== null && hash_equals($currentToken, $token);
        } finally {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
        }
    }
}
