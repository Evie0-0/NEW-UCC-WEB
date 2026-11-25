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

final class CsrfProtectionMiddleware implements MiddlewareInterface {
    public function __construct(
        private readonly ViewRenderer $view,
        private readonly Helper $helper
    ) {}

    public function handle(Request $request, Closure $next): Response {
        $submittedToken = $request->headers->get('Csrf-Token');

        if ($submittedToken !== null && $request->hasPreviousSession()) {
            $sessionToken = $request->getSession()->get('csrf_token');

            if (is_string($sessionToken) && $sessionToken !== '' && hash_equals($sessionToken, $submittedToken)) {
                return $next($request);
            }
        }

        $status = Response::HTTP_FORBIDDEN;
        $responseData = $this->helper->responseData(
            status: $status,
            message: 'Please reload the page and try again.',
            title: 'Request Verification Failed',
            additional: [
                'hint' => 'Reload the page and try again.'
            ]
        );

        $path = $request->getPathInfo();

        if ($path === '/api' || str_starts_with($path, '/api/')) {
            return new JsonResponse($responseData, $status);
        }

        return new Response($this->view->render('error', $responseData), $status);
    }
}
