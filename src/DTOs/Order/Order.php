<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Order;

final readonly class Order
{
    public function __construct(
        public int $orderId = 0,
        public int $locationId = 0,
        public string $pickUpSiteName = '',
        public string $pickupStartDate = '',
        public string $pickupEndDate = '',
        public int $noParcel = 0,
        public int $noEnvelop = 0,
        public int $totalWeight = 0,
        public int $noAwb = 0,
        public string $observations = '',
        public string $packageContent = '',
        public string $creationDate = '',
        public string $validationDate = '',
        public string $processedDate = '',
        public int $ordStatus = 0,
        public string $ordStatusDetails = '',
        public string $adresaExp = '',
        public string $email = '',
        public int $orderType = 0,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            orderId: (int) ($data['OrderId'] ?? $data['orderId'] ?? 0),
            locationId: (int) ($data['LocationId'] ?? $data['locationId'] ?? 0),
            pickUpSiteName: (string) ($data['PickUpSiteName'] ?? $data['pickUpSiteName'] ?? ''),
            pickupStartDate: (string) ($data['PickupStartDate'] ?? $data['pickupStartDate'] ?? ''),
            pickupEndDate: (string) ($data['PickupEndDate'] ?? $data['pickupEndDate'] ?? ''),
            noParcel: (int) ($data['NoParcel'] ?? $data['noParcel'] ?? 0),
            noEnvelop: (int) ($data['NoEnvelop'] ?? $data['noEnvelop'] ?? 0),
            totalWeight: (int) ($data['TotalWeight'] ?? $data['totalWeight'] ?? 0),
            noAwb: (int) ($data['NoAwb'] ?? $data['noAwb'] ?? 0),
            observations: (string) ($data['Observations'] ?? $data['observations'] ?? ''),
            packageContent: (string) ($data['PackageContent'] ?? $data['packageContent'] ?? ''),
            creationDate: (string) ($data['CreationDate'] ?? $data['creationDate'] ?? ''),
            validationDate: (string) ($data['ValidationDate'] ?? $data['validationDate'] ?? ''),
            processedDate: (string) ($data['ProcessedDate'] ?? $data['processedDate'] ?? ''),
            ordStatus: (int) ($data['OrdStatus'] ?? $data['ordStatus'] ?? 0),
            ordStatusDetails: (string) ($data['OrdStatusDetails'] ?? $data['ordStatusDetails'] ?? ''),
            adresaExp: (string) ($data['AdresaExp'] ?? $data['adresaExp'] ?? ''),
            email: (string) ($data['Email'] ?? $data['email'] ?? ''),
            orderType: (int) ($data['OrderType'] ?? $data['orderType'] ?? 0),
        );
    }
}
