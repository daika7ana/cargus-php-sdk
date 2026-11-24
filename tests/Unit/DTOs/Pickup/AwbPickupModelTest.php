<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\Pickup;

use Daika7ana\Cargus\DTOs\Pickup\AwbPickupModel;
use Daika7ana\Cargus\DTOs\PickupLocation\AddressExtended;
use PHPUnit\Framework\TestCase;

final class AwbPickupModelTest extends TestCase
{
    public function testToArraySerializesNestedAddressesAndDropsNullOptionals(): void
    {
        $model = new AwbPickupModel(
            pickupStartDate: '2026-11-24T10:00:00',
            clientDropOff: false,
            sender: new AddressExtended(name: 'Sender', countyId: 11),
            parcels: 2,
        );

        self::assertSame([
            'PickupStartDate' => '2026-11-24T10:00:00',
            'ClientDropOff' => false,
            'Sender' => ['Name' => 'Sender', 'CountyId' => 11],
            'Parcels' => 2,
        ], $model->toArray());
    }
}
