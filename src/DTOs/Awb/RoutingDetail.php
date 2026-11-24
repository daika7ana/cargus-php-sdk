<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Awb;

final readonly class RoutingDetail
{
    public function __construct(
        public ?int $totalWeight = null,
        public ?SenderAddress $sender = null,
        public ?RecipientAddress $recipient = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];
        $optionals = [
            'TotalWeight' => $this->totalWeight,
            'Sender' => $this->sender === null ? null : $this->sender->toArray(),
            'Recipient' => $this->recipient === null ? null : $this->recipient->toArray(),
        ];

        foreach ($optionals as $key => $value) {
            if ($value !== null) {
                $data[$key] = $value;
            }
        }

        return $data;
    }
}
