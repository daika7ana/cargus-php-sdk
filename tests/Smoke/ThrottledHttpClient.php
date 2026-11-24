<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Smoke;

use Daika7ana\Cargus\Http\GuzzleHttpClient;
use Daika7ana\Cargus\Http\HttpClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Retries the shared test account's intermittent 409/429 throttling responses.
 * Test-support only: the SDK transport stays retry-free.
 */
final class ThrottledHttpClient implements HttpClientInterface
{
    private const RETRY_STATUSES = [409, 429];

    private const BACKOFF_SECONDS = [2, 4];

    public function __construct(private HttpClientInterface $inner = new GuzzleHttpClient()) {}

    public function sendRequest(RequestInterface $request, ?float $timeout = null): ResponseInterface
    {
        $response = $this->inner->sendRequest($request, $timeout);

        foreach (self::BACKOFF_SECONDS as $backoff) {
            if (!in_array($response->getStatusCode(), self::RETRY_STATUSES, true)) {
                return $response;
            }

            sleep($backoff);
            $body = $request->getBody();

            if ($body->isSeekable()) {
                $body->rewind();
            }

            $response = $this->inner->sendRequest($request, $timeout);
        }

        return $response;
    }
}
