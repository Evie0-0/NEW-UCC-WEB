<?php
declare(strict_types=1);

namespace Core;

use Helpers\Helper;
use LogicException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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

        #[Autowire(service: 'logger.error')]
        private readonly LoggerInterface $errorLogger,
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

            return $this->run($request, $parameters);
        } catch (ResourceNotFoundException $e) {
            $this->errorLogger->notice('No route matched the requested path.', [
                'method' => $request->getMethod(),
                'path' => $request->getPathInfo(),
                'exception' => $e,
            ]);

            return $this->errorResponse(
                $request,
                $this->helper->responseData(
                    status: Response::HTTP_NOT_FOUND,
                    message: 'The requested resource could not be found.',
                    hint: 'Check the web address or try a different link.'
                )
            );
        } catch (MethodNotAllowedException $e) {
            $allowedMethods = $e->getAllowedMethods();

            $this->errorLogger->notice('The requested HTTP method is not allowed for this path.', [
                'method' => $request->getMethod(),
                'path' => $request->getPathInfo(),
                'allowed_methods' => $allowedMethods,
                'exception' => $e,
            ]);

            $response = $this->errorResponse(
                $request,
                $this->helper->responseData(
                    status: Response::HTTP_METHOD_NOT_ALLOWED,
                    message: 'The requested HTTP method is not allowed.',
                    hint: 'Try opening a page from a different link.'
                )
            );

            $response->headers->set('Allow', implode(', ', $allowedMethods));

            return $response;
        } catch (JsonException $e) {
            $this->errorLogger->warning('Rejected rejected because the JSON body could not be parsed.', [
                'method' => $request->getMethod(),
                'path' => $request->getPathInfo(),
                'route' => $request->attributes->get('_route'),
                'exception' => $e,
            ]);

            return $this->errorResponse(
                $request,
                $this->helper->responseData(
                    status: Response::HTTP_BAD_REQUEST,
                    message: 'Invalid JSON request body.',
                    hint: 'Reload the page and try again.'
                )
            );
        } catch (Throwable $e) {
            $this->errorLogger->error('Unhandled application exception.', [
                'method' => $request->getMethod(),
                'path' => $request->getPathInfo(),
                'route' => $request->attributes->get('_route'),
                'exception' => $e
            ]);

            return $this->errorResponse(
                $request,
                $this->helper->responseData(
                    status: Response::HTTP_INTERNAL_SERVER_ERROR,
                    message: 'An unexpected error occurred. Please try again later.'
                )
            );
        }
    }

    // Executes the controller after the middlewares
    private function dispatch(Request $request, array $parameters): Response {
        $handler = $parameters['_controller'] ?? null;

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

    // Runs middlewares first, controller after
    private function run(Request $request, array $parameters): Response {
        $stack = $parameters['_middleware'] ?? [];

        if (!is_array($stack)) {
            throw new LogicException('Invalid middleware definition.');
        }

        $next = fn(Request $request): Response => $this->dispatch($request, $parameters);

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

    // Legacy URL normalization
    private function normalizePath(Request $request): string {
        $path = $request->getPathInfo();

        if ($path === '/api'
            || str_starts_with($path, '/api/')
            || !in_array($request->getMethod(), ['GET', 'HEAD'], true)
        ) {
            return $path;
        }

        if (preg_match('~/index\.php\z~i', $path)) {
            return substr($path, 0, -strlen('index.php'));
        }

        return preg_replace('~\.php\z~i', '', $path) ?? $path;
    }

    // Show error based on request type
    private function errorResponse(Request $request, array $data): Response {
        $path = $request->getPathInfo();
        $status = $data['result']['status'];

        if ($path === '/api' || str_starts_with($path, '/api/')) {
            return new JsonResponse($data, $status);
        }

        return new Response($this->view->renderError($data), $status);
    }
}
