<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Support;

use Daika7ana\Cargus\DTOs\Common\Errors\CargusError;
use Daika7ana\Cargus\Exceptions\AuthenticationException;
use Daika7ana\Cargus\Exceptions\UnexpectedStatusException;
use Daika7ana\Cargus\Exceptions\ValidationException;
use JsonException;
use Psr\Http\Message\ResponseInterface;

final class ApiResponseMapper
{
    /**
     * @param int|list<int> $expectedStatus
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     */
    public static function decodeJson(ResponseInterface $response, int|array $expectedStatus = 200): mixed
    {
        self::assertStatus($response, $expectedStatus);

        try {
            return JsonHelper::decode((string) $response->getBody());
        } catch (JsonException $e) {
            throw new UnexpectedStatusException(
                $response,
                'Response body is not valid JSON: ' . $e->getMessage(),
                retryAfter: self::retryAfter($response),
                error: self::errorFromResponse($response),
            );
        }
    }

    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     */
    public static function document(ResponseInterface $response, int $expectedStatus = 200): Document
    {
        self::assertStatus($response, $expectedStatus);

        return Document::fromResponse($response);
    }

    /**
     * @param int|list<int> $expectedStatus
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     */
    public static function assertStatus(ResponseInterface $response, int|array $expectedStatus): void
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode === 401) {
            throw new AuthenticationException(
                message: self::failureMessage($response) ?? 'Authentication failed.',
                response: $response,
                retryAfter: self::retryAfter($response),
                error: self::errorFromResponse($response),
            );
        }

        if ($statusCode === 400 || $statusCode === 422) {
            throw new ValidationException(
                errors: self::errorsFromResponse($response),
                message: self::failureMessage($response) ?? 'Validation failed',
                response: $response,
                retryAfter: self::retryAfter($response),
                error: self::errorFromResponse($response),
            );
        }

        if (!in_array($statusCode, (array) $expectedStatus, true)) {
            throw new UnexpectedStatusException(
                $response,
                self::failureMessage($response) ?? "Unexpected HTTP status {$statusCode}",
                retryAfter: self::retryAfter($response),
                error: self::errorFromResponse($response),
            );
        }
    }

    public static function retryAfter(ResponseInterface $response): ?int
    {
        $value = trim($response->getHeaderLine('Retry-After'));

        return ctype_digit($value) ? (int) $value : null;
    }

    /**
     * Decodes an OpenAPI `string` + `format: byte` body; leaves non-base64 payloads as-is.
     */
    public static function decodeByteString(string $payload): string
    {
        $decoded = base64_decode($payload, true);

        return $decoded === false ? $payload : $decoded;
    }

    /**
     * Decodes an OpenAPI `string` + `format: byte` body, failing loudly when
     * the API answers that no document exists.
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     */
    public static function byteString(ResponseInterface $response, string $missingMessage): string
    {
        if ($response->getStatusCode() === 204) {
            throw new UnexpectedStatusException($response, $missingMessage, error: self::errorFromResponse($response));
        }

        $payload = self::decodeJson($response);

        if (! is_string($payload) || $payload === '') {
            throw new UnexpectedStatusException($response, $missingMessage, error: self::errorFromResponse($response));
        }

        return self::decodeByteString($payload);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listOfArrays(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            if (is_array($item)) {
                $items[] = $item;
            }
        }

        return $items;
    }

    private static function errorFromResponse(ResponseInterface $response): CargusError
    {
        return CargusError::fromArray(self::decodeBody($response), $response->getStatusCode());
    }

    private static function failureMessage(ResponseInterface $response): ?string
    {
        $payload = self::decodeBody($response);

        foreach (['message', 'Message', 'error', 'Error'] as $key) {
            if (isset($payload[$key]) && is_string($payload[$key]) && $payload[$key] !== '') {
                return $payload[$key];
            }
        }

        return null;
    }

    /**
     * @return array<string, string[]>
     */
    private static function errorsFromResponse(ResponseInterface $response): array
    {
        $payload = self::decodeBody($response);
        $raw = $payload['errors'] ?? $payload['Errors'] ?? [];

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

    /**
     * @return array<string, mixed>
     */
    private static function decodeBody(ResponseInterface $response): array
    {
        try {
            $payload = JsonHelper::decode((string) $response->getBody());
        } catch (\Throwable) {
            return [];
        }

        return is_array($payload) ? $payload : [];
    }
}
