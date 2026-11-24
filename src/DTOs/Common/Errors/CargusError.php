<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Common\Errors;

final readonly class CargusError
{
    /**
     * @param array<string, mixed> $raw
     * @param array<string, string[]> $errors
     */
    public function __construct(
        public int $code,
        public string $message,
        public string $id,
        public array $raw,
        public array $errors = [],
        public ?string $context = null,
        public ?string $component = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, int $fallbackCode = 0): self
    {
        $message = $data['message'] ?? $data['Message'] ?? $data['error'] ?? $data['Error'] ?? null;

        return new self(
            code: (int) ($data['code'] ?? $data['Code'] ?? $fallbackCode),
            message: is_string($message) && $message !== '' ? $message : 'Cargus API error.',
            id: (string) ($data['id'] ?? $data['Id'] ?? ''),
            raw: $data,
            errors: self::errorsFrom($data['errors'] ?? $data['Errors'] ?? []),
            context: isset($data['context']) ? (string) $data['context'] : null,
            component: isset($data['component']) ? (string) $data['component'] : null,
        );
    }

    public function isValidationRelated(): bool
    {
        if ($this->code === 400 || $this->code === 422) {
            return true;
        }

        if ($this->errors !== []) {
            return true;
        }

        $message = strtolower($this->message);

        foreach (['invalid', 'required', 'mandatory', 'missing', 'duplicate', 'already', 'not valid', 'validation', 'must be'] as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }

    public function isAuthenticationRelated(): bool
    {
        if ($this->code === 401 || $this->code === 403) {
            return true;
        }

        $message = strtolower($this->message);

        return str_contains($message, 'auth')
            || str_contains($message, 'password')
            || str_contains($message, 'credential')
            || str_contains($message, 'token')
            || str_contains($message, 'unauthenticated')
            || str_contains($message, 'logged user')
            || str_contains($message, 'access rights');
    }

    /**
     * @return array<string, string[]>
     */
    private static function errorsFrom(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $errors = [];

        foreach ($raw as $field => $messages) {
            if (!is_string($field) || !is_array($messages)) {
                continue;
            }

            $errors[$field] = array_values(array_filter($messages, static fn(mixed $message): bool => is_string($message)));
        }

        return $errors;
    }
}
