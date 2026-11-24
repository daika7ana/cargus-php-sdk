<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Tracking;

final readonly class TraceEvent
{
    public function __construct(
        public string $date,
        public int $eventId,
        public string $description,
        public string $localityName,
        public string $countyName = '',
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            date: (string) ($data['Date'] ?? ''),
            eventId: (int) ($data['EventId'] ?? 0),
            description: (string) ($data['Description'] ?? ''),
            localityName: (string) ($data['LocalityName'] ?? ''),
            countyName: (string) ($data['CountyName'] ?? ''),
        );
    }
}
