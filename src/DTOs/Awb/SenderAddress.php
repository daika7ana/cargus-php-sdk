<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Awb;

final readonly class SenderAddress
{
    public function __construct(
        public ?string $countyName = null,
        public ?int $localityId = null,
        public ?string $localityName = null,
        public ?string $zipCode = null,
    ) {}

    /**
     * @return array<string, int|string>
     */
    public function toArray(): array
    {
        $data = [];
        $optionals = [
            'CountyName' => $this->countyName,
            'LocalityId' => $this->localityId,
            'LocalityName' => $this->localityName,
            'ZipCode' => $this->zipCode,
        ];

        foreach ($optionals as $key => $value) {
            if ($value !== null) {
                $data[$key] = $value;
            }
        }

        return $data;
    }
}
