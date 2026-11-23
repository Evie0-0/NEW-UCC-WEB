<?php
declare(strict_types=1);

namespace Controllers;

use Core\ViewRenderer;
use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PageController {
    public function __construct(
        private readonly ViewRenderer $view
    ) {}

    public function adminLogin(Request $request): Response {
        return $this->showPage($request);
    }

    public function adminDashboard(Request $request): Response {
        return $this->showPage($request);
    }

    private function showPage(Request $request): Response {
        $view = $request->attributes->get('_view');

        if (!is_string($view) || $view === '') {
            throw new LogicException('The page route must define a view.');
        }

        $data = $request->attributes->all('_data');
        $security = $request->attributes->all('_security');

        if (($security['include_csrf_token'] ?? false) === true) {
            $session = $request->getSession();
            $token = $session->get('csrf_token');

            if (!is_string($token) || $token === '') {
                $token = bin2hex(random_bytes(32));
                $session->set('csrf_token', $token);
            }

            $data['csrf_token'] = $token;
        }

        return new Response($this->view->render($view, $data));
    }
}
