<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Marketing;

final readonly class UpdatedUser
{
    public function __construct(
        public string $phoneNumber = '',
        public string $email = '',
        public string $firstName = '',
        public string $lastName = '',
        public int $marketingEmail = 0,
        public int $marketingSms = 0,
        public int $marketingPush = 0,
        public int $emailConfirmed = 0,
        public string $platformActivity = '',
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            phoneNumber: (string) ($data['PhoneNumber'] ?? $data['phoneNumber'] ?? ''),
            email: (string) ($data['Email'] ?? $data['email'] ?? ''),
            firstName: (string) ($data['FirstName'] ?? $data['firstName'] ?? ''),
            lastName: (string) ($data['LastName'] ?? $data['lastName'] ?? ''),
            marketingEmail: (int) ($data['MarketingEmail'] ?? $data['marketingEmail'] ?? 0),
            marketingSms: (int) ($data['MarketingSms'] ?? $data['marketingSms'] ?? 0),
            marketingPush: (int) ($data['MarketingPush'] ?? $data['marketingPush'] ?? 0),
            emailConfirmed: (int) ($data['EmailConfirmed'] ?? $data['emailConfirmed'] ?? 0),
            platformActivity: (string) ($data['PlatformActivity'] ?? $data['platformActivity'] ?? ''),
        );
    }
}
