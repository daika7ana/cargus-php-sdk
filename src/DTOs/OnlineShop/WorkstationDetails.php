<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\OnlineShop;

final readonly class WorkstationDetails
{
    public function __construct(
        public int $id = 0,
        public string $name = '',
        public string $contactPerson = '',
        public string $contactPhoneNumber = '',
        public string $contactEmail = '',
        public string $county = '',
        public string $countyAbbr = '',
        public string $locality = '',
        public string $streetName = '',
        public string $streetNumber = '',
        public string $addressDetails = '',
        public string $zipCode = '',
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['Id'] ?? $data['id'] ?? 0),
            name: (string) ($data['Name'] ?? $data['name'] ?? ''),
            contactPerson: (string) ($data['ContactPerson'] ?? $data['contactPerson'] ?? ''),
            contactPhoneNumber: (string) ($data['ContactPhoneNumber'] ?? $data['contactPhoneNumber'] ?? ''),
            contactEmail: (string) ($data['ContactEmail'] ?? $data['contactEmail'] ?? ''),
            county: (string) ($data['County'] ?? $data['county'] ?? ''),
            countyAbbr: (string) ($data['CountyAbbr'] ?? $data['countyAbbr'] ?? ''),
            locality: (string) ($data['Locality'] ?? $data['locality'] ?? ''),
            streetName: (string) ($data['StreetName'] ?? $data['streetName'] ?? ''),
            streetNumber: (string) ($data['StreetNumber'] ?? $data['streetNumber'] ?? ''),
            addressDetails: (string) ($data['AddressDetails'] ?? $data['addressDetails'] ?? ''),
            zipCode: (string) ($data['ZipCode'] ?? $data['zipCode'] ?? ''),
        );
    }
}
