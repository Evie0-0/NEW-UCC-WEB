<?php
declare(strict_types=1);

use Controllers\PageController;
use Controllers\UserController;
use Core\Application;
use Core\Database;
use Core\ViewRenderer;
use Helpers\Helper;
use Middleware\AuthenticationMiddleware;
use Middleware\AuthorizationMiddleware;
use Middleware\CsrfProtectionMiddleware;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Repositories\UserRepository;
use Services\UserService;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouteCollection;

$builder = new ContainerBuilder();

# Track this config for cache invalidation
$builder->addResource(new FileResource(__FILE__));


$builder->register('logger.app', Logger::class)->setSynthetic(true)->setPublic(true);
$builder->register('logger.error', Logger::class)->setSynthetic(true)->setPublic(true);
$builder->setAlias(LoggerInterface::class, 'logger.app');

$builder->register(Database::class)->setSynthetic(true)->setPublic(true);
$builder->register(PDO::class, Database::class)->setFactory([
    new Reference(Database::class),
    'connect'
]);

$builder->register(RequestStack::class, RequestStack::class)->setPublic(true);

$builder->register(RouteCollection::class)->setSynthetic(true)->setPublic(true);

$builder->register(Helper::class, Helper::class)->setPublic(true);

$builder->register(Application::class, Application::class)->setAutowired(true)->setPublic(true);

$builder->register(ViewRenderer::class)->setSynthetic(true)->setPublic(true);

$builder->register(AuthenticationMiddleware::class, AuthenticationMiddleware::class)->setAutowired(true)->setPublic(true)
    ->addTag('app.middleware');
$builder->register(AuthorizationMiddleware::class, AuthorizationMiddleware::class)->setAutowired(true)->setPublic(true)
    ->addTag('app.middleware');
$builder->register(CsrfProtectionMiddleware::class, CsrfProtectionMiddleware::class)->setAutowired(true)->setPublic(true)
    ->addTag('app.middleware');

$builder->register(PageController::class, PageController::class)->setAutowired(true)->setPublic(true)
    ->addTag('app.controller');

    $builder->register(UserController::class, UserController::class)->setAutowired(true)->setPublic(true)
    ->addTag('app.controller');
$builder->register(UserService::class, UserService::class)->setAutowired(true);
$builder->register(UserRepository::class, UserRepository::class)->setAutowired(true);

return $builder;
