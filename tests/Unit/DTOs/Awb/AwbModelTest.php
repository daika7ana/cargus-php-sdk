<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\Awb;

use Daika7ana\Cargus\DTOs\Awb\AwbModel;
use Daika7ana\Cargus\DTOs\Awb\ParcelCode;
use PHPUnit\Framework\TestCase;

final class AwbModelTest extends TestCase
{
    public function testFromArrayMapsNestedAddressParcelsAndCost(): void
    {
        $awb = AwbModel::fromArray([
            'Sender' => ['Name' => 'Sender'],
            'Recipient' => ['Name' => 'Receiver', 'LocalityId' => 7],
            'Parcels' => 2,
            'Envelopes' => 1,
            'TotalWeight' => 5,
            'ShipmentPayer' => 3,
            'BarCode' => 'AWB1',
            'ParcelCodes' => [['Code' => 'P1', 'Type' => 0, 'Weight' => 1.5]],
            'ShippingCost' => ['GrandTotal' => 20.0],
        ]);

        self::assertSame('Sender', $awb->sender->name);
        self::assertSame(7, $awb->recipient->localityId);
        self::assertSame(2, $awb->parcels);
        self::assertSame('AWB1', $awb->barCode);
        self::assertInstanceOf(ParcelCode::class, $awb->parcelCodes->first());
        self::assertSame(1.5, $awb->parcelCodes->first()->weight);
        self::assertSame(20.0, $awb->shippingCost?->grandTotal);
    }

    public function testFromArrayDefaultsMissingValues(): void
    {
        $awb = AwbModel::fromArray([]);

        self::assertSame(0, $awb->totalWeight);
        self::assertSame('', $awb->status);
        self::assertCount(0, $awb->parcelCodes);
        self::assertNull($awb->shippingCost);
    }
}
