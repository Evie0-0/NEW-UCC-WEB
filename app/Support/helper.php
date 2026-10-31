<?php
declare(strict_types=1);
use Symfony\Polyfill\Php80\Php80;

function e(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// Logs unexpected exceptions for debugging or diagnostics
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

// Returns a status code and message with optional data (for alerts)
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

function validateDate(mixed $value): string|false {
    if (!is_string($value)) {
        return false;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('Asia/Manila'));
    $errors = DateTimeImmutable::getLastErrors();

    if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $value) {
        return false;
    }

    return $date->format('Y-m-d');
}

function validateString(mixed $value, string $field): string {
    if (!is_string($value)) {
        jsonResponse(422, "$field must be a valid string.");
    }

    return $value;
}

function validateInt(mixed $value, ?int $min = null, ?int $max = null): int|false {
    $options = [];

    if ($min !== null) {
        $options['min_range'] = $min;
    }

    if ($max !== null) {
        $options['max_range'] = $max;
    }

    return filter_var($value, FILTER_VALIDATE_INT, [
        'options' => $options
    ]);
}

function validateBool(mixed $value): bool {
    return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
}

function isSuperadmin(): bool {
    return (isset($_SESSION['user_id'], $_SESSION['role']) && $_SESSION['role'] === 'superadmin');
}

function isInAllowedRoles(): bool {
    return (isset($_SESSION['user_id'], $_SESSION['role']) && in_array($_SESSION['role'], ['superadmin', 'dean', 'program head'], true));
}
