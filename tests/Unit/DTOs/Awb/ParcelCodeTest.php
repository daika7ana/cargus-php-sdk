<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\Awb;

use Daika7ana\Cargus\DTOs\Awb\ParcelCode;
use PHPUnit\Framework\TestCase;

final class ParcelCodeTest extends TestCase
{
    public function testFromArrayMapsAllFields(): void
    {
        $parcelCode = ParcelCode::fromArray([
            'Code' => 'P1',
            'Type' => 1,
            'Weight' => 2.5,
            'Length' => 10,
            'Width' => 11,
            'Height' => 12,
            'ParcelContent' => 'Books',
            'NetAmount' => 99.5,
        ]);

        self::assertSame('P1', $parcelCode->code);
        self::assertSame(1, $parcelCode->type);
        self::assertSame(2.5, $parcelCode->weight);
        self::assertSame(10, $parcelCode->length);
        self::assertSame(11, $parcelCode->width);
        self::assertSame(12, $parcelCode->height);
        self::assertSame('Books', $parcelCode->parcelContent);
        self::assertSame(99.5, $parcelCode->netAmount);
    }

    public function testToArrayDropsNullOptionals(): void
    {
        self::assertSame(['Code' => 'P1', 'Type' => 0], (new ParcelCode('P1', 0))->toArray());
    }
}
