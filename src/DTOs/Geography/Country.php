<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Geography;

final readonly class Country
{
    public function __construct(
        public int $id,
        public string $name,
        public string $abbreviation = '',
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['CountryId'] ?? $data['countryId'] ?? 0),
            name: (string) ($data['CountryName'] ?? $data['countryName'] ?? ''),
            abbreviation: (string) ($data['Abbreviation'] ?? $data['abbreviation'] ?? ''),
        );
    }
}
