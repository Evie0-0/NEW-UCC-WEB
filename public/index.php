<?php
declare(strict_types=1);

use Core\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

$container = require dirname(__DIR__) . '/bootstrap/app.php';

$app = $container->get(Application::class);

$request = Request::createFromGlobals();

$request->setSessionFactory(static fn () => new Session(
    new NativeSessionStorage([
        'name' => 'UCCSESSID',
        'cookie_secure' => $request->isSecure(),
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ])
));

$response = $app->handle($request);

if ($request->attributes->get('no_store') === true)  {
    $response->headers->addCacheControlDirective('no-store');
}

$response->prepare($request);
$response->send();
