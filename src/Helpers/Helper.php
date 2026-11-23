<?php
declare(strict_types=1);

namespace Helpers;

use Symfony\Component\HttpFoundation\Response;

final class Helper {
    public function responseData(int $status, string $message, ?string $title = null, ?string $hint = null, mixed $data = null, array $additional = []): array {
        return [
            'result' => [
                'success' => $status >= 200 && $status < 300,
                'status' => $status,
                'title' => $title ?? Response::$statusTexts[$status] ?? 'Unknown Status',
                'message' => $message,
                'hint' => $hint ?? 'Try again later. If the problem continues, contact an administrator.'
            ],
            'data' => $data,
            'additional' => $additional
        ];
    }

    public function escape(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
