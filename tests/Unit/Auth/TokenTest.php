<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\Auth;

use Daika7ana\Cargus\Auth\Token;
use Daika7ana\Cargus\Exceptions\AuthenticationException;
use PHPUnit\Framework\TestCase;

final class TokenTest extends TestCase
{
    public function testRoundTripThroughArray(): void
    {
        $token = Token::fromArray([
            'access_token' => 'abc',
            'token_type' => 'Bearer',
            'expires_at' => '2030-06-14T06:00:55+00:00',
        ]);

        self::assertSame('abc', $token->accessToken);
        self::assertSame('Bearer', $token->tokenType);
        self::assertSame('2030-06-14T06:00:55+00:00', $token->toArray()['expires_at']);
        self::assertFalse($token->isExpired());
    }

    public function testFromArrayDefaultsTokenTypeAndDropsMissingExpiry(): void
    {
        $token = Token::fromArray(['access_token' => 'abc']);

        self::assertSame('Bearer', $token->tokenType);
        self::assertNull($token->expiresAt);
        self::assertFalse($token->isExpired());
    }

    public function testFromArrayWithoutAccessTokenIsRejected(): void
    {
        $this->expectException(AuthenticationException::class);

        Token::fromArray(['token_type' => 'Bearer']);
    }

    public function testCorruptedExpiryIsTreatedAsExpired(): void
    {
        $token = Token::fromArray(['access_token' => 'abc', 'expires_at' => 'not-a-date']);

        self::assertTrue($token->isExpired());
    }

    public function testNullExpirySerializesAsNull(): void
    {
        $token = new Token('abc');

        self::assertNull($token->toArray()['expires_at']);
    }
}
