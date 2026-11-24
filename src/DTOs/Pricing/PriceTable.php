<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Pricing;

final readonly class PriceTable
{
    public function __construct(
        public int $id,
        public string $name = '',
        public int $tariffVersion = 0,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['PriceTableId'] ?? $data['priceTableId'] ?? 0),
            name: (string) ($data['Name'] ?? $data['name'] ?? ''),
            tariffVersion: (int) ($data['TariffVerion'] ?? $data['TariffVersion'] ?? $data['tariffVersion'] ?? 0),
        );
    }
}
