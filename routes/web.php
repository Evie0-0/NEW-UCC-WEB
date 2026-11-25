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
        '_controller' => [PageController::class => 'adminLogin'],
        'view' => 'admin/login',
        '_middlewares' => [AuthenticationMiddleware::class],
        '_security' => [
            'redirect_authenticated' => '/admin/',
            'include_csrf_token' => true
        ],
        '_data' => [
            'site' => [
                'page_title' => 'Login'
            ],
            'files' => [
                'js' => 'js/login.js'
            ],
        ],
        'no_store' => true,
        'include_user_info' => true
    ],
    methods: ['GET']
));

$routes->add('admin.dashboard', new Route(
    '/admin/',
    [
        '_controller' => [PageController::class => 'adminDashboard'],
        'view' => 'admin/dashboard',
        '_middlewares' => [AuthenticationMiddleware::class, AuthorizationMiddleware::class],
        '_security' => [
            'allowed_roles' => ['admin', 'superadmin']
        ],
        '_data' => [
            'site' => [
                'page_title' => 'Dashboard'
            ],
        ],
        'no_store' => true,
        'include_user_info' => true
    ],
    methods: ['GET']
));

$routes->add('admin.post', new Route(
 '/admin/post',
 [
     '_controller' => [ PageController::class => 'adminPost' ],
     'view' => 'admin/post/index',
     '_middlewares' => [ AuthenticationMiddleware::class, AuthorizationMiddleware::class ],
     '_security' => [
         'allowed_roles' => ['admin', 'superadmin']
     ],
     '_data' => [
         'site' => [
             'page_title' => 'Post'
         ],
         'files' => [
             'js' => 'js/post.js'
         ]
     ],
     'no_store' => true,
     'include_user_info' => true
 ],
 methods: ['GET']
));

return $routes;
