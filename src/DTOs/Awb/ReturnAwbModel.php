<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Awb;

final readonly class ReturnAwbModel
{
    public function __construct(
        public ?string $barcode = null,
        public ?int $clientId = null,
        public ?int $userId = null,
        public ?bool $hasContract = null,
        public ?bool $isMarketplace = null,
        public ?int $returnCodeExpirationDays = null,
        public ?int $senderLocationId = null,
    ) {}

    /**
     * @return array<string, bool|int|string>
     */
    public function toArray(): array
    {
        $data = [];
        $optionals = [
            'Barcode' => $this->barcode,
            'ClientId' => $this->clientId,
            'UserId' => $this->userId,
            'HasContract' => $this->hasContract,
            'IsMarketplace' => $this->isMarketplace,
            'ReturnCodeExpirationDays' => $this->returnCodeExpirationDays,
            'SenderLocationId' => $this->senderLocationId,
        ];

        foreach ($optionals as $key => $value) {
            if ($value !== null) {
                $data[$key] = $value;
            }
        }

        return $data;
    }
}
