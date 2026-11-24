<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Awb;

use Daika7ana\Cargus\DTOs\Pricing\ShippingCost;

final readonly class AwbTurModel
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
        public ?int $deliveryPudoPoint = null,
        public ?int $consumerReturnType = null,
        public ?int $returnCodeExpirationDays = null,
        public ?int $senderClientId = null,
        public ?int $tertiaryClientId = null,
        public ?int $tertiaryLocationId = null,
        public ?int $serviceId = null,
        public ?float $declaredValue = null,
        public ?float $cashRepayment = null,
        public ?float $bankRepayment = null,
        public ?string $otherRepayment = null,
        public ?string $barCodeRepayment = null,
        public ?int $paymentInstrumentId = null,
        public ?float $paymentInstrumentValue = null,
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
        public ?string $validationDate = null,
        public ?ShippingCost $shippingCost = null,
        public ?string $status = null,
        public ?string $senderReference1 = null,
        public ?string $recipientReference1 = null,
        public ?string $recipientReference2 = null,
        public ?string $invoiceReference = null,
        public ?int $length = null,
        public ?int $width = null,
        public ?int $height = null,
        public ?string $transitPriority = null,
        public ?string $transitRoute = null,
        public ?string $transitCodes = null,
        public ?string $deliveryRoute = null,
        public ?string $originDeposit = null,
        public ?string $transit1Deposit = null,
        public ?string $transit2Deposit = null,
        public ?string $originDepositFinger = null,
        public ?string $transit1DepositFinger = null,
        public ?string $transit2DepositFinger = null,
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
            'DeliveryPudoPoint' => $this->deliveryPudoPoint,
            'ConsumerReturnType' => $this->consumerReturnType,
            'ReturnCodeExpirationDays' => $this->returnCodeExpirationDays,
            'SenderClientId' => $this->senderClientId,
            'TertiaryClientId' => $this->tertiaryClientId,
            'TertiaryLocationId' => $this->tertiaryLocationId,
            'ServiceId' => $this->serviceId,
            'DeclaredValue' => $this->declaredValue,
            'CashRepayment' => $this->cashRepayment,
            'BankRepayment' => $this->bankRepayment,
            'OtherRepayment' => $this->otherRepayment,
            'BarCodeRepayment' => $this->barCodeRepayment,
            'PaymentInstrumentId' => $this->paymentInstrumentId,
            'PaymentInstrumentValue' => $this->paymentInstrumentValue,
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
            'ValidationDate' => $this->validationDate,
            'ShippingCost' => $this->shippingCost === null ? null : [
                'BaseCost' => $this->shippingCost->baseCost,
                'ExtraKmCost' => $this->shippingCost->extraKmCost,
                'WeightCost' => $this->shippingCost->weightCost,
                'InsuranceCost' => $this->shippingCost->insuranceCost,
                'SpecialCost' => $this->shippingCost->specialCost,
                'RepaymentCost' => $this->shippingCost->repaymentCost,
                'Subtotal' => $this->shippingCost->subtotal,
                'Tax' => $this->shippingCost->tax,
                'GrandTotal' => $this->shippingCost->grandTotal,
            ],
            'Status' => $this->status,
            'SenderReference1' => $this->senderReference1,
            'RecipientReference1' => $this->recipientReference1,
            'RecipientReference2' => $this->recipientReference2,
            'InvoiceReference' => $this->invoiceReference,
            'Length' => $this->length,
            'Width' => $this->width,
            'Height' => $this->height,
            'TransitPriority' => $this->transitPriority,
            'TransitRoute' => $this->transitRoute,
            'TransitCodes' => $this->transitCodes,
            'DeliveryRoute' => $this->deliveryRoute,
            'OriginDeposit' => $this->originDeposit,
            'Transit1Deposit' => $this->transit1Deposit,
            'Transit2Deposit' => $this->transit2Deposit,
            'OriginDepositFinger' => $this->originDepositFinger,
            'Transit1DepositFinger' => $this->transit1DepositFinger,
            'Transit2DepositFinger' => $this->transit2DepositFinger,
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
