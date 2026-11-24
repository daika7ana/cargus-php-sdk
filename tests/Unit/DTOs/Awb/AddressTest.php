<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\Awb;

use Daika7ana\Cargus\DTOs\Awb\Address;
use PHPUnit\Framework\TestCase;

final class AddressTest extends TestCase
{
    public function testFromArrayReadsPascalCaseAndFallsBackToCamelCase(): void
    {
        $address = Address::fromArray([
            'LocationId' => 1,
            'Name' => 'Acme',
            'CountyId' => 2,
            'countyName' => 'Cluj',
            'PostalCode' => '400001',
            'CityCenterId' => 9,
        ]);

        self::assertSame(1, $address->locationId);
        self::assertSame('Acme', $address->name);
        self::assertSame(2, $address->countyId);
        self::assertSame('Cluj', $address->countyName);
        self::assertSame('400001', $address->postalCode);
        self::assertSame(9, $address->cityCenterId);
        self::assertNull($address->email);
    }

    public function testToArrayDropsNullOptionals(): void
    {
        $address = new Address(name: 'Acme', localityId: 3);

        self::assertSame(['Name' => 'Acme', 'LocalityId' => 3], $address->toArray());
    }
}
