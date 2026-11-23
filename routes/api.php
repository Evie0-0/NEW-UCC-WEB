<?php
declare(strict_types=1);

use Controllers\UserController;
use Middleware\CsrfProtectionMiddleware;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

$routes = new RouteCollection();

$routes->add('api.user.login', new Route(
    '/user/login',
    [
        '_controller' => [ UserController::class => 'login' ],
        '_middleware' => [ CsrfProtectionMiddleware::class ],
        '_additional' => [
            'no_store' = true
        ]
    ],
    methods: [ 'POST' ]
));

return $routes;
