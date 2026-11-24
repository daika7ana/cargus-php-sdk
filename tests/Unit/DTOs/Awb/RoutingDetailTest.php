<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\Awb;

use Daika7ana\Cargus\DTOs\Awb\RecipientAddress;
use Daika7ana\Cargus\DTOs\Awb\RoutingDetail;
use Daika7ana\Cargus\DTOs\Awb\SenderAddress;
use PHPUnit\Framework\TestCase;

final class RoutingDetailTest extends TestCase
{
    public function testToArrayEmitsNestedAddressesAndDropsNulls(): void
    {
        $detail = new RoutingDetail(
            totalWeight: 5,
            sender: new SenderAddress(countyName: 'Cluj', localityId: 1),
            recipient: new RecipientAddress(localityId: 2, streetName: 'Main'),
        );

        self::assertSame([
            'TotalWeight' => 5,
            'Sender' => ['CountyName' => 'Cluj', 'LocalityId' => 1],
            'Recipient' => ['LocalityId' => 2, 'StreetName' => 'Main'],
        ], $detail->toArray());
    }

    public function testToArrayReturnsEmptyArrayWhenNothingIsSet(): void
    {
        self::assertSame([], (new RoutingDetail())->toArray());
    }
}
