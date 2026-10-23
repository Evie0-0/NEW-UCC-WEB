<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';


function e(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function logError(Throwable $exception, string $message = 'An unexpected error occurred', string $level = 'ERROR', array $context = []): void {
    $clean = static function (string $value): string {
        return str_replace(["\r", "\n", '|'], [' ', ' ', '/'], $value);
    };

    $message = $clean($message);
    $exceptionMessage = $clean($exception->getMessage());

    if ($exceptionMessage !== '') {
        $message .= ': ' . $exceptionMessage;
    }

    $contextEntries = [];

    foreach ($context as $key => $value) {
        if (!is_scalar($value) && $value !== null) {
            continue;
        }

        $key = preg_replace('/[^a-zA-Z0-9_.-]/', '_', (string) $key);

        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif ($value === null) {
            $value = 'null';
        }

        $value = $clean((string) $value);
        $contextEntries[] = $key . '=' . $value;
    }

    $previous = $exception->getPrevious();

    while ($previous !== null) {
        $previousMessage = $clean($previous->getMessage());
        $previousFile = $clean($previous->getFile());
        $contextEntries[] = sprintf(
            'caused_by=%s: %s @ %s:%d',
            get_class($previous),
            $previousMessage,
            $previousFile,
            $previous->getLine()
        );
        $previous = $previous->getPrevious();
    }

    $contextString = $contextEntries !== [] ? implode(', ', $contextEntries) : '-';
    $entry = sprintf(
           "[%s] [%s] [%s] %s | %s:%d | %s",
           date(DATE_ATOM),
           strtoupper($clean($level)),
           get_class($exception),
           $message,
           $clean($exception->getFile()),
           $exception->getLine(),
           $contextString
    );

    error_log($entry);
}

function jsonResponse(int $statusCode = 200, string $message = 'An unexpected error occured.', array $data = []): never {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'message' => $message,
        'data' => $data,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );

    exit;
}

function jsonDataResponse(array $data = []): never {
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );

    exit;
}
