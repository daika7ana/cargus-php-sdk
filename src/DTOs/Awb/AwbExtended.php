<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Awb;

use Daika7ana\Cargus\DTOs\Pricing\ShippingCost;
use Daika7ana\SimpleCollections\Collection;

final readonly class AwbExtended
{
    /**
     * @param Collection<int, ParcelCode> $parcelCodes
     */
    public function __construct(
        public Address $sender,
        public Address $recipient,
        public int $parcels,
        public int $envelopes,
        public int $totalWeight,
        public int $shipmentPayer,
        public string $returnCodeExpiration = '',
        public int $idComanda = 0,
        public string $returnCode = '',
        public string $returnAwb = '',
        public int $senderClientId = 0,
        public int $tertiaryClientId = 0,
        public int $tertiaryLocationId = 0,
        public int $serviceId = 0,
        public float $declaredValue = 0.0,
        public float $cashRepayment = 0.0,
        public float $bankRepayment = 0.0,
        public string $otherRepayment = '',
        public string $barCodeRepayment = '',
        public int $paymentInstrumentId = 0,
        public float $paymentInstrumentValue = 0.0,
        public bool $hasTertReimbursement = false,
        public bool $openPackage = false,
        public int $priceTableId = 0,
        public int $shippingRepayment = 0,
        public bool $saturdayDelivery = false,
        public bool $morningDelivery = false,
        public bool $hasNonStandardParcel = false,
        public int $deliveryTime = 0,
        public string $observations = '',
        public string $packageContent = '',
        public string $customString = '',
        public string $barCode = '',
        public Collection $parcelCodes = new Collection(),
        public string $validationDate = '',
        public ?ShippingCost $shippingCost = null,
        public string $status = '',
        public string $senderReference1 = '',
        public string $recipientReference1 = '',
        public string $recipientReference2 = '',
        public string $invoiceReference = '',
        public int $length = 0,
        public int $width = 0,
        public int $height = 0,
        public string $transitPriority = '',
        public string $transitRoute = '',
        public string $transitCodes = '',
        public string $deliveryRoute = '',
        public string $originDeposit = '',
        public string $transit1Deposit = '',
        public string $transit2Deposit = '',
        public string $originDepositFinger = '',
        public string $transit1DepositFinger = '',
        public string $transit2DepositFinger = '',
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $parcelCodes = [];

        foreach (is_array($data['ParcelCodes'] ?? null) ? $data['ParcelCodes'] : [] as $parcelCode) {
            if (is_array($parcelCode)) {
                $parcelCodes[] = ParcelCode::fromArray($parcelCode);
            }
        }

        $shippingCost = $data['ShippingCost'] ?? null;

        return new self(
            sender: Address::fromArray(is_array($data['Sender'] ?? null) ? $data['Sender'] : []),
            recipient: Address::fromArray(is_array($data['Recipient'] ?? null) ? $data['Recipient'] : []),
            parcels: (int) ($data['Parcels'] ?? 0),
            envelopes: (int) ($data['Envelopes'] ?? 0),
            totalWeight: (int) ($data['TotalWeight'] ?? 0),
            shipmentPayer: (int) ($data['ShipmentPayer'] ?? 0),
            returnCodeExpiration: (string) ($data['ReturnCodeExpiration'] ?? ''),
            idComanda: (int) ($data['IdComanda'] ?? 0),
            returnCode: (string) ($data['ReturnCode'] ?? ''),
            returnAwb: (string) ($data['ReturnAwb'] ?? ''),
            senderClientId: (int) ($data['SenderClientId'] ?? 0),
            tertiaryClientId: (int) ($data['TertiaryClientId'] ?? 0),
            tertiaryLocationId: (int) ($data['TertiaryLocationId'] ?? 0),
            serviceId: (int) ($data['ServiceId'] ?? 0),
            declaredValue: (float) ($data['DeclaredValue'] ?? 0.0),
            cashRepayment: (float) ($data['CashRepayment'] ?? 0.0),
            bankRepayment: (float) ($data['BankRepayment'] ?? 0.0),
            otherRepayment: (string) ($data['OtherRepayment'] ?? ''),
            barCodeRepayment: (string) ($data['BarCodeRepayment'] ?? ''),
            paymentInstrumentId: (int) ($data['PaymentInstrumentId'] ?? 0),
            paymentInstrumentValue: (float) ($data['PaymentInstrumentValue'] ?? 0.0),
            hasTertReimbursement: (bool) ($data['HasTertReimbursement'] ?? false),
            openPackage: (bool) ($data['OpenPackage'] ?? false),
            priceTableId: (int) ($data['PriceTableId'] ?? 0),
            shippingRepayment: (int) ($data['ShippingRepayment'] ?? 0),
            saturdayDelivery: (bool) ($data['SaturdayDelivery'] ?? false),
            morningDelivery: (bool) ($data['MorningDelivery'] ?? false),
            hasNonStandardParcel: (bool) ($data['HasNonStandardParcel'] ?? false),
            deliveryTime: (int) ($data['DeliveryTime'] ?? 0),
            observations: (string) ($data['Observations'] ?? ''),
            packageContent: (string) ($data['PackageContent'] ?? ''),
            customString: (string) ($data['CustomString'] ?? ''),
            barCode: (string) ($data['BarCode'] ?? ''),
            parcelCodes: new Collection($parcelCodes),
            validationDate: (string) ($data['ValidationDate'] ?? ''),
            shippingCost: is_array($shippingCost) ? ShippingCost::fromArray($shippingCost) : null,
            status: (string) ($data['Status'] ?? ''),
            senderReference1: (string) ($data['SenderReference1'] ?? ''),
            recipientReference1: (string) ($data['RecipientReference1'] ?? ''),
            recipientReference2: (string) ($data['RecipientReference2'] ?? ''),
            invoiceReference: (string) ($data['InvoiceReference'] ?? ''),
            length: (int) ($data['Length'] ?? 0),
            width: (int) ($data['Width'] ?? 0),
            height: (int) ($data['Height'] ?? 0),
            transitPriority: (string) ($data['TransitPriority'] ?? ''),
            transitRoute: (string) ($data['TransitRoute'] ?? ''),
            transitCodes: (string) ($data['TransitCodes'] ?? ''),
            deliveryRoute: (string) ($data['DeliveryRoute'] ?? ''),
            originDeposit: (string) ($data['OriginDeposit'] ?? ''),
            transit1Deposit: (string) ($data['Transit1Deposit'] ?? ''),
            transit2Deposit: (string) ($data['Transit2Deposit'] ?? ''),
            originDepositFinger: (string) ($data['OriginDepositFinger'] ?? ''),
            transit1DepositFinger: (string) ($data['Transit1DepositFinger'] ?? ''),
            transit2DepositFinger: (string) ($data['Transit2DepositFinger'] ?? ''),
        );
    }
}
