<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Support;

use Daika7ana\Cargus\Client;
use Daika7ana\Cargus\Config\ClientConfig;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class TestClientFactory
{
    public const SUBSCRIPTION_KEY = 'test-subscription-key';

    /**
     * @param callable(RequestInterface): ResponseInterface $handler
     * @return array{Client, FakeHttpClient}
     */
    public static function create(callable $handler, ?ClientConfig $config = null): array
    {
        $httpClient = new FakeHttpClient($handler);
        $factory = new HttpFactory();

        $client = Client::create(
            httpClient: $httpClient,
            requestFactory: $factory,
            streamFactory: $factory,
            config: $config ?? new ClientConfig(
                username: 'test-user',
                password: 'test-pass',
                subscriptionKey: self::SUBSCRIPTION_KEY,
            ),
        );

        return [$client, $httpClient];
    }
}
