<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Auth;

use DateTimeImmutable;
use Daika7ana\Cargus\Exceptions\AuthenticationException;

final readonly class Token
{
    public const LIFETIME_HOURS = 24;

    public function __construct(
        public string $accessToken,
        public ?DateTimeImmutable $expiresAt = null,
        public string $tokenType = 'Bearer',
    ) {}

    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        // Treat a token expiring within 60s as expired, so it is not sent mid-flight.
        return $this->expiresAt <= new DateTimeImmutable('+60 seconds');
    }

    public static function fromTokenString(string $accessToken): self
    {
        if ($accessToken === '') {
            throw new AuthenticationException('Cannot build token: the login response is empty.');
        }

        return new self(
            accessToken: $accessToken,
            expiresAt: new DateTimeImmutable(sprintf('+%d hours', self::LIFETIME_HOURS)),
        );
    }

    /**
     * @return array{access_token: string, token_type: string, expires_at: ?string}
     */
    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'token_type' => $this->tokenType,
            'expires_at' => $this->expiresAt?->format(DATE_ATOM),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $accessToken = (string) ($data['access_token'] ?? '');

        if ($accessToken === '') {
            throw new AuthenticationException('Cannot restore token: access_token is missing or empty.');
        }

        return new self(
            accessToken: $accessToken,
            expiresAt: self::parseDate($data['expires_at'] ?? null),
            tokenType: (string) ($data['token_type'] ?? 'Bearer'),
        );
    }

    private static function parseDate(mixed $value): ?DateTimeImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (\Exception) {
            // Corrupted cache data: treat as already expired so a fresh login is requested.
            return new DateTimeImmutable();
        }
    }
}
