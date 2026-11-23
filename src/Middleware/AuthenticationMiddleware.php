<?php
declare(strict_types=1);

namespace Middleware;

use Closure;
use Core\MiddlewareInterface;
use Core\ViewRenderer;
use Helpers\Helper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticationMiddleware implements MiddlewareInterface {
    public function __construct(
        private readonly ViewRenderer $view,
        private readonly Helper $helper
    ) {}

    public function handle(Request $request, Closure $next): Response {
        $isSignedIn = $request->hasPreviousSession() && $request->getSession()->has('user');
        $security = $request->attributes->all('_security');
        $redirectPath = $security['redirect_authenticated'] ?? null;

        if (is_string($redirectPath) && $redirectPath !== '') {
            if (!$isSignedIn) {
                return $next($request);
            }

            return new RedirectResponse($request->getBasePath() . '/' . ltrim($redirectPath, '/'));
        }

        if ($isSignedIn) {
            return $next($request);
        }

        $status = Response::HTTP_UNAUTHORIZED;
        $responseData = $this->helper->responseData(
            status: $status,
            message: 'Please sign in to continue.',
            title: 'Sign-In Required',
            hint: 'Your session may have expired, or you may not be sign in. Sign in to continue.'
        );

        $path = $request->getPathInfo();

        if ($path === '/api' || str_starts_with($path, '/api/')) {
            return new JsonResponse($responseData, $status);
        }

        return new Response($this->view->renderError($responseData), $status);
    }
}
