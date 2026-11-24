<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Awb;

final readonly class ParcelCode
{
    public function __construct(
        public string $code,
        public int $type,
        public ?float $weight = null,
        public ?int $length = null,
        public ?int $width = null,
        public ?int $height = null,
        public ?string $parcelContent = null,
        public ?float $netAmount = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            code: (string) ($data['Code'] ?? $data['code'] ?? ''),
            type: (int) ($data['Type'] ?? $data['type'] ?? 0),
            weight: isset($data['Weight']) ? (float) $data['Weight'] : (isset($data['weight']) ? (float) $data['weight'] : null),
            length: isset($data['Length']) ? (int) $data['Length'] : (isset($data['length']) ? (int) $data['length'] : null),
            width: isset($data['Width']) ? (int) $data['Width'] : (isset($data['width']) ? (int) $data['width'] : null),
            height: isset($data['Height']) ? (int) $data['Height'] : (isset($data['height']) ? (int) $data['height'] : null),
            parcelContent: isset($data['ParcelContent']) ? (string) $data['ParcelContent'] : (isset($data['parcelContent']) ? (string) $data['parcelContent'] : null),
            netAmount: isset($data['NetAmount']) ? (float) $data['NetAmount'] : (isset($data['netAmount']) ? (float) $data['netAmount'] : null),
        );
    }

    /**
     * @return array<string, int|float|string>
     */
    public function toArray(): array
    {
        $data = [
            'Code' => $this->code,
            'Type' => $this->type,
        ];
        $optionals = [
            'Weight' => $this->weight,
            'Length' => $this->length,
            'Width' => $this->width,
            'Height' => $this->height,
            'ParcelContent' => $this->parcelContent,
            'NetAmount' => $this->netAmount,
        ];

        foreach ($optionals as $key => $value) {
            if ($value !== null) {
                $data[$key] = $value;
            }
        }

        return $data;
    }
}
