<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\Awb;

use Daika7ana\Cargus\DTOs\Awb\Address;
use Daika7ana\Cargus\DTOs\Awb\AwbTurModel;
use Daika7ana\Cargus\DTOs\Awb\ParcelCode;
use PHPUnit\Framework\TestCase;

final class AwbTurModelTest extends TestCase
{
    public function testToArrayEmitsRequiredKeysAndDropsNullOptionals(): void
    {
        $awb = new AwbTurModel(
            sender: new Address(name: 'Sender'),
            recipient: new Address(name: 'Receiver'),
            parcels: 1,
            envelopes: 2,
            totalWeight: 3,
            shipmentPayer: 1,
            observations: 'Handle with care',
            parcelCodes: [new ParcelCode('P1', 0)],
        );

        self::assertSame([
            'Sender' => ['Name' => 'Sender'],
            'Recipient' => ['Name' => 'Receiver'],
            'Parcels' => 1,
            'Envelopes' => 2,
            'TotalWeight' => 3,
            'ShipmentPayer' => 1,
            'Observations' => 'Handle with care',
            'ParcelCodes' => [['Code' => 'P1', 'Type' => 0]],
        ], $awb->toArray());
    }

    public function testToArrayKeepsFalseOptionals(): void
    {
        $awb = new AwbTurModel(
            sender: new Address(),
            recipient: new Address(),
            parcels: 0,
            envelopes: 0,
            totalWeight: 0,
            shipmentPayer: 0,
            openPackage: false,
        );

        self::assertFalse($awb->toArray()['OpenPackage']);
    }
}
