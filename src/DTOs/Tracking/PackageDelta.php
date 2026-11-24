<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Tracking;

use Daika7ana\SimpleCollections\Collection;

final readonly class PackageDelta
{
    /**
     * @param Collection<int, TraceEvent> $events
     */
    public function __construct(
        public string $code,
        public string $codeNT,
        public string $type,
        public float $measuredWeight,
        public float $volumetricWeight,
        public int $orderStatus,
        public bool $orderLate,
        public Collection $events,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $events = [];

        foreach (is_array($data['Events'] ?? null) ? $data['Events'] : [] as $event) {
            if (is_array($event)) {
                $events[] = TraceEvent::fromArray($event);
            }
        }

        return new self(
            code: (string) ($data['Code'] ?? ''),
            codeNT: (string) ($data['CodeNT'] ?? ''),
            type: (string) ($data['Type'] ?? ''),
            measuredWeight: (float) ($data['MeasuredWeight'] ?? 0.0),
            volumetricWeight: (float) ($data['VolumetricWeight'] ?? 0.0),
            orderStatus: (int) ($data['OrderStatus'] ?? 0),
            orderLate: (bool) ($data['OrderLate'] ?? false),
            events: new Collection($events),
        );
    }
}
