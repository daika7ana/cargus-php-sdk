<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Config;

final readonly class ClientConfig
{
    public const BASE_URL = 'https://urgentcargus.azure-api.net';

    public function __construct(
        public string $username = '',
        public string $password = '',
        public string $subscriptionKey = '',
        public string $baseUrl = self::BASE_URL,
        public ?float $timeout = null,
    ) {}

    public function withTimeout(?float $timeout): static
    {
        return new static(
            username: $this->username,
            password: $this->password,
            subscriptionKey: $this->subscriptionKey,
            baseUrl: $this->baseUrl,
            timeout: $timeout,
        );
    }

    public static function fromEnvironment(): self
    {
        $baseUrl = getenv('CARGUS_BASE_URL');
        $timeout = getenv('CARGUS_TIMEOUT');

        if (is_string($timeout) && $timeout !== '' && !is_numeric($timeout)) {
            throw new \InvalidArgumentException('CARGUS_TIMEOUT must be numeric.');
        }

        return new self(
            username: (string) (getenv('CARGUS_USERNAME') ?: ''),
            password: (string) (getenv('CARGUS_PASSWORD') ?: ''),
            subscriptionKey: (string) (getenv('CARGUS_SUBSCRIPTION_KEY') ?: ''),
            baseUrl: is_string($baseUrl) && $baseUrl !== '' ? rtrim($baseUrl, '/') : self::BASE_URL,
            timeout: is_string($timeout) && $timeout !== '' ? (float) $timeout : null,
        );
    }
}
