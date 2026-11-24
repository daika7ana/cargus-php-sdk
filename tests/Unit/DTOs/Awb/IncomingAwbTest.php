<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\Awb;

use Daika7ana\Cargus\DTOs\Awb\IncomingAwb;
use Daika7ana\Cargus\DTOs\Awb\IncomingPackage;
use PHPUnit\Framework\TestCase;

final class IncomingAwbTest extends TestCase
{
    public function testFromArrayMapsReturnAddressAndPackages(): void
    {
        $awb = IncomingAwb::fromArray([
            'AwbId' => 9,
            'AwbCode' => 'IN1',
            'AwbAmount' => 4.5,
            'ReturnAddress' => ['Name' => 'Return'],
            'Packages' => [['PackageId' => 3, 'PackageBarCode' => 'PK3']],
        ]);

        self::assertSame(9, $awb->awbId);
        self::assertSame('IN1', $awb->awbCode);
        self::assertSame(4.5, $awb->awbAmount);
        self::assertSame('Return', $awb->returnAddress?->name);
        self::assertInstanceOf(IncomingPackage::class, $awb->packages->first());
        self::assertSame('PK3', $awb->packages->first()->packageBarCode);
    }
}
