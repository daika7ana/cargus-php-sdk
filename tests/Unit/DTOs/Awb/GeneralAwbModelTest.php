<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\Awb;

use Daika7ana\Cargus\DTOs\Awb\Address;
use Daika7ana\Cargus\DTOs\Awb\GeneralAwbModel;
use Daika7ana\Cargus\DTOs\Awb\ParcelCode;
use PHPUnit\Framework\TestCase;

final class GeneralAwbModelTest extends TestCase
{
    public function testToArrayEmitsRequiredKeysAndDropsNullOptionals(): void
    {
        $awb = new GeneralAwbModel(
            sender: new Address(name: 'Sender'),
            recipient: new Address(name: 'Receiver'),
            parcels: 1,
            envelopes: 0,
            totalWeight: 3,
            shipmentPayer: 1,
            isReturn: true,
            parcelCodes: [new ParcelCode('G1', 2)],
        );

        self::assertSame([
            'Sender' => ['Name' => 'Sender'],
            'Recipient' => ['Name' => 'Receiver'],
            'Parcels' => 1,
            'Envelopes' => 0,
            'TotalWeight' => 3,
            'ShipmentPayer' => 1,
            'IsReturn' => true,
            'ParcelCodes' => [['Code' => 'G1', 'Type' => 2]],
        ], $awb->toArray());
    }
}
