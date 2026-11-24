<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\PickupLocation;

use Daika7ana\Cargus\DTOs\PickupLocation\AddressExtended;
use PHPUnit\Framework\TestCase;

final class AddressExtendedTest extends TestCase
{
    public function testFromArrayReadsPascalCaseAndCamelCaseKeys(): void
    {
        $address = AddressExtended::fromArray([
            'AutomaticEOD' => '1',
            'LocationId' => 4,
            'Name' => 'Depot',
            'CountyId' => 11,
            'AddressText' => 'Str. A 10',
            'postalCode' => '010101',
        ]);

        self::assertSame('1', $address->automaticEOD);
        self::assertSame(4, $address->locationId);
        self::assertSame('Depot', $address->name);
        self::assertSame(11, $address->countyId);
        self::assertSame('Str. A 10', $address->addressText);
        self::assertSame('010101', $address->postalCode);
    }

    public function testToArrayEmitsPascalCaseKeysAndDropsNullOptionals(): void
    {
        $address = new AddressExtended(locationId: 4, name: 'Depot');

        self::assertSame(['LocationId' => 4, 'Name' => 'Depot'], $address->toArray());
    }
}
