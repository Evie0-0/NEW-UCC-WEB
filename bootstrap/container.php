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
use Repositories\AuditLogRepository;
use Repositories\UserRepository;
use Services\AuditLogService;
use Services\UserService;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Routing\RouteCollection;

$builder = new ContainerBuilder();

# Track this config for cache invalidation
$builder->addResource(new FileResource(__FILE__));

$builder->register(Database::class)->setSynthetic(true)->setPublic(true);
$builder->register(PDO::class, Database::class)->setFactory([
    new Reference(Database::class),
    'connect'
]);

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

$builder->register(AuditLogService::class, AuditLogService::class)->setAutowired(true);
$builder->register(AuditLogRepository::class, AuditLogRepository::class)->setAutowired(true);

return $builder;
