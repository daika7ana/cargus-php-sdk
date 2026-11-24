<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Awb;

final readonly class DeliveryRouteModel
{
    public function __construct(
        public string $transitPriority = '',
        public string $transitRoute = '',
        public string $transitCodes = '',
        public string $deliveryRoute = '',
        public string $originDeposit = '',
        public string $originDepositFinger = '',
        public string $transit1Deposit = '',
        public string $transit1DepositFinger = '',
        public string $transit2Deposit = '',
        public string $transit2DepositFinger = '',
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            transitPriority: (string) ($data['TransitPriority'] ?? ''),
            transitRoute: (string) ($data['TransitRoute'] ?? ''),
            transitCodes: (string) ($data['TransitCodes'] ?? ''),
            deliveryRoute: (string) ($data['DeliveryRoute'] ?? ''),
            originDeposit: (string) ($data['OriginDeposit'] ?? ''),
            originDepositFinger: (string) ($data['OriginDepositFinger'] ?? ''),
            transit1Deposit: (string) ($data['Transit1Deposit'] ?? ''),
            transit1DepositFinger: (string) ($data['Transit1DepositFinger'] ?? ''),
            transit2Deposit: (string) ($data['Transit2Deposit'] ?? ''),
            transit2DepositFinger: (string) ($data['Transit2DepositFinger'] ?? ''),
        );
    }
}
