<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Awb;

final readonly class AwbReturModel
{
    public function __construct(
        public ?AwbModel $awb = null,
        public ?AwbModel $awbReturn = null,
        public string $courier = '',
        public string $timestampDeparture = '',
        public string $clientObservation = '',
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $awb = $data['Awb'] ?? null;
        $awbReturn = $data['AwbReturn'] ?? null;

        return new self(
            awb: is_array($awb) ? AwbModel::fromArray($awb) : null,
            awbReturn: is_array($awbReturn) ? AwbModel::fromArray($awbReturn) : null,
            courier: (string) ($data['Courier'] ?? ''),
            timestampDeparture: (string) ($data['TimestampDeparture'] ?? ''),
            clientObservation: (string) ($data['ClientObservation'] ?? ''),
        );
    }
}
