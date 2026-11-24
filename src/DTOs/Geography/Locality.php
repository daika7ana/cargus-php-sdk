<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Geography;

final readonly class Locality
{
    public function __construct(
        public int $id,
        public string $name,
        public int $parentId = 0,
        public string $parentName = '',
        public int $extraKm = 0,
        public bool $inNetwork = false,
        public int $countyId = 0,
        public int $countryId = 0,
        public string $postalCode = '',
        public string $maxHour = '',
        public bool $saturdayDelivery = false,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['LocalityId'] ?? $data['localityId'] ?? 0),
            name: (string) ($data['Name'] ?? $data['name'] ?? ''),
            parentId: (int) ($data['ParentId'] ?? $data['parentId'] ?? 0),
            parentName: (string) ($data['ParentName'] ?? $data['parentName'] ?? ''),
            extraKm: (int) ($data['ExtraKm'] ?? $data['extraKm'] ?? 0),
            inNetwork: (bool) ($data['InNetwork'] ?? $data['inNetwork'] ?? false),
            countyId: (int) ($data['CountyId'] ?? $data['countyId'] ?? 0),
            countryId: (int) ($data['CountryId'] ?? $data['countryId'] ?? 0),
            postalCode: (string) ($data['PostalCode'] ?? $data['postalCode'] ?? ''),
            maxHour: (string) ($data['MaxHour'] ?? $data['maxHour'] ?? ''),
            saturdayDelivery: (bool) ($data['SaturdayDelivery'] ?? $data['saturdayDelivery'] ?? false),
        );
    }
}
