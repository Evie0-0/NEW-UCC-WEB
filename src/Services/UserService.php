<?php
declare(strict_types=1);

namespace Services;

use Helpers\Helper;
use PDO;
use Repositories\UserRepository;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Throwable;

final class UserService {
    public function __construct(
        private readonly UserRepository $repository,
        private readonly AuditLogService $auditLogService,
        private readonly PDO $connection,
        private readonly Helper $helper
    ) {}

    public function authenticate(string $username, string $password, SessionInterface $session): array {
        $username = trim($username);

        if ($username === '' || $password === '') {
            return $this->helper->responseData(
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
                message: 'Username or password cannot be empty.',
                title: 'Missing Login Details'
            );
        }

        $fetchedUser = $this->repository->getItemByUsername($username);

        if ($fetchedUser === false || !password_verify($password, $fetchedUser['password'])) {
            return $this->helper->responseData(
                status: Response::HTTP_NOT_FOUND,
                message: 'Invalid username or password.',
                title: 'Login Failed',
                additional:  [
                    'redo_password' => true,
                ]
            );
        }

        $session->start();

        if (!$session->migrate(true)) {
            throw new RuntimeException('Failed to regenerate the session ID.');
        }

        $session->set('user', [
            'id' => $fetchedUser['id'],
            'username' => $fetchedUser['username'],
            'role' => $fetchedUser['role'],
        ]);
        $session->save();

        $userId = $fetchedUser['id'];

        $this->connection->beginTransaction();

        try {
            $this->repository->updateLastLoggedIn($userId);

            $this->auditLogService->record(
                action: 'Login',
                description: 'User logged in.',
                entityType: 'User',
                entityId: $userId,
                actorUserId: $userId
            );

            $this->connection->commit();
        } catch (Throwable $e) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $e;
        }

        return $this->helper->responseData(
            status: Response::HTTP_OK,
            message: 'You`re now logged in.',
            title: 'Logged In Successfully',
            additional: [
                'redirect' => '/admin/',
            ]
        );
    }
}
