<?php
declare(strict_types=1);

namespace Controllers;

use Helpers\Helper;
use Services\UserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class UserController {
    public function __construct(
        private readonly UserService $service,
        private readonly Helper $helper
    ) {}

    public function login(Request $request): JsonResponse {
        $data = $request->getPayload()->all();

        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;

        if (!is_string($username) || !is_string($password)) {
            $status = Response::HTTP_UNPROCESSABLE_ENTITY;

            return new JsonResponse(
                $this->helper->responseData(
                    status: $status,
                    message: 'Username and password must be provided as text.',
                    title: 'Invalid Login Details'
                ),
                $status
            );
        }

        $result = $this->service->authenticate($username, $password, $request->getSession());

        return new JsonResponse($result, $result['result']['status']);
    }
}
