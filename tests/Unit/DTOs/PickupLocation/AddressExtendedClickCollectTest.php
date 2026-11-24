<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\PickupLocation;

use Daika7ana\Cargus\DTOs\PickupLocation\AddressExtendedClickCollect;
use PHPUnit\Framework\TestCase;

final class AddressExtendedClickCollectTest extends TestCase
{
    public function testFromArrayAndToArrayUsePascalCaseKeys(): void
    {
        $address = AddressExtendedClickCollect::fromArray([
            'MinihubId' => 'MH1',
            'Active' => 1,
            'LocationId' => 4,
            'Name' => 'Locker',
        ]);

        self::assertSame('MH1', $address->minihubId);
        self::assertSame(1, $address->active);
        self::assertSame(4, $address->locationId);
        self::assertSame([
            'MinihubId' => 'MH1',
            'Active' => 1,
            'LocationId' => 4,
            'Name' => 'Locker',
        ], $address->toArray());
    }
}
