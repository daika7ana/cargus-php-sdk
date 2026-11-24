<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Awb;

final readonly class GeneralAwbModel
{
    /**
     * @param list<ParcelCode> $parcelCodes
     */
    public function __construct(
        public Address $sender,
        public Address $recipient,
        public int $parcels,
        public int $envelopes,
        public int $totalWeight,
        public int $shipmentPayer,
        public ?int $tertiaryClientId = null,
        public ?int $tertiaryLocationId = null,
        public ?int $deliveryPudoPoint = null,
        public ?int $consumerReturnType = null,
        public ?bool $isReturn = null,
        public ?int $returnCodeExpirationDays = null,
        public ?int $serviceId = null,
        public ?float $declaredValue = null,
        public ?float $cashRepayment = null,
        public ?float $bankRepayment = null,
        public ?string $otherRepayment = null,
        public ?string $barCodeRepayment = null,
        public ?bool $hasTertReimbursement = null,
        public ?bool $openPackage = null,
        public ?int $priceTableId = null,
        public ?int $shippingRepayment = null,
        public ?bool $saturdayDelivery = null,
        public ?bool $morningDelivery = null,
        public ?bool $hasNonStandardParcel = null,
        public ?int $deliveryTime = null,
        public ?string $observations = null,
        public ?string $packageContent = null,
        public ?string $customString = null,
        public ?string $barCode = null,
        public array $parcelCodes = [],
        public ?string $senderReference1 = null,
        public ?string $recipientReference1 = null,
        public ?string $recipientReference2 = null,
        public ?string $invoiceReference = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'Sender' => $this->sender->toArray(),
            'Recipient' => $this->recipient->toArray(),
            'Parcels' => $this->parcels,
            'Envelopes' => $this->envelopes,
            'TotalWeight' => $this->totalWeight,
            'ShipmentPayer' => $this->shipmentPayer,
        ];
        $optionals = [
            'TertiaryClientId' => $this->tertiaryClientId,
            'TertiaryLocationId' => $this->tertiaryLocationId,
            'DeliveryPudoPoint' => $this->deliveryPudoPoint,
            'ConsumerReturnType' => $this->consumerReturnType,
            'IsReturn' => $this->isReturn,
            'ReturnCodeExpirationDays' => $this->returnCodeExpirationDays,
            'ServiceId' => $this->serviceId,
            'DeclaredValue' => $this->declaredValue,
            'CashRepayment' => $this->cashRepayment,
            'BankRepayment' => $this->bankRepayment,
            'OtherRepayment' => $this->otherRepayment,
            'BarCodeRepayment' => $this->barCodeRepayment,
            'HasTertReimbursement' => $this->hasTertReimbursement,
            'OpenPackage' => $this->openPackage,
            'PriceTableId' => $this->priceTableId,
            'ShippingRepayment' => $this->shippingRepayment,
            'SaturdayDelivery' => $this->saturdayDelivery,
            'MorningDelivery' => $this->morningDelivery,
            'HasNonStandardParcel' => $this->hasNonStandardParcel,
            'DeliveryTime' => $this->deliveryTime,
            'Observations' => $this->observations,
            'PackageContent' => $this->packageContent,
            'CustomString' => $this->customString,
            'BarCode' => $this->barCode,
            'SenderReference1' => $this->senderReference1,
            'RecipientReference1' => $this->recipientReference1,
            'RecipientReference2' => $this->recipientReference2,
            'InvoiceReference' => $this->invoiceReference,
        ];

        foreach ($optionals as $key => $value) {
            if ($value !== null) {
                $data[$key] = $value;
            }
        }

        if ($this->parcelCodes !== []) {
            $data['ParcelCodes'] = array_map(
                static fn(ParcelCode $parcelCode): array => $parcelCode->toArray(),
                $this->parcelCodes,
            );
        }

        return $data;
    }
}
