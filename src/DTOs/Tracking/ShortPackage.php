<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Tracking;

use Daika7ana\SimpleCollections\Collection;

final readonly class ShortPackage
{
    /**
     * @param Collection<int, TraceEvent> $events
     */
    public function __construct(
        public string $awbBarcode,
        public string $awbStatus,
        public string $undeliveredPieces,
        public string $envelope,
        public string $parcel,
        public string $confirmedDate,
        public string $confirmedBy,
        public string $responseCode,
        public string $transitTime,
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
            awbBarcode: (string) ($data['AwbBarcode'] ?? ''),
            awbStatus: (string) ($data['AwbStatus'] ?? ''),
            undeliveredPieces: (string) ($data['UndeliveredPieces'] ?? ''),
            envelope: (string) ($data['Envelope'] ?? ''),
            parcel: (string) ($data['Parcel'] ?? ''),
            confirmedDate: (string) ($data['ConfirmedDate'] ?? ''),
            confirmedBy: (string) ($data['ConfirmedBy'] ?? ''),
            responseCode: (string) ($data['ResponseCode'] ?? ''),
            transitTime: (string) ($data['TransitTime'] ?? ''),
            events: new Collection($events),
        );
    }
}
