<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\Marketing;

use Daika7ana\Cargus\DTOs\Marketing\UpdatedUser;
use Daika7ana\Cargus\DTOs\Marketing\UpdatedUsersExtended;
use PHPUnit\Framework\TestCase;

final class MarketingDtoTest extends TestCase
{
    public function testUpdatedUsersExtendedReadsTheWrapper(): void
    {
        $result = UpdatedUsersExtended::fromArray([
            'UpdatedUsersModel' => [[
                'Email' => 'jane@example.com',
                'FirstName' => 'Jane',
                'MarketingEmail' => 1,
                'EmailConfirmed' => 1,
            ]],
            'TotalRecords' => 1,
        ]);

        self::assertSame(1, $result->totalRecords);
        self::assertCount(1, $result->updatedUsers);
        self::assertSame('Jane', $result->updatedUsers->first()->firstName);
        self::assertSame(1, $result->updatedUsers->first()->marketingEmail);
        self::assertSame(1, $result->updatedUsers->first()->emailConfirmed);
    }

    public function testUpdatedUserFallsBackToCamelCase(): void
    {
        $user = UpdatedUser::fromArray(['firstName' => 'John', 'phoneNumber' => '0700']);

        self::assertSame('John', $user->firstName);
        self::assertSame('0700', $user->phoneNumber);
    }
}
