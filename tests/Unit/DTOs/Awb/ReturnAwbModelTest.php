<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\Awb;

use Daika7ana\Cargus\DTOs\Awb\ReturnAwbModel;
use PHPUnit\Framework\TestCase;

final class ReturnAwbModelTest extends TestCase
{
    public function testToArrayDropsNullOptionalsAndKeepsFalse(): void
    {
        $model = new ReturnAwbModel(barcode: 'AWB1', hasContract: false);

        self::assertSame(['Barcode' => 'AWB1', 'HasContract' => false], $model->toArray());
    }
}
