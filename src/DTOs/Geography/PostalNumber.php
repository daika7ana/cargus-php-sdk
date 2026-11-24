<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Geography;

final readonly class PostalNumber
{
    public function __construct(
        public string $fromNo = '',
        public string $toNo = '',
        public string $postalCode = '',
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            fromNo: (string) ($data['FromNo'] ?? $data['fromNo'] ?? ''),
            toNo: (string) ($data['ToNo'] ?? $data['toNo'] ?? ''),
            postalCode: (string) ($data['PostalCode'] ?? $data['postalCode'] ?? ''),
        );
    }
}
