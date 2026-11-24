<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\Exceptions;

use Daika7ana\Cargus\Exceptions\AuthenticationException;
use Daika7ana\Cargus\Exceptions\CargusException;
use Daika7ana\Cargus\Exceptions\TransportException;
use Daika7ana\Cargus\Exceptions\UnexpectedStatusException;
use Daika7ana\Cargus\Exceptions\ValidationException;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;

final class ExceptionHierarchyTest extends TestCase
{
    public function testAllExceptionsShareTheBaseClass(): void
    {
        self::assertInstanceOf(CargusException::class, new AuthenticationException());
        self::assertInstanceOf(CargusException::class, new ValidationException());
        self::assertInstanceOf(CargusException::class, new UnexpectedStatusException());
        self::assertInstanceOf(CargusException::class, new TransportException());
    }

    public function testTransportExceptionIsAPsr18ClientException(): void
    {
        self::assertInstanceOf(ClientExceptionInterface::class, new TransportException());
    }

    public function testAuthenticationCodeDerivesFromTheResponse(): void
    {
        self::assertSame(401, (new AuthenticationException('denied', response: new Response(401)))->getCode());
    }

    public function testValidationCodeDerivesFromTheResponse(): void
    {
        self::assertSame(422, (new ValidationException([], response: new Response(422)))->getCode());
    }

    public function testUnexpectedStatusCarriesResponseAndRetryAfter(): void
    {
        $response = new Response(503);
        $exception = new UnexpectedStatusException($response, retryAfter: 30);

        self::assertSame($response, $exception->response);
        self::assertSame(30, $exception->retryAfter);
        self::assertSame('Unexpected HTTP status 503', $exception->getMessage());
        self::assertSame(503, $exception->getCode());
    }

    public function testUnexpectedStatusAcceptsNoResponse(): void
    {
        $exception = new UnexpectedStatusException();

        self::assertNull($exception->response);
        self::assertSame('Unexpected HTTP status', $exception->getMessage());
    }
}
