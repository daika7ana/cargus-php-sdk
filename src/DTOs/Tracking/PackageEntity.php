<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Tracking;

use Daika7ana\SimpleCollections\Collection;

final readonly class PackageEntity
{
    /**
     * @param Collection<int, TraceEvent> $events
     */
    public function __construct(
        public string $code,
        public string $type,
        public float $measuredWeight,
        public float $volumetricWeight,
        public string $confirmationName,
        public string $observation,
        public string $responseCode,
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

        foreach (is_array($data['Event'] ?? null) ? $data['Event'] : [] as $event) {
            if (is_array($event)) {
                $events[] = TraceEvent::fromArray($event);
            }
        }

        return new self(
            code: (string) ($data['Code'] ?? ''),
            type: (string) ($data['Type'] ?? ''),
            measuredWeight: (float) ($data['MeasuredWeight'] ?? 0.0),
            volumetricWeight: (float) ($data['VolumetricWeight'] ?? 0.0),
            confirmationName: (string) ($data['ConfirmationName'] ?? ''),
            observation: (string) ($data['Observation'] ?? ''),
            responseCode: (string) ($data['ResponseCode'] ?? ''),
            orderStatus: (int) ($data['OrderStatus'] ?? 0),
            orderLate: (bool) ($data['OrderLate'] ?? false),
            events: new Collection($events),
        );
    }
}
