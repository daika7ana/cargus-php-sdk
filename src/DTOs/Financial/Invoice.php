<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Financial;

final readonly class Invoice
{
    public function __construct(
        public int $id,
        public string $date = '',
        public string $dueDate = '',
        public string $series = '',
        public int $number = 0,
        public float $value = 0.0,
        public float $total = 0.0,
        public bool $closed = false,
        public float $balance = 0.0,
        public int $currencyId = 0,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['InvoiceId'] ?? $data['invoiceId'] ?? 0),
            date: (string) ($data['Date'] ?? $data['date'] ?? ''),
            dueDate: (string) ($data['DueDate'] ?? $data['dueDate'] ?? ''),
            series: (string) ($data['Series'] ?? $data['series'] ?? ''),
            number: (int) ($data['Number'] ?? $data['number'] ?? 0),
            value: (float) ($data['Value'] ?? $data['value'] ?? 0.0),
            total: (float) ($data['Total'] ?? $data['total'] ?? 0.0),
            closed: (bool) ($data['Closed'] ?? $data['closed'] ?? false),
            balance: (float) ($data['Balance'] ?? $data['balance'] ?? 0.0),
            currencyId: (int) ($data['CurrencyId'] ?? $data['currencyId'] ?? 0),
        );
    }
}
