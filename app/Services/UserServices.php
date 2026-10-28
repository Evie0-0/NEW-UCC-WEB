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
        $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
        $password = is_string($_POST['password'] ?? null) ? trim($_POST['password']) : '';

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
        $_SESSION[self::USERNAME_KEY] = $fetchedUser['username'];
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

    public function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = validateInt($_SESSION[self::USER_ID_KEY] ?? null, 1, null);
        $username = $_SESSION[self::USERNAME_KEY] ?? null;

        $isAuthenticated = $userId !== false
            && is_string($username)
            && trim($username) !== '';

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $cookieParams = session_get_cookie_params();

            setcookie(session_name(), '', time() - 42000, $cookieParams['path'], $cookieParams['domain'], $cookieParams['secure'], $cookieParams['httponly']);
        }

        session_destroy();

        if (!$isAuthenticated) {
            jsonResponse(200, 'You have been logged out successfully.', [
                'redirect' => 'login.php'
            ]);
        }

        try {
            $data = [
                'action' => 'LOGOUT',
                'description' => "User $username logged out.",
                'entity_type' => 'User',
                'entity_id' => $userId,
                'user_id' => $userId
            ];

            $repo = new AuditTrailRepository(Database::connect());
            $repo->insertItem($data);
        } catch (Throwable $e) {
            throw $e;
        }

        jsonResponse(200, 'You have been logged out successfully.', [
            'redirect' => 'login.php'
        ]);
    }

    public static function isAuthenticated(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            $username = $_SESSION[self::USERNAME_KEY] ?? null;

            return validateInt($_SESSION[self::USER_ID_KEY] ?? null, 1, null) !== false
                && is_string($username)
                && trim($username) !== '';
        } finally {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
        }
    }

    /** @param list<string> $allowedRoles */
    public static function isAuthorized(array $allowedRoles = []): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($allowedRoles === []) {
            $allowedRoles = [self::SUPERADMIN_KEY, self::DEAN_KEY, self::PROGRAM_HEAD_KEY];
        }

        try {
            $role = $_SESSION[self::ROLE_KEY] ?? null;

            return validateInt($_SESSION[self::USER_ID_KEY] ?? null, 1, null) !== false
                && is_string($role)
                && trim($role) !== ''
                && in_array($role, $allowedRoles, true);
        } finally {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
        }
    }
}
