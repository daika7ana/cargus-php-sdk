<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Awb;

final readonly class AwbParcelUpdateModel
{
    /**
     * @param list<ParcelCode> $parcelCodes
     */
    public function __construct(
        public string $barcode,
        public array $parcelCodes = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'Barcode' => $this->barcode,
            'ParcelCodes' => array_map(
                static fn(ParcelCode $parcelCode): array => $parcelCode->toArray(),
                $this->parcelCodes,
            ),
        ];
    }
}
