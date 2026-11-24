<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\Awb;

use Daika7ana\Cargus\DTOs\Awb\AwbUpdate;
use PHPUnit\Framework\TestCase;

final class AwbUpdateTest extends TestCase
{
    public function testToArrayDropsNullOptionals(): void
    {
        $update = new AwbUpdate(awbBarcode: 'AWB1', reference: 'REF');

        self::assertSame(['AwbBarcode' => 'AWB1', 'Reference' => 'REF'], $update->toArray());
    }
}
