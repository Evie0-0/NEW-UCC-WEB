<?php
declare(strict_types=1);

namespace Middleware;

use Closure;
use Core\MiddlewareInterface;
use Core\ViewRenderer;
use Helpers\Helper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthorizationMiddleware implements MiddlewareInterface {
    public function __construct(
        private readonly ViewRenderer $view,
        private readonly Helper $helper
    ) {}

    public function handle(Request $request, Closure $next): Response {
        $user = $request->getSession()->get('user', []);
        $role = $user['role'] ?? null;
        $security = $request->attributes->all('_security');
        $allowedRoles = $security['allowed_roles'] ?? [];

        if ($user !== [] && $role !== null && in_array($role, $allowedRoles, true)) {
            return $next($request);
        }

        $status = Response::HTTP_FORBIDDEN;
        $responseData = $this->helper->responseData(
            status: $status,
            message: 'Your account does not have permission to access this resource.',
            title: 'Access Denied',
            hint: 'If you believe you should have access, contact an administrator.'
        );

        $path = $request->getPathInfo();

        if ($path === '/api' || str_starts_with($path, '/api/')) {
            return new JsonResponse($responseData, $status);
        }

        return new Response($this->view->renderError($responseData), $status);
    }
}
