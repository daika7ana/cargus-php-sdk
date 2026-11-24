<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\OnlineShop;

final readonly class CustomerDetails
{
    public function __construct(
        public int $clientId = 0,
        public string $name = '',
        public string $clientName = '',
        public string $cui = '',
        public string $tradeRegisterNumber = '',
        public string $bank = '',
        public string $iban = '',
        public int $paymentTerm = 0,
        public string $category = '',
        public bool $juridicPerson = false,
        public string $contractNumber = '',
        public bool $applyDiscount = false,
        public bool $onlinePayment = false,
        public string $billingEmails = '',
        public string $county = '',
        public string $countyAbbr = '',
        public string $locality = '',
        public string $streetName = '',
        public string $streetNumber = '',
        public string $addressDetails = '',
        public string $zipCode = '',
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            clientId: (int) ($data['ClientID'] ?? $data['clientId'] ?? 0),
            name: (string) ($data['Name'] ?? $data['name'] ?? ''),
            clientName: (string) ($data['ClientName'] ?? $data['clientName'] ?? ''),
            cui: (string) ($data['CUI'] ?? $data['cui'] ?? ''),
            tradeRegisterNumber: (string) ($data['TradeRegisterNumber'] ?? $data['tradeRegisterNumber'] ?? ''),
            bank: (string) ($data['Bank'] ?? $data['bank'] ?? ''),
            iban: (string) ($data['Iban'] ?? $data['iban'] ?? ''),
            paymentTerm: (int) ($data['PaymentTerm'] ?? $data['paymentTerm'] ?? 0),
            category: (string) ($data['Category'] ?? $data['category'] ?? ''),
            juridicPerson: (bool) ($data['JuridicPerson'] ?? $data['juridicPerson'] ?? false),
            contractNumber: (string) ($data['ContractNumber'] ?? $data['contractNumber'] ?? ''),
            applyDiscount: (bool) ($data['ApplyDiscount'] ?? $data['applyDiscount'] ?? false),
            onlinePayment: (bool) ($data['OnlinePayment'] ?? $data['onlinePayment'] ?? false),
            billingEmails: (string) ($data['BillingEmails'] ?? $data['billingEmails'] ?? ''),
            county: (string) ($data['County'] ?? $data['county'] ?? ''),
            countyAbbr: (string) ($data['CountyAbbr'] ?? $data['countyAbbr'] ?? ''),
            locality: (string) ($data['Locality'] ?? $data['locality'] ?? ''),
            streetName: (string) ($data['StreetName'] ?? $data['streetName'] ?? ''),
            streetNumber: (string) ($data['StreetNumber'] ?? $data['streetNumber'] ?? ''),
            addressDetails: (string) ($data['AddressDetails'] ?? $data['addressDetails'] ?? ''),
            zipCode: (string) ($data['ZipCode'] ?? $data['zipCode'] ?? ''),
        );
    }
}
