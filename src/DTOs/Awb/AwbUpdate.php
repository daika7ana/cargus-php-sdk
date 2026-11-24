<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Awb;

final readonly class AwbUpdate
{
    public function __construct(
        public ?string $awbBarcode = null,
        public ?string $pieceBarcode = null,
        public ?string $reference = null,
        public ?string $referenceDate = null,
        public ?string $referenceType = null,
        public ?string $attach = null,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $data = [];
        $optionals = [
            'AwbBarcode' => $this->awbBarcode,
            'PieceBarcode' => $this->pieceBarcode,
            'Reference' => $this->reference,
            'ReferenceDate' => $this->referenceDate,
            'ReferenceType' => $this->referenceType,
            'Attach' => $this->attach,
        ];

        foreach ($optionals as $key => $value) {
            if ($value !== null) {
                $data[$key] = $value;
            }
        }

        return $data;
    }
}
