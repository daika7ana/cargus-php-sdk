<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Pickup;

use Daika7ana\Cargus\DTOs\PickupLocation\AddressExtended;

final readonly class AwbPickupIndividualModel
{
    /**
     * @param array<string, mixed>|null $senderReturnAccountDetails
     * @param list<array<string, mixed>>|null $parcelCodes
     * @param array<string, mixed>|null $shippingCost
     */
    public function __construct(
        public ?int $deliveryPudoPoint = null,
        public ?int $pickUpPudoPoint = null,
        public ?array $senderReturnAccountDetails = null,
        public ?string $pickupStartDate = null,
        public ?string $pickupEndDate = null,
        public ?bool $clientDropOff = null,
        public ?int $senderClientId = null,
        public ?int $tertiaryClientId = null,
        public ?int $tertiaryLocationId = null,
        public ?AddressExtended $sender = null,
        public ?AddressExtended $recipient = null,
        public ?int $parcels = null,
        public ?int $envelopes = null,
        public ?int $totalWeight = null,
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
        public ?int $shipmentPayer = null,
        public ?int $shippingRepayment = null,
        public ?bool $saturdayDelivery = null,
        public ?bool $morningDelivery = null,
        public ?bool $hasNonStandardParcel = null,
        public ?int $deliveryTime = null,
        public ?string $observations = null,
        public ?string $packageContent = null,
        public ?string $customString = null,
        public ?string $barCode = null,
        public ?array $parcelCodes = null,
        public ?string $validationDate = null,
        public ?array $shippingCost = null,
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
        return array_filter([
            'DeliveryPudoPoint' => $this->deliveryPudoPoint,
            'PickUpPudoPoint' => $this->pickUpPudoPoint,
            'SenderReturnAccountDetails' => $this->senderReturnAccountDetails,
            'PickupStartDate' => $this->pickupStartDate,
            'PickupEndDate' => $this->pickupEndDate,
            'ClientDropOff' => $this->clientDropOff,
            'SenderClientId' => $this->senderClientId,
            'TertiaryClientId' => $this->tertiaryClientId,
            'TertiaryLocationId' => $this->tertiaryLocationId,
            'Sender' => $this->sender?->toArray(),
            'Recipient' => $this->recipient?->toArray(),
            'Parcels' => $this->parcels,
            'Envelopes' => $this->envelopes,
            'TotalWeight' => $this->totalWeight,
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
            'ShipmentPayer' => $this->shipmentPayer,
            'ShippingRepayment' => $this->shippingRepayment,
            'SaturdayDelivery' => $this->saturdayDelivery,
            'MorningDelivery' => $this->morningDelivery,
            'HasNonStandardParcel' => $this->hasNonStandardParcel,
            'DeliveryTime' => $this->deliveryTime,
            'Observations' => $this->observations,
            'PackageContent' => $this->packageContent,
            'CustomString' => $this->customString,
            'BarCode' => $this->barCode,
            'ParcelCodes' => $this->parcelCodes,
            'ValidationDate' => $this->validationDate,
            'ShippingCost' => $this->shippingCost,
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
        ], static fn(mixed $value): bool => $value !== null);
    }
}
