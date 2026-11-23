<?php
declare(strict_types=1);

use Controllers\PageController;
use Middleware\AuthenticationMiddleware;
use Middleware\AuthorizationMiddleware;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

$routes = new RouteCollection();

$routes->add('admin.login', new Route(
    '/admin/login',
    [
        '_controller' => [ PageController::class => 'adminLogin' ],
        '_view' => 'admin/login',
        '_middleware' => [ AuthenticationMiddleware::class ],
        '_security' => [
            'redirect_authenticated' => '/admin/',
            'include_csrf_token' => true,
            'no_store' => true
        ],
        '_data' => [
            'site' => [
                'page_title' => 'Sign In'
            ],
            'files' => [
                'js' => 'admin/js/login.js'
            ]
        ]
    ],
    methods: [ 'GET' ]
));

// Add a way to add data here for renderfile
$routes->add('admin.dashboard', new Route(
    '/admin/',
    [
        '_controller' => [ PageController::class => 'adminDashboard' ],
        '_view' => 'admin/dashboard',
        '_middleware' => [ AuthenticationMiddleware::class, AuthorizationMiddleware::class ],
        '_security' => [
            'allowed_roles' => [ 'admin', 'superadmin' ],
            'no_store' => true
        ],
        '_data' => [
            'site' => [
                'page_title' => 'Dashboard'
            ]
        ],
    ],
    methods: ['GET']
));

return $routes;
