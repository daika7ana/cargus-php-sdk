<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Smoke;

use Daika7ana\SimpleCollections\Collection;

final class AwbReadSmokeTest extends SmokeTestCase
{
    public function testGetByDateReturnsAwbList(): void
    {
        $from = gmdate('Y-m-d', strtotime('-1 day'));
        $to = gmdate('Y-m-d');

        $awbs = $this->client()->awbs()->getByDate($from, $to, 1, 5);

        self::assertInstanceOf(Collection::class, $awbs);
        self::assertLessThanOrEqual(5, $awbs->count());
    }

    public function testTraceWithConfiguredBarcode(): void
    {
        $barCode = $this->env('CARGUS_SMOKE_BARCODE');

        if ($barCode === null) {
            $this->markTestSkipped('Set CARGUS_SMOKE_BARCODE to an existing AWB barcode to run trace smoke tests.');
        }

        $client = $this->client();

        self::assertInstanceOf(Collection::class, $client->tracking()->trace($barCode));
        self::assertInstanceOf(Collection::class, $client->tracking()->shortTraceNoAuth($barCode));
    }
}
