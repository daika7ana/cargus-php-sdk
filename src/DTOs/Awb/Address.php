<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Awb;

final readonly class Address
{
    public function __construct(
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
        public ?string $cityCenterName = null,
        public ?int $cityCenterId = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            locationId: self::intOrNull($data, 'LocationId', 'locationId'),
            name: self::stringOrNull($data, 'Name', 'name'),
            countyId: self::intOrNull($data, 'CountyId', 'countyId'),
            countyName: self::stringOrNull($data, 'CountyName', 'countyName'),
            localityId: self::intOrNull($data, 'LocalityId', 'localityId'),
            localityName: self::stringOrNull($data, 'LocalityName', 'localityName'),
            streetId: self::intOrNull($data, 'StreetId', 'streetId'),
            streetName: self::stringOrNull($data, 'StreetName', 'streetName'),
            buildingNumber: self::stringOrNull($data, 'BuildingNumber', 'buildingNumber'),
            addressText: self::stringOrNull($data, 'AddressText', 'addressText'),
            contactPerson: self::stringOrNull($data, 'ContactPerson', 'contactPerson'),
            phoneNumber: self::stringOrNull($data, 'PhoneNumber', 'phoneNumber'),
            email: self::stringOrNull($data, 'Email', 'email'),
            codPostal: self::stringOrNull($data, 'CodPostal', 'codPostal'),
            postalCode: self::stringOrNull($data, 'PostalCode', 'postalCode'),
            countryId: self::intOrNull($data, 'CountryId', 'countryId'),
            cityCenterName: self::stringOrNull($data, 'CityCenterName', 'cityCenterName'),
            cityCenterId: self::intOrNull($data, 'CityCenterId', 'cityCenterId'),
        );
    }

    /**
     * @return array<string, int|string>
     */
    public function toArray(): array
    {
        $data = [];
        $optionals = [
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
            'CityCenterName' => $this->cityCenterName,
            'CityCenterId' => $this->cityCenterId,
        ];

        foreach ($optionals as $key => $value) {
            if ($value !== null) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function intOrNull(array $data, string $pascal, string $camel): ?int
    {
        $value = $data[$pascal] ?? $data[$camel] ?? null;

        return $value === null ? null : (int) $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function stringOrNull(array $data, string $pascal, string $camel): ?string
    {
        $value = $data[$pascal] ?? $data[$camel] ?? null;

        return $value === null ? null : (string) $value;
    }
}
