<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\Awb;

use Daika7ana\Cargus\DTOs\Awb\AwbParcelUpdateModel;
use Daika7ana\Cargus\DTOs\Awb\ParcelCode;
use PHPUnit\Framework\TestCase;

final class AwbParcelUpdateModelTest extends TestCase
{
    public function testToArrayMapsBarcodeAndParcelCodes(): void
    {
        $update = new AwbParcelUpdateModel('AWB1', [new ParcelCode('P1', 1)]);

        self::assertSame([
            'Barcode' => 'AWB1',
            'ParcelCodes' => [['Code' => 'P1', 'Type' => 1]],
        ], $update->toArray());
    }
}
