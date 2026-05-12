<?php

namespace App\Logging;

use Monolog\LogRecord;

class SensitiveDataRedactionProcessor
{
    /**
     * @var array<int, string>
     */
    private array $sensitiveKeys = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'access_token',
        'refresh_token',
        'api_key',
        'authorization',
        'secret',
        'client_secret',
        'db_password',
        'mail_password',
    ];

    /**
     * Redact secrets from structured log payloads before they are written.
     *
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            message: $this->redactString($record->message),
            context: $this->sanitize($record->context),
            extra: $this->sanitize($record->extra),
        );
    }

    /**
     * @param  mixed  $value
     * @return mixed
     */
    private function sanitize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return is_string($value) ? $this->redactString($value) : $value;
        }

        foreach ($value as $key => $item) {
            if ($this->isSensitiveKey((string) $key)) {
                $value[$key] = '[REDACTED]';

                continue;
            }

            $value[$key] = $this->sanitize($item);
        }

        return $value;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);

        foreach ($this->sensitiveKeys as $sensitiveKey) {
            if (str_contains($normalized, $sensitiveKey)) {
                return true;
            }
        }

        return false;
    }

    private function redactString(string $value): string
    {
        return preg_replace(
            '/(password|token|secret|authorization)(\s*[:=]\s*)([^,\s]+)/i',
            '$1$2[REDACTED]',
            $value,
        ) ?? $value;
    }
}
