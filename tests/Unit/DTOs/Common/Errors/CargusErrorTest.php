<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\Common\Errors;

use Daika7ana\Cargus\DTOs\Common\Errors\CargusError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CargusErrorTest extends TestCase
{
    public function testFromArrayMapsTheFailureEnvelope(): void
    {
        $raw = ['message' => 'Invalid AWB', 'id' => 9, 'context' => 'awb', 'component' => 'label'];
        $error = CargusError::fromArray($raw, 502);

        self::assertSame(502, $error->code);
        self::assertSame('Invalid AWB', $error->message);
        self::assertSame('9', $error->id);
        self::assertSame($raw, $error->raw);
        self::assertSame('awb', $error->context);
        self::assertSame('label', $error->component);
    }

    public function testMessageFallsBackThroughTheAlternateKeys(): void
    {
        self::assertSame('boom', CargusError::fromArray(['Message' => 'boom'])->message);
        self::assertSame('boom', CargusError::fromArray(['error' => 'boom'])->message);
        self::assertSame('boom', CargusError::fromArray(['Error' => 'boom'])->message);
        self::assertSame('Cargus API error.', CargusError::fromArray([])->message);
    }

    public function testFieldErrorsAreNormalized(): void
    {
        $error = CargusError::fromArray(['errors' => ['weight' => ['required', 5], 'phone' => 'ignored']]);

        self::assertSame(['weight' => ['required']], $error->errors);
    }

    public function testValidationRelated(): void
    {
        self::assertTrue(CargusError::fromArray([], 400)->isValidationRelated());
        self::assertTrue(CargusError::fromArray([], 422)->isValidationRelated());
        self::assertTrue(CargusError::fromArray(['errors' => ['weight' => ['required']]])->isValidationRelated());
        self::assertFalse(CargusError::fromArray(['errors' => []])->isValidationRelated());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validationMessages(): iterable
    {
        yield 'invalid' => ['Invalid AWB'];
        yield 'required' => ['The weight field is required.'];
        yield 'mandatory' => ['The locality is mandatory.'];
        yield 'missing' => ['Missing recipient phone'];
        yield 'duplicate' => ['Duplicate order'];
        yield 'already' => ['This order was already deleted'];
        yield 'not valid' => ['The date is not valid'];
        yield 'validation' => ['Validation failed'];
        yield 'must be' => ['The weight must be greater than zero'];
    }

    #[DataProvider('validationMessages')]
    public function testValidationRelatedMessageHeuristics(string $message): void
    {
        self::assertTrue(CargusError::fromArray(['message' => $message])->isValidationRelated());
    }

    public function testUnclassifiedMessageIsNotValidationRelated(): void
    {
        self::assertFalse(CargusError::fromArray(['message' => 'Service unavailable'])->isValidationRelated());
    }

    public function testAuthenticationRelated(): void
    {
        self::assertTrue(CargusError::fromArray([], 401)->isAuthenticationRelated());
        self::assertTrue(CargusError::fromArray([], 403)->isAuthenticationRelated());
        self::assertTrue(CargusError::fromArray(['message' => 'Invalid credentials'])->isAuthenticationRelated());
        self::assertFalse(CargusError::fromArray(['message' => 'Invalid AWB'])->isAuthenticationRelated());
    }
}
