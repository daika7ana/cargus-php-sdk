<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Awb;

final readonly class RecipientAddress
{
    public function __construct(
        public ?string $countyName = null,
        public ?int $localityId = null,
        public ?string $localityName = null,
        public ?int $streetId = null,
        public ?string $streetName = null,
        public ?string $buildingNumber = null,
        public ?string $addressText = null,
        public ?string $zipCode = null,
    ) {}

    /**
     * @return array<string, int|string>
     */
    public function toArray(): array
    {
        $data = [];
        $optionals = [
            'CountyName' => $this->countyName,
            'LocalityId' => $this->localityId,
            'LocalityName' => $this->localityName,
            'StreetId' => $this->streetId,
            'StreetName' => $this->streetName,
            'BuildingNumber' => $this->buildingNumber,
            'AddressText' => $this->addressText,
            'ZipCode' => $this->zipCode,
        ];

        foreach ($optionals as $key => $value) {
            if ($value !== null) {
                $data[$key] = $value;
            }
        }

        return $data;
    }
}
