<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Pricing;

final readonly class ShippingCalculationModel
{
    /**
     * @param list<ShippingCalculationParcelCode> $parcelCodes
     */
    public function __construct(
        public int $parcels,
        public int $envelopes,
        public int $totalWeight,
        public int $shipmentPayer,
        public float $declaredValue,
        public float $cashRepayment,
        public float $bankRepayment,
        public bool $openPackage,
        public bool $saturdayDelivery,
        public bool $morningDelivery,
        public ?int $deliveryPudoPoint = null,
        public ?int $fromLocalityId = null,
        public ?int $toLocalityId = null,
        public ?string $fromCountyName = null,
        public ?string $fromLocalityName = null,
        public ?string $toCountyName = null,
        public ?string $toLocalityName = null,
        public ?int $priceTableId = null,
        public ?int $serviceId = null,
        public ?string $otherRepayment = null,
        public ?int $paymentInstrumentId = null,
        public ?float $paymentInstrumentValue = null,
        public array $parcelCodes = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = array_filter([
            'DeliveryPudoPoint' => $this->deliveryPudoPoint,
            'FromLocalityId' => $this->fromLocalityId,
            'ToLocalityId' => $this->toLocalityId,
            'FromCountyName' => $this->fromCountyName,
            'FromLocalityName' => $this->fromLocalityName,
            'ToCountyName' => $this->toCountyName,
            'ToLocalityName' => $this->toLocalityName,
            'Parcels' => $this->parcels,
            'Envelopes' => $this->envelopes,
            'TotalWeight' => $this->totalWeight,
            'PriceTableId' => $this->priceTableId,
            'ServiceId' => $this->serviceId,
            'ShipmentPayer' => $this->shipmentPayer,
            'DeclaredValue' => $this->declaredValue,
            'CashRepayment' => $this->cashRepayment,
            'BankRepayment' => $this->bankRepayment,
            'OtherRepayment' => $this->otherRepayment,
            'PaymentInstrumentId' => $this->paymentInstrumentId,
            'PaymentInstrumentValue' => $this->paymentInstrumentValue,
            'OpenPackage' => $this->openPackage,
            'SaturdayDelivery' => $this->saturdayDelivery,
            'MorningDelivery' => $this->morningDelivery,
        ], static fn(mixed $value): bool => $value !== null);

        if ($this->parcelCodes !== []) {
            $data['ParcelCodes'] = array_map(
                static fn(ShippingCalculationParcelCode $parcelCode): array => $parcelCode->toArray(),
                $this->parcelCodes,
            );
        }

        return $data;
    }
}
