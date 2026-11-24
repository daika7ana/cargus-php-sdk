<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Awb;

use Daika7ana\SimpleCollections\Collection;

final readonly class IncomingAwb
{
    /**
     * @param Collection<int, IncomingPackage> $packages
     */
    public function __construct(
        public int $awbId = 0,
        public string $awbCode = '',
        public string $awbDate = '',
        public float $awbAmount = 0.0,
        public int $orderId = 0,
        public string $customer = '',
        public ?Address $returnAddress = null,
        public string $minihub = '',
        public int $locationId = 0,
        public Collection $packages = new Collection(),
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $packages = [];

        foreach (is_array($data['Packages'] ?? null) ? $data['Packages'] : [] as $package) {
            if (is_array($package)) {
                $packages[] = IncomingPackage::fromArray($package);
            }
        }

        $returnAddress = $data['ReturnAddress'] ?? null;

        return new self(
            awbId: (int) ($data['AwbId'] ?? 0),
            awbCode: (string) ($data['AwbCode'] ?? ''),
            awbDate: (string) ($data['AwbDate'] ?? ''),
            awbAmount: (float) ($data['AwbAmount'] ?? 0.0),
            orderId: (int) ($data['OrderId'] ?? 0),
            customer: (string) ($data['Customer'] ?? ''),
            returnAddress: is_array($returnAddress) ? Address::fromArray($returnAddress) : null,
            minihub: (string) ($data['Minihub'] ?? ''),
            locationId: (int) ($data['LocationId'] ?? 0),
            packages: new Collection($packages),
        );
    }
}
