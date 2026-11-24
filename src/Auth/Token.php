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
}
