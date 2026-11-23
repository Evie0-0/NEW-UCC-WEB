<?php
declare(strict_types=1);

namespace Controllers;

use Core\ViewRenderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PageController {
    public function __construct(
        private readonly ViewRenderer $views
    ) {}

    public function adminLogin(Request $request): Response {
        $session = $request->getSession();
        $token = $session->get('csrf_token');

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $session->set('csrf_token', $token);
        }

        $data = $request->attributes->all('_data');
        $data['csrf_token'] = $token;

        $response = new Response($this->views->render('admin/login', $data));
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    public function adminDashboard(Request $request): Response {
        return new Response($this->views->render('admin/dashboard', $request->attributes->all('_data')));
    }
}
