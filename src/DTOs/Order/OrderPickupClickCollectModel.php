<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Order;

final readonly class OrderPickupClickCollectModel
{
    public function __construct(
        public ?string $observations = null,
        public ?int $parcels = null,
        public ?int $envelopes = null,
        public ?int $totalWeight = null,
        public ?string $pickupStartDate = null,
        public ?string $pickupEndDate = null,
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
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'Observations' => $this->observations,
            'Parcels' => $this->parcels,
            'Envelopes' => $this->envelopes,
            'TotalWeight' => $this->totalWeight,
            'PickupStartDate' => $this->pickupStartDate,
            'PickupEndDate' => $this->pickupEndDate,
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
