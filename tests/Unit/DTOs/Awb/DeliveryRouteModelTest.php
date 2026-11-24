<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\Awb;

use Daika7ana\Cargus\DTOs\Awb\DeliveryRouteModel;
use PHPUnit\Framework\TestCase;

final class DeliveryRouteModelTest extends TestCase
{
    public function testFromArrayMapsRouteFields(): void
    {
        $route = DeliveryRouteModel::fromArray([
            'TransitPriority' => 'P',
            'TransitRoute' => 'R',
            'DeliveryRoute' => 'D',
            'OriginDeposit' => 'O',
            'Transit2DepositFinger' => 'F',
        ]);

        self::assertSame('P', $route->transitPriority);
        self::assertSame('R', $route->transitRoute);
        self::assertSame('D', $route->deliveryRoute);
        self::assertSame('O', $route->originDeposit);
        self::assertSame('F', $route->transit2DepositFinger);
        self::assertSame('', $route->transit1Deposit);
    }
}
