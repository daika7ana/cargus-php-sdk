<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Support;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

final class Fixture
{
    /**
     * @param array<string, string> $headers
     */
    public static function json(mixed $data, int $status = 200, array $headers = []): ResponseInterface
    {
        return new Response(
            $status,
            ['Content-Type' => 'application/json'] + $headers,
            json_encode($data, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @param array<string, string> $headers
     */
    public static function raw(string $body, int $status = 200, array $headers = []): ResponseInterface
    {
        return new Response($status, $headers, $body);
    }
}
