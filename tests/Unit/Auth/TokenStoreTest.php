<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\Auth;

use Daika7ana\Cargus\Auth\InMemoryTokenStore;
use Daika7ana\Cargus\Auth\Token;
use Daika7ana\Cargus\Exceptions\AuthenticationException;
use PHPUnit\Framework\TestCase;

final class TokenStoreTest extends TestCase
{
    public function testStoreRoundTrip(): void
    {
        $store = new InMemoryTokenStore();
        $token = new Token('abc');

        self::assertNull($store->getToken());

        $store->setToken($token);

        self::assertSame($token, $store->getToken());
    }

    public function testTokenStringExpiresInTwentyFourHours(): void
    {
        $token = Token::fromTokenString('abc');

        self::assertSame('abc', $token->accessToken);
        self::assertSame('Bearer', $token->tokenType);
        self::assertNotNull($token->expiresAt);
        self::assertFalse($token->isExpired());
    }

    public function testEmptyTokenStringIsRejected(): void
    {
        $this->expectException(AuthenticationException::class);

        Token::fromTokenString('');
    }

    public function testTokenExpiringWithinAMinuteIsExpired(): void
    {
        $token = new Token('abc', new \DateTimeImmutable('+30 seconds'));

        self::assertTrue($token->isExpired());
    }
}
