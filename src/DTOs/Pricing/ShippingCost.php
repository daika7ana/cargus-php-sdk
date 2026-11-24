<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Pricing;

final readonly class ShippingCost
{
    public function __construct(
        public float $baseCost = 0.0,
        public float $extraKmCost = 0.0,
        public float $weightCost = 0.0,
        public float $insuranceCost = 0.0,
        public float $specialCost = 0.0,
        public float $repaymentCost = 0.0,
        public float $subtotal = 0.0,
        public float $tax = 0.0,
        public float $grandTotal = 0.0,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            baseCost: (float) ($data['BaseCost'] ?? $data['baseCost'] ?? 0.0),
            extraKmCost: (float) ($data['ExtraKmCost'] ?? $data['extraKmCost'] ?? 0.0),
            weightCost: (float) ($data['WeightCost'] ?? $data['weightCost'] ?? 0.0),
            insuranceCost: (float) ($data['InsuranceCost'] ?? $data['insuranceCost'] ?? 0.0),
            specialCost: (float) ($data['SpecialCost'] ?? $data['specialCost'] ?? 0.0),
            repaymentCost: (float) ($data['RepaymentCost'] ?? $data['repaymentCost'] ?? 0.0),
            subtotal: (float) ($data['Subtotal'] ?? $data['subtotal'] ?? 0.0),
            tax: (float) ($data['Tax'] ?? $data['tax'] ?? 0.0),
            grandTotal: (float) ($data['GrandTotal'] ?? $data['grandTotal'] ?? 0.0),
        );
    }
}
