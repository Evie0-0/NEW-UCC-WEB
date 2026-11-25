<?php
declare(strict_types=1);

namespace Core;

use Helpers\Helper;
use LogicException;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Throwable;

final class Application {
    public function __construct(
        private readonly RouteCollection $routes,
        #[AutowireLocator('app.controller')]
        private readonly ContainerInterface $controllers,
        #[AutowireLocator('app.middleware')]
        private readonly ContainerInterface $middleware,
        private readonly ViewRenderer $view,
        private readonly Helper $helper
    ) {}

    public function handle(Request $request): Response {
        try {
            $context = new RequestContext();
            $context->fromRequest($request);

            $matcher = new UrlMatcher($this->routes, $context);
            $parameters = $matcher->match($this->normalizePath($request));

            $request->attributes->add($parameters);

            return $this->run($request);
        } catch (ResourceNotFoundException $e) {
            $this->helper->errorLog($e);

            return $this->errorResponse(
                $request,
                $this->helper->responseData(
                    status: Response::HTTP_NOT_FOUND,
                    message: 'The requested resource could not be found.',
                    additional: [
                        'hint' => 'Check the web address or try a different link.'
                    ]
                )
            );
        } catch (MethodNotAllowedException $e) {
            $this->helper->errorLog($e);

            $response = $this->errorResponse(
                $request,
                $this->helper->responseData(
                    status: Response::HTTP_METHOD_NOT_ALLOWED,
                    message: 'The requested HTTP method is not allowed.',
                    additional: [
                        'hint' => 'Try opening a page from a different link.'
                    ]
                )
            );

            $response->headers->set('Allow', implode(', ', $e->getAllowedMethods()));

            return $response;
        } catch (JsonException $e) {
            $this->helper->errorLog($e);

            return $this->errorResponse(
                $request,
                $this->helper->responseData(
                    status: Response::HTTP_BAD_REQUEST,
                    message: 'Invalid JSON request body.',
                    additional: [
                        'hint' => 'Reload the page and try again.'
                    ]
                )
            );
        } catch (Throwable $e) {
            $this->helper->errorLog($e);

            return $this->errorResponse(
                $request,
                $this->helper->responseData(
                    status: Response::HTTP_INTERNAL_SERVER_ERROR,
                    message: 'An unexpected error occurred. Please try again later.',
                    additional: [
                        'hint' => 'Try again later. If the problem continues, contact an administrator.'
                    ]
                )
            );
        }
    }

    // Runs middlewares first, controller after
    private function run(Request $request): Response {
        $stack = $request->attributes->get('_middlewares', []);

        if (!is_array($stack)) {
            throw new LogicException('Invalid middleware definition.');
        }

        $next = fn(Request $request): Response => $this->dispatch($request);

        foreach (array_reverse($stack) as $middlewareKey) {
            if (!is_string($middlewareKey)) {
                throw new LogicException('Invalid middleware identifier.');
            }

            $following = $next;

            $next = function (Request $request) use ($middlewareKey, $following): Response {
                $middleware = $this->middleware->get($middlewareKey);

                if (!($middleware instanceof MiddlewareInterface)) {
                    throw new LogicException('Invalid middleware implementation.');
                }

                return $middleware->handle($request, $following);
            };
        }

        return $next($request);
    }

    // Executes the controller after the middlewares
    private function dispatch(Request $request): Response {
        $handler = $request->attributes->get('_controller');

        if (!is_array($handler) || count($handler) !== 1) {
            throw new LogicException('Invalid controller definition.');
        }

        $class = array_key_first($handler);
        $method = $handler[$class];

        if (!is_string($class) || !is_string($method)) {
            throw new LogicException('Invalid controller definition.');
        }

        $controller = $this->controllers->get($class);

        if (!is_callable([$controller, $method])) {
            throw new LogicException('Invalid controller method.');
        }

        $response = $controller->$method($request);

        if (!$response instanceof Response) {
            throw new LogicException('Controller must return a Response.');
        }

        return $response;
    }

    // Legacy URL normalization
    private function normalizePath(Request $request): string {
        $path = $request->getPathInfo();

        if ($path === '/api' || str_starts_with($path, '/api/')) {
            return $path;
        }

        if (preg_match('~/index\.php\z~i', $path)) {
            return substr($path, 0, -strlen('index.php'));
        }

        return preg_replace('~\.php\z~i', '', $path) ?? $path;
    }

    // Changed responsedata into data for easy access for pages
    // Show error based on request type
    private function errorResponse(Request $request, array $responseData): Response {
        $path = $request->getPathInfo();
        $status = $responseData['result']['status'] ?? 500;

        if ($path === '/api' || str_starts_with($path, '/api/')) {
            return new JsonResponse($responseData, $status);
        }

        return new Response($this->view->render('error', $responseData), $status);
    }
}
