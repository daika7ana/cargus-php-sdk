<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\Order;

use Daika7ana\Cargus\DTOs\Order\OrderPickupModel;
use PHPUnit\Framework\TestCase;

final class OrderPickupModelTest extends TestCase
{
    public function testToArrayEmitsPascalCaseKeysAndDropsNullOptionals(): void
    {
        $model = new OrderPickupModel(
            parcels: 2,
            envelopes: 0,
            pickupStartDate: '2026-11-24T10:00:00',
            name: 'Sender',
            countryId: 1,
        );

        self::assertSame([
            'Parcels' => 2,
            'Envelopes' => 0,
            'PickupStartDate' => '2026-11-24T10:00:00',
            'Name' => 'Sender',
            'CountryId' => 1,
        ], $model->toArray());
    }
}
