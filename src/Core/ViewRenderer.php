<?php
declare(strict_types=1);

namespace Core;

use Helpers\Helper;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class ViewRenderer {
    public function __construct(
        private readonly string $basePath,
        private readonly array $siteConfig,
        private readonly Helper $helper
    ) {}

    public function render(string $view, array $responseData = []): string {
        $responseData['site'] = array_replace($this->siteConfig, $responseData['site'] ?? []);

        return $this->getContent($view, $responseData);
    }

    public function partial(string $type, string $view, array $responseData = []): string {
        return $this->getContent($type . '/partials/' . $view, $responseData);
    }

    public function asset(string $type, string $path): string {
        return '/assets/' . $type . '/' . $path;
    }

    // Changed responsedata into data for easy access for pages
    private function getContent(string $view, array $responseData): string {
        if (!preg_match('~\A[a-zA-Z0-9_-]+(?:/[a-zA-Z0-9_-]+)*\z~D', $view)) {
            throw new InvalidArgumentException("Invalid view: {$view}.");
        }

        $file = $this->basePath . '/resources/views/' . $view . '.php';

        if (!is_file($file)) {
            throw new RuntimeException("View to render not found: {$view}");
        }

        $bufferLevel = ob_get_level();

        if (!ob_start()) {
            throw new RuntimeException('Failed to start output buffering.');
        }

        try {
            // Name of data used in html pages
            $data = $responseData;

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
