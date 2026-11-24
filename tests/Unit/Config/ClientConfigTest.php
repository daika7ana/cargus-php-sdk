<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\Config;

use Daika7ana\Cargus\Config\ClientConfig;
use PHPUnit\Framework\TestCase;

final class ClientConfigTest extends TestCase
{
    private const VARS = [
        'CARGUS_USERNAME',
        'CARGUS_PASSWORD',
        'CARGUS_SUBSCRIPTION_KEY',
        'CARGUS_BASE_URL',
        'CARGUS_TIMEOUT',
    ];

    protected function tearDown(): void
    {
        foreach (self::VARS as $name) {
            putenv($name);
        }
    }

    public function testReadsEveryEnvironmentVariable(): void
    {
        putenv('CARGUS_USERNAME=user');
        putenv('CARGUS_PASSWORD=pass');
        putenv('CARGUS_SUBSCRIPTION_KEY=key');
        putenv('CARGUS_BASE_URL=https://example.test/');
        putenv('CARGUS_TIMEOUT=5');

        $config = ClientConfig::fromEnvironment();

        self::assertSame('user', $config->username);
        self::assertSame('pass', $config->password);
        self::assertSame('key', $config->subscriptionKey);
        self::assertSame('https://example.test', $config->baseUrl);
        self::assertSame(5.0, $config->timeout);
    }

    public function testFallsBackToTheDocumentedDefaults(): void
    {
        $config = ClientConfig::fromEnvironment();

        self::assertSame('', $config->username);
        self::assertSame('', $config->password);
        self::assertSame('', $config->subscriptionKey);
        self::assertSame(ClientConfig::BASE_URL, $config->baseUrl);
        self::assertNull($config->timeout);
    }

    public function testWithTimeoutReturnsNewInstance(): void
    {
        $original = new ClientConfig();
        $modified = $original->withTimeout(30.0);

        self::assertSame(30.0, $modified->timeout);
        self::assertNull($original->timeout);
    }

    public function testWithTimeoutNullClearsValue(): void
    {
        $config = (new ClientConfig(timeout: 30.0))->withTimeout(null);

        self::assertNull($config->timeout);
    }

    public function testNonNumericTimeoutIsRejected(): void
    {
        putenv('CARGUS_TIMEOUT=soon');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CARGUS_TIMEOUT must be numeric.');

        ClientConfig::fromEnvironment();
    }
}
