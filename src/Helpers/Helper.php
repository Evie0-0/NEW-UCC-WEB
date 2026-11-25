<?php
declare(strict_types=1);

namespace Helpers;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class Helper {
    public function errorLog(Throwable $e): void {
        error_log(sprintf(
            '[%s] %s | %s:%d',
            $e::class,
            str_replace(["\r", "\n"], '', $e->getMessage()),
            basename($e->getFile()),
            $e->getLine()
        ));
    }

    public function responseData(int $status, string $message, ?string $title = null, mixed $data = null, array $additional = []): array {
        return [
            'result' => [
                'success' => $status >= 200 && $status < 300,
                'status' => $status,
                'title' => $title ?? Response::$statusTexts[$status] ?? 'Unknown Status',
                'message' => $message
            ],
            'data' => $data,
            ...$additional
        ];
    }

    public function escape(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function filterInt(mixed $input, int $minRange, bool $allowMaxRange = false, int $maxRange = 0): int|false {
        $options = ['min_range' => $minRange];

        if ($allowMaxRange) {
            $options['max_range'] = $maxRange;
        }

        return filter_var($input, FILTER_VALIDATE_INT, ['options' => $options]);
    }
}
