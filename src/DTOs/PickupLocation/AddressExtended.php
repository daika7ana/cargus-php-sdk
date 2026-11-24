<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\PickupLocation;

final readonly class AddressExtended
{
    public function __construct(
        public ?string $automaticEOD = null,
        public ?int $locationId = null,
        public ?string $name = null,
        public ?int $countyId = null,
        public ?string $countyName = null,
        public ?int $localityId = null,
        public ?string $localityName = null,
        public ?int $streetId = null,
        public ?string $streetName = null,
        public ?string $buildingNumber = null,
        public ?string $addressText = null,
        public ?string $contactPerson = null,
        public ?string $phoneNumber = null,
        public ?string $email = null,
        public ?string $codPostal = null,
        public ?string $postalCode = null,
        public ?int $countryId = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            automaticEOD: isset($data['AutomaticEOD']) ? (string) $data['AutomaticEOD'] : (isset($data['automaticEOD']) ? (string) $data['automaticEOD'] : null),
            locationId: isset($data['LocationId']) ? (int) $data['LocationId'] : (isset($data['locationId']) ? (int) $data['locationId'] : null),
            name: isset($data['Name']) ? (string) $data['Name'] : (isset($data['name']) ? (string) $data['name'] : null),
            countyId: isset($data['CountyId']) ? (int) $data['CountyId'] : (isset($data['countyId']) ? (int) $data['countyId'] : null),
            countyName: isset($data['CountyName']) ? (string) $data['CountyName'] : (isset($data['countyName']) ? (string) $data['countyName'] : null),
            localityId: isset($data['LocalityId']) ? (int) $data['LocalityId'] : (isset($data['localityId']) ? (int) $data['localityId'] : null),
            localityName: isset($data['LocalityName']) ? (string) $data['LocalityName'] : (isset($data['localityName']) ? (string) $data['localityName'] : null),
            streetId: isset($data['StreetId']) ? (int) $data['StreetId'] : (isset($data['streetId']) ? (int) $data['streetId'] : null),
            streetName: isset($data['StreetName']) ? (string) $data['StreetName'] : (isset($data['streetName']) ? (string) $data['streetName'] : null),
            buildingNumber: isset($data['BuildingNumber']) ? (string) $data['BuildingNumber'] : (isset($data['buildingNumber']) ? (string) $data['buildingNumber'] : null),
            addressText: isset($data['AddressText']) ? (string) $data['AddressText'] : (isset($data['addressText']) ? (string) $data['addressText'] : null),
            contactPerson: isset($data['ContactPerson']) ? (string) $data['ContactPerson'] : (isset($data['contactPerson']) ? (string) $data['contactPerson'] : null),
            phoneNumber: isset($data['PhoneNumber']) ? (string) $data['PhoneNumber'] : (isset($data['phoneNumber']) ? (string) $data['phoneNumber'] : null),
            email: isset($data['Email']) ? (string) $data['Email'] : (isset($data['email']) ? (string) $data['email'] : null),
            codPostal: isset($data['CodPostal']) ? (string) $data['CodPostal'] : (isset($data['codPostal']) ? (string) $data['codPostal'] : null),
            postalCode: isset($data['PostalCode']) ? (string) $data['PostalCode'] : (isset($data['postalCode']) ? (string) $data['postalCode'] : null),
            countryId: isset($data['CountryId']) ? (int) $data['CountryId'] : (isset($data['countryId']) ? (int) $data['countryId'] : null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'AutomaticEOD' => $this->automaticEOD,
            'LocationId' => $this->locationId,
            'Name' => $this->name,
            'CountyId' => $this->countyId,
            'CountyName' => $this->countyName,
            'LocalityId' => $this->localityId,
            'LocalityName' => $this->localityName,
            'StreetId' => $this->streetId,
            'StreetName' => $this->streetName,
            'BuildingNumber' => $this->buildingNumber,
            'AddressText' => $this->addressText,
            'ContactPerson' => $this->contactPerson,
            'PhoneNumber' => $this->phoneNumber,
            'Email' => $this->email,
            'CodPostal' => $this->codPostal,
            'PostalCode' => $this->postalCode,
            'CountryId' => $this->countryId,
        ], static fn(mixed $value): bool => $value !== null);
    }
}
