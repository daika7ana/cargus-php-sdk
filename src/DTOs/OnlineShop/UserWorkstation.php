<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\OnlineShop;

final readonly class UserWorkstation
{
    public function __construct(
        public int $id,
        public string $name = '',
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['Id'] ?? $data['id'] ?? 0),
            name: (string) ($data['Name'] ?? $data['name'] ?? ''),
        );
    }
}
