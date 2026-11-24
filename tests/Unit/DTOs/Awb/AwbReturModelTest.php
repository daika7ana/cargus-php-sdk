<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\Awb;

use Daika7ana\Cargus\DTOs\Awb\AwbReturModel;
use PHPUnit\Framework\TestCase;

final class AwbReturModelTest extends TestCase
{
    public function testFromArrayMapsNestedAwbs(): void
    {
        $retur = AwbReturModel::fromArray([
            'Awb' => ['BarCode' => 'AWB1'],
            'AwbReturn' => ['BarCode' => 'AWB2'],
            'Courier' => 'Courier',
            'ClientObservation' => 'obs',
        ]);

        self::assertSame('AWB1', $retur->awb?->barCode);
        self::assertSame('AWB2', $retur->awbReturn?->barCode);
        self::assertSame('Courier', $retur->courier);
        self::assertSame('obs', $retur->clientObservation);
    }
}
