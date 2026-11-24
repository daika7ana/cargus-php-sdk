<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\Resources;

use Daika7ana\Cargus\Auth\Token;
use Daika7ana\Cargus\Tests\Support\Fixture;
use Daika7ana\Cargus\Tests\Support\TestClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class MarketingResourceTest extends TestCase
{
    /**
     * @return array<string, string>
     */
    private static function query(RequestInterface $request): array
    {
        parse_str($request->getUri()->getQuery(), $query);

        /** @var array<string, string> $query */
        return $query;
    }

    public function testUpdatedUsersSendsTheRequiredQueryAndMapsTheWrapper(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([
                'UpdatedUsersModel' => [[
                    'Email' => 'jane@example.com',
                    'FirstName' => 'Jane',
                    'MarketingEmail' => 1,
                ]],
                'TotalRecords' => 1,
            ]),
        );
        $client->setToken(new Token('abc'));

        $result = $client->marketing()->updatedUsers('2026-01-01', '2026-01-31', 1, 100);

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/Marketing/GetUpdatedUsers', $http->lastRequest?->getUri()->getPath());
        self::assertSame([
            'startDate' => '2026-01-01',
            'endDate' => '2026-01-31',
            'pageNumber' => '1',
            'rowsPerPage' => '100',
        ], self::query($http->lastRequest));
        self::assertSame(1, $result->totalRecords);
        self::assertSame('Jane', $result->updatedUsers->first()->firstName);
        self::assertSame(1, $result->updatedUsers->first()->marketingEmail);
    }

    public function testUnsubscribeEmailSendsTheEmailAsQueryAndReturnsTheBoolean(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json(true),
        );
        $client->setToken(new Token('abc'));

        $unsubscribed = $client->marketing()->unsubscribeEmail('jane@example.com');

        self::assertSame('POST', $http->lastRequest?->getMethod());
        self::assertSame('/api/Marketing/MarketingEmailUnsubscribe', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['email' => 'jane@example.com'], self::query($http->lastRequest));
        self::assertTrue($unsubscribed);
    }

    public function testUpdatedUsersMaps204ToNull(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::raw('', 204),
        );
        $client->setToken(new Token('abc'));

        $result = $client->marketing()->updatedUsers('2026-01-01', '2026-01-31', 1, 100);

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/Marketing/GetUpdatedUsers', $http->lastRequest?->getUri()->getPath());
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertNull($result);
    }
}
