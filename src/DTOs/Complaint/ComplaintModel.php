<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Complaint;

final readonly class ComplaintModel
{
    public function __construct(
        public string $personName,
        public string $email,
        public string $complaintDetails,
        public ?string $shipment = null,
        public ?string $company = null,
        public ?string $telephone = null,
        public ?string $shipmentContent = null,
        public ?string $shipmentPack = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'Shipment' => $this->shipment,
            'PersonName' => $this->personName,
            'Company' => $this->company,
            'Telephone' => $this->telephone,
            'Email' => $this->email,
            'ComplaintDetails' => $this->complaintDetails,
            'ShipmentContent' => $this->shipmentContent,
            'ShipmentPack' => $this->shipmentPack,
        ], static fn(mixed $value): bool => $value !== null);
    }
}
