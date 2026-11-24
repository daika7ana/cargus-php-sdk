<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\Order;

use Daika7ana\Cargus\DTOs\Order\OrderPickupClickCollectModel;
use PHPUnit\Framework\TestCase;

final class OrderPickupClickCollectModelTest extends TestCase
{
    public function testToArrayEmitsPascalCaseKeysAndDropsNullOptionals(): void
    {
        $model = new OrderPickupClickCollectModel(
            observations: 'ring twice',
            parcels: 4,
            locationId: 3,
        );

        self::assertSame([
            'Observations' => 'ring twice',
            'Parcels' => 4,
            'LocationId' => 3,
        ], $model->toArray());
    }
}
