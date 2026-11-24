<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Complaint;

final readonly class ComplaintStatus
{
    public function __construct(
        public bool $shipmentExists = false,
        public string $complaintStatusRo = '',
        public string $complaintStatusEn = '',
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            shipmentExists: (bool) ($data['ShipmentExists'] ?? $data['shipmentExists'] ?? false),
            complaintStatusRo: (string) ($data['ComplaintStatusRo'] ?? $data['complaintStatusRo'] ?? ''),
            complaintStatusEn: (string) ($data['ComplaintStatusEn'] ?? $data['complaintStatusEn'] ?? ''),
        );
    }
}
