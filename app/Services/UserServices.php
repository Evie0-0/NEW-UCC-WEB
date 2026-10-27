<?php
declare(strict_types=1);

namespace Serv;

use Core\Database;
use Repo\AuditTrailRepository;
use Repo\UserRepository;
use Throwable;

final class UserServices {
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

        $_SESSION['user_id'] = $fetchedUser['id'];
        $_SESSION['username'] = $fetchedUser['username'];
        $_SESSION['role'] = $fetchedUser['role'];
        $_SESSION['unit_id'] = $fetchedUser['unit_id'];
        $_SESSION['program_id'] = $fetchedUser['program_id'];

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

    public static function isAuthenticated(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $isLoggedIn = isset($_SESSION['user_id']);

        session_write_close();

        return $isLoggedIn;
    }

    /** @param list<string> $allowedRoles */
    public static function isAuthorized(array $allowedRoles): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $role = $_SESSION['role'] ?? null;

        session_write_close();

        return is_string($role) && in_array($role, $allowedRoles, true);
    }

    public static function getUsername(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $username = $_SESSION['username'] ?? 'Username not found';

        session_write_close();

        return $username;
    }
}
