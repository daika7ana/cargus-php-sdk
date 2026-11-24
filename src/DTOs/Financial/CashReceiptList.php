<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Financial;

final readonly class CashReceiptList
{
    /**
     * @param list<CashReceipt>|null $cashReceipts
     */
    public function __construct(
        public string $date,
        public string $operator,
        public int $location,
        public ?int $id = null,
        public ?int $orderId = null,
        public ?array $cashReceipts = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = array_filter([
            'Id' => $this->id,
            'Date' => $this->date,
            'Operator' => $this->operator,
            'Location' => $this->location,
            'OrderId' => $this->orderId,
        ], static fn(mixed $value): bool => $value !== null);

        if ($this->cashReceipts !== null) {
            $data['CashReceipts'] = array_map(
                static fn(CashReceipt $cashReceipt): array => $cashReceipt->toArray(),
                $this->cashReceipts,
            );
        }

        return $data;
    }
}
