<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Financial;

final readonly class CashReceipt
{
    public function __construct(
        public string $receiptDate,
        public string $receiptNumber,
        public string $receiptOperator,
        public float $amount,
        public string $awb,
        public int $idAwb,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ReceiptDate' => $this->receiptDate,
            'ReceiptNumber' => $this->receiptNumber,
            'ReceiptOperator' => $this->receiptOperator,
            'Amount' => $this->amount,
            'Awb' => $this->awb,
            'IdAwb' => $this->idAwb,
        ];
    }
}
