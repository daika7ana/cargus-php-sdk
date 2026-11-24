<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\Awb;

use Daika7ana\Cargus\DTOs\Awb\AwbExtended;
use PHPUnit\Framework\TestCase;

final class AwbExtendedTest extends TestCase
{
    public function testFromArrayMapsExtendedFields(): void
    {
        $awb = AwbExtended::fromArray([
            'Sender' => ['Name' => 'Sender'],
            'Recipient' => ['Name' => 'Receiver'],
            'ReturnCode' => 'RC',
            'ReturnAwb' => 'AWB-RET',
            'IdComanda' => 55,
            'BarCode' => 'AWB1',
            'ParcelCodes' => [['Code' => 'P1', 'Type' => 0]],
        ]);

        self::assertSame('Sender', $awb->sender->name);
        self::assertSame('RC', $awb->returnCode);
        self::assertSame('AWB-RET', $awb->returnAwb);
        self::assertSame(55, $awb->idComanda);
        self::assertCount(1, $awb->parcelCodes);
    }
}
