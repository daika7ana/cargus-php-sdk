<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Geography;

final readonly class Branch
{
    public function __construct(
        public int $id,
        public string $symbol = '',
        public string $type = '',
        public string $name = '',
        public string $storeName = '',
        public string $address = '',
        public string $phone = '',
        public string $workhours = '',
        public float $latitude = 0.0,
        public float $longitude = 0.0,
        public int $depositId = 0,
        public int $cityId = 0,
        public int $streetId = 0,
        public string $postalNo = '',
        public string $postalCode = '',
        public string $taxes = '',
        public string $cod = '',
        public string $branchDetails = '',
        public string $branchDetailsEn = '',
        public string $branchImage = '',
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['BranchId'] ?? $data['branchId'] ?? 0),
            symbol: (string) ($data['Symbol'] ?? $data['symbol'] ?? ''),
            type: (string) ($data['Type'] ?? $data['type'] ?? ''),
            name: (string) ($data['Name'] ?? $data['name'] ?? ''),
            storeName: (string) ($data['StoreName'] ?? $data['storeName'] ?? ''),
            address: (string) ($data['Address'] ?? $data['address'] ?? ''),
            phone: (string) ($data['Phone'] ?? $data['phone'] ?? ''),
            workhours: (string) ($data['Workhours'] ?? $data['workhours'] ?? ''),
            latitude: (float) ($data['Latitude'] ?? $data['latitude'] ?? 0.0),
            longitude: (float) ($data['Longitude'] ?? $data['longitude'] ?? 0.0),
            depositId: (int) ($data['DepositId'] ?? $data['depositId'] ?? 0),
            cityId: (int) ($data['CityId'] ?? $data['cityId'] ?? 0),
            streetId: (int) ($data['StreetId'] ?? $data['streetId'] ?? 0),
            postalNo: (string) ($data['PostalNo'] ?? $data['postalNo'] ?? ''),
            postalCode: (string) ($data['PostalCode'] ?? $data['postalCode'] ?? ''),
            taxes: (string) ($data['Taxes'] ?? $data['taxes'] ?? ''),
            cod: (string) ($data['COD'] ?? $data['cod'] ?? ''),
            branchDetails: (string) ($data['BranchDetails'] ?? $data['branchDetails'] ?? ''),
            branchDetailsEn: (string) ($data['BranchDetailsEN'] ?? $data['branchDetailsEn'] ?? ''),
            branchImage: (string) ($data['BranchImage'] ?? $data['branchImage'] ?? ''),
        );
    }
}
