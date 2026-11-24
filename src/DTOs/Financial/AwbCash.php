<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Financial;

final readonly class AwbCash
{
    public function __construct(
        public string $date = '',
        public string $barCode = '',
        public string $sender = '',
        public string $receiver = '',
        public string $fromLocality = '',
        public string $toLocality = '',
        public string $customString = '',
        public string $picture = '',
        public string $repaymentDate = '',
        public string $repaymentId = '',
        public float $repaymentValue = 0.0,
        public string $deductionDate = '',
        public string $deductionId = '',
        public string $statusExpression = '',
        public string $confirmationDate = '',
        public string $confirmationPersonName = '',
        public string $barCodeTur = '',
        public string $tariffSetName = '',
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            date: (string) ($data['Date'] ?? $data['date'] ?? ''),
            barCode: (string) ($data['BarCode'] ?? $data['barCode'] ?? ''),
            sender: (string) ($data['Sender'] ?? $data['sender'] ?? ''),
            receiver: (string) ($data['Receiver'] ?? $data['receiver'] ?? ''),
            fromLocality: (string) ($data['FromLocality'] ?? $data['fromLocality'] ?? ''),
            toLocality: (string) ($data['ToLocality'] ?? $data['toLocality'] ?? ''),
            customString: (string) ($data['CustomString'] ?? $data['customString'] ?? ''),
            picture: (string) ($data['Picture'] ?? $data['picture'] ?? ''),
            repaymentDate: (string) ($data['RepaymentDate'] ?? $data['repaymentDate'] ?? ''),
            repaymentId: (string) ($data['RepaymentId'] ?? $data['repaymentId'] ?? ''),
            repaymentValue: (float) ($data['RepaymentValue'] ?? $data['repaymentValue'] ?? 0.0),
            deductionDate: (string) ($data['DeductionDate'] ?? $data['deductionDate'] ?? ''),
            deductionId: (string) ($data['DeductionId'] ?? $data['deductionId'] ?? ''),
            statusExpression: (string) ($data['StatusExpression'] ?? $data['statusExpression'] ?? ''),
            confirmationDate: (string) ($data['ConfirmationDate'] ?? $data['confirmationDate'] ?? ''),
            confirmationPersonName: (string) ($data['ConfirmationPersonName'] ?? $data['confirmationPersonName'] ?? ''),
            barCodeTur: (string) ($data['BarCodeTur'] ?? $data['barCodeTur'] ?? ''),
            tariffSetName: (string) ($data['TariffSetName'] ?? $data['tariffSetName'] ?? ''),
        );
    }
}
