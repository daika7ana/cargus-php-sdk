<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Geography;

final readonly class PudoPaymentType
{
    public function __construct(
        public bool $cash = false,
        public bool $card = false,
        public bool $online = false,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            cash: (bool) ($data['Cash'] ?? $data['cash'] ?? false),
            card: (bool) ($data['Card'] ?? $data['card'] ?? false),
            online: (bool) ($data['Online'] ?? $data['online'] ?? false),
        );
    }
}
