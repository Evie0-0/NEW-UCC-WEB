<?php
declare(strict_types=1);

namespace Core;

use Helpers\Helper;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;

final class ViewRenderer {
    public function __construct(
        private readonly string $viewsPath,
        private readonly string $basePath,
        private readonly array $siteConfig,
        private readonly Helper $helper,
        private readonly RequestStack $requestStack
    ) {}

    public function render(string $view, array $data = []): string {
        $data['site'] = array_replace($this->siteConfig, $data['site'] ?? []);
        $data['user'] ??= [];

        $request = $this->requestStack->getCurrentRequest();

        if (str_starts_with($view, 'admin/') && $request !== null && $request->hasPreviousSession()) {
            $data['user'] = $request->getSession()->get('user', []);
        }

        return $this->getContent($view, $data);
    }

    public function renderError(array $data = []): string {
        return $this->render('errors/error', $data);
    }

    public function partial(string $type, string $view, array $data = []): string {
        return $this->getContent($type . '/partials/' . $view, $data);
    }

    public function asset(string $path): string {
        return rtrim($this->basePath, '/') . '/assets/' . ltrim($path, '/');
    }

    private function getContent(string $view, array $data): string {
        if (!preg_match('~\A[a-zA-Z0-9_-]+(?:/[a-zA-Z0-9_-]+)*\z~D', $view)) {
            throw new InvalidArgumentException("Invalid view: {$view}.");
        }

        $file = rtrim($this->viewsPath, '/') . '/' . $view . '.php';

        if (!is_file($file)) {
            throw new RuntimeException("View to render not found: {$view}");
        }

        $bufferLevel = ob_get_level();

        if (!ob_start()) {
            throw new RuntimeException('Failed to start output buffering.');
        }

        try {
            require $file;

            if (ob_get_level() !== $bufferLevel + 1) {
                throw new RuntimeException('View output buffers are unbalanced.');
            }

            $html = ob_get_clean();

            if ($html === false) {
                throw new RuntimeException('Failed to retrieve rendered view.');
            }

            return $html;
        } catch (Throwable $e) {
            while (ob_get_level() > $bufferLevel) {
                if (!ob_end_clean()) {
                    break;
                }
            }

            throw $e;
        }
    }
}
