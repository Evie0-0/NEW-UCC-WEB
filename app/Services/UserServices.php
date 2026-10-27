<?php
declare(strict_types=1);

namespace Serv;

use Core\Database;
use Repo\AuditTrailRepository;
use Repo\UserRepository;
use Throwable;

final class UserServices {
    public const SUPERADMIN_KEY = 'superadmin';
    public const DEAN_KEY = 'dean';
    public const PROGRAM_HEAD_KEY = 'program head';
    public const USER_ID_KEY = 'user_id';
    public const USERNAME_KEY = 'username';
    public const ROLE_KEY = 'role';
    public const UNIT_ID_KEY = 'unit_id';
    public const PROGRAM_ID_KEY = 'program_id';

    public function login(): void {
        $username = trim(validateString($_POST['username'] ?? '', 'Username'));
        $password = validateString($_POST['password'] ?? '', 'Password');

        if ($username === '') {
            jsonResponse(422, 'Username cannot be empty.');
        }

        if ($password === '') {
            jsonResponse(422, 'Password cannot be empty.');
        }

        $repo = new UserRepository(Database::connect());
        $fetchedUser = $repo->fetchItemByUsername($username);
        $returnedPassword = null;

        if ($fetchedUser === false) {
            jsonResponse(404, 'User does not exist');
        }

        $returnedPassword = $fetchedUser['password'];

        if (!password_verify($password, $returnedPassword)) {
            jsonResponse(401, 'Incorrect username or password.', [
                'redo_password' => true
            ]);
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        session_regenerate_id(true);

        $_SESSION[self::USER_ID_KEY] = $fetchedUser['id'];
        $_SESSION[self::USERNAME_KEY]] = $fetchedUser['username'];
        $_SESSION[self::ROLE_KEY] = $fetchedUser['role'];
        $_SESSION[self::UNIT_ID_KEY] = $fetchedUser['unit_id'];
        $_SESSION[self::PROGRAM_ID_KEY] = $fetchedUser['program_id'];

        session_write_close();

        $userId = $fetchedUser['id'];

        try {
            $data = [
                'action' => 'LOGIN',
                'description' => "User $username logged in.",
                'entity_type' => 'User',
                'entity_id' => $userId,
                'user_id' => $userId
            ];

            $repo = new AuditTrailRepository(Database::connect());
            $repo->insertItem($data);
        } catch (Throwable $e) {
            throw $e;
        }

        jsonResponse(200, 'Successful, logging in.', [
            'redirect' => 'index.php'
        ]);
    }

    public static function getUsername(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            $username = $_SESSION[self::USERNAME_KEY] ?? null;

            return is_string($username) ? $username : '';
        } finally {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
        }
    }

    public static function getRole(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            $role = $_SESSION[self::ROLE_KEY] ?? null;

            return is_string($role) ? $role : '';
        } finally {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
        }
    }

    public static function isAuthenticated(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            return isset($_SESSION[self::USER_ID_KEY]);
        } finally {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
        }
    }

    /** @param list<string> $allowedRoles */
    public static function isAuthorized(array $allowedRoles = [self::SUPERADMIN_KEY, self::DEAN_KEY, self::PROGRAM_HEAD_KEY]): bool {
        return in_array(self::getRole(), $allowedRoles, true);
    }
}
