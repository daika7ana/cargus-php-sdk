<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Pricing;

final readonly class ShippingCalculationParcelCode
{
    public function __construct(
        public int $type,
        public ?float $weight = null,
        public ?int $length = null,
        public ?int $width = null,
        public ?int $height = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'Type' => $this->type,
            'Weight' => $this->weight,
            'Length' => $this->length,
            'Width' => $this->width,
            'Height' => $this->height,
        ], static fn(mixed $value): bool => $value !== null);
    }
}
