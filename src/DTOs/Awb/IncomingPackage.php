<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Awb;

final readonly class IncomingPackage
{
    public function __construct(
        public int $packageId = 0,
        public string $packageBarCode = '',
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            packageId: (int) ($data['PackageId'] ?? 0),
            packageBarCode: (string) ($data['PackageBarCode'] ?? ''),
        );
    }
}
