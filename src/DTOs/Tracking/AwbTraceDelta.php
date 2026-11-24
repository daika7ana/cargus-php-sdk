<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Tracking;

use Daika7ana\SimpleCollections\Collection;

final readonly class AwbTraceDelta
{
    /**
     * @param Collection<int, PackageDelta> $packages
     */
    public function __construct(
        public string $date,
        public string $barCode,
        public string $sender,
        public string $receiver,
        public string $fromCounty,
        public string $fromLocality,
        public string $toCounty,
        public string $toLocality,
        public string $customString,
        public string $repaymentDate,
        public string $repaymentId,
        public float $repaymentValue,
        public string $deductionDate,
        public string $deductionId,
        public string $statusExpression,
        public string $confirmationDate,
        public string $confirmationPersonName,
        public string $initialAwb,
        public string $responseCode,
        public Collection $packages,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $packages = [];

        foreach (is_array($data['Packages'] ?? null) ? $data['Packages'] : [] as $package) {
            if (is_array($package)) {
                $packages[] = PackageDelta::fromArray($package);
            }
        }

        return new self(
            date: (string) ($data['Date'] ?? ''),
            barCode: (string) ($data['BarCode'] ?? ''),
            sender: (string) ($data['Sender'] ?? ''),
            receiver: (string) ($data['Receiver'] ?? ''),
            fromCounty: (string) ($data['FromCounty'] ?? ''),
            fromLocality: (string) ($data['FromLocality'] ?? ''),
            toCounty: (string) ($data['ToCounty'] ?? ''),
            toLocality: (string) ($data['ToLocality'] ?? ''),
            customString: (string) ($data['CustomString'] ?? ''),
            repaymentDate: (string) ($data['RepaymentDate'] ?? ''),
            repaymentId: (string) ($data['RepaymentId'] ?? ''),
            repaymentValue: (float) ($data['RepaymentValue'] ?? 0.0),
            deductionDate: (string) ($data['DeductionDate'] ?? ''),
            deductionId: (string) ($data['DeductionId'] ?? ''),
            statusExpression: (string) ($data['StatusExpression'] ?? ''),
            confirmationDate: (string) ($data['ConfirmationDate'] ?? ''),
            confirmationPersonName: (string) ($data['ConfirmationPersonName'] ?? ''),
            initialAwb: (string) ($data['InitialAwb'] ?? ''),
            responseCode: (string) ($data['ResponseCode'] ?? ''),
            packages: new Collection($packages),
        );
    }
}
