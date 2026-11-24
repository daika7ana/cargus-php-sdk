<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Smoke;

final class ClientSmokeTest extends SmokeTestCase
{
    public function testLiveTokenVerification(): void
    {
        self::assertTrue($this->client()->tokenVerification());
    }
}
