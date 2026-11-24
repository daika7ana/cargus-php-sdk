<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Geography;

use Daika7ana\SimpleCollections\Collection;

final readonly class Street
{
    /**
     * @param Collection<int, PostalNumber> $postalNumbers
     */
    public function __construct(
        public int $id,
        public string $name,
        public Collection $postalNumbers,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $postalNumbers = [];

        foreach (is_array($data['PostalNumbers'] ?? null) ? $data['PostalNumbers'] : [] as $item) {
            if (is_array($item)) {
                $postalNumbers[] = PostalNumber::fromArray($item);
            }
        }

        return new self(
            id: (int) ($data['StreetId'] ?? $data['streetId'] ?? 0),
            name: (string) ($data['Name'] ?? $data['name'] ?? ''),
            postalNumbers: new Collection($postalNumbers),
        );
    }
}
