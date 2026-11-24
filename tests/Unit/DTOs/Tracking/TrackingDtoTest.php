<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\Tracking;

use Daika7ana\Cargus\DTOs\Financial\AwbCash;
use Daika7ana\Cargus\DTOs\Tracking\AwbTraceDelta;
use Daika7ana\Cargus\DTOs\Tracking\PackageEntity;
use PHPUnit\Framework\TestCase;

final class TrackingDtoTest extends TestCase
{
    public function testPackageEntityMapsEventsAndTypes(): void
    {
        $package = PackageEntity::fromArray([
            'Code' => 'ABC123',
            'Type' => 'Parcel',
            'MeasuredWeight' => 2.5,
            'VolumetricWeight' => 3,
            'ConfirmationName' => 'Pop Ion',
            'Observation' => 'ok',
            'ResponseCode' => '200',
            'OrderStatus' => 1,
            'OrderLate' => true,
            'Event' => [[
                'Date' => '2026-11-20T10:00:00',
                'EventId' => 7,
                'Description' => 'Delivered',
                'LocalityName' => 'Cluj',
            ]],
        ]);

        self::assertSame('ABC123', $package->code);
        self::assertSame(2.5, $package->measuredWeight);
        self::assertSame(3.0, $package->volumetricWeight);
        self::assertTrue($package->orderLate);
        self::assertCount(1, $package->events);
        self::assertSame(7, $package->events->first()->eventId);
    }

    public function testAwbTraceDeltaMapsNestedPackages(): void
    {
        $trace = AwbTraceDelta::fromArray([
            'BarCode' => 'ABC123',
            'RepaymentValue' => 100.5,
            'Packages' => [[
                'Code' => 'ABC123',
                'CodeNT' => 'NT1',
                'Events' => [[
                    'Description' => 'Delivered',
                    'CountyName' => 'Cluj',
                ]],
            ]],
        ]);

        self::assertSame('ABC123', $trace->barCode);
        self::assertSame(100.5, $trace->repaymentValue);
        self::assertCount(1, $trace->packages);
        self::assertSame('NT1', $trace->packages->first()->codeNT);
        self::assertSame('Cluj', $trace->packages->first()->events->first()->countyName);
    }

    public function testAwbCashMapsTheStatusFields(): void
    {
        $cash = AwbCash::fromArray([
            'BarCode' => 'ABC123',
            'RepaymentValue' => 100.5,
            'StatusExpression' => 'Livrat',
        ]);

        self::assertSame('ABC123', $cash->barCode);
        self::assertSame(100.5, $cash->repaymentValue);
        self::assertSame('Livrat', $cash->statusExpression);
        self::assertSame('', $cash->tariffSetName);
    }
}
