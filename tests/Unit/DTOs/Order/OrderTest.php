<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\Order;

use Daika7ana\Cargus\DTOs\Order\Order;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function testFromArrayReadsPascalCaseKeys(): void
    {
        $order = Order::fromArray([
            'OrderId' => 10,
            'LocationId' => 3,
            'PickUpSiteName' => 'Site',
            'NoParcel' => 2,
            'TotalWeight' => 5,
            'OrdStatus' => 2,
            'OrdStatusDetails' => 'Validated',
            'Email' => 'a@example.com',
            'OrderType' => 1,
        ]);

        self::assertSame(10, $order->orderId);
        self::assertSame(3, $order->locationId);
        self::assertSame('Site', $order->pickUpSiteName);
        self::assertSame(2, $order->noParcel);
        self::assertSame(5, $order->totalWeight);
        self::assertSame(2, $order->ordStatus);
        self::assertSame('Validated', $order->ordStatusDetails);
        self::assertSame('a@example.com', $order->email);
        self::assertSame(1, $order->orderType);
    }

    public function testFromArrayFallsBackToCamelCaseKeys(): void
    {
        $order = Order::fromArray(['orderId' => 11, 'ordStatus' => 1, 'pickUpSiteName' => 'Other']);

        self::assertSame(11, $order->orderId);
        self::assertSame(1, $order->ordStatus);
        self::assertSame('Other', $order->pickUpSiteName);
    }
}
