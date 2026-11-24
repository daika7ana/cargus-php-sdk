<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\Resources;

use Daika7ana\Cargus\Auth\Token;
use Daika7ana\Cargus\Tests\Support\Fixture;
use Daika7ana\Cargus\Tests\Support\TestClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class OnlineShopResourceTest extends TestCase
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

    public function testCustomerDetailsMapsTheObject(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([
                'ClientID' => 12,
                'ClientName' => 'ACME',
                'CUI' => 'RO123',
                'JuridicPerson' => true,
            ]),
        );
        $client->setToken(new Token('abc'));

        $details = $client->onlineShop()->customerDetails();

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/OnlineShop/GetCustomerDetails', $http->lastRequest?->getUri()->getPath());
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame(12, $details->clientId);
        self::assertSame('ACME', $details->clientName);
        self::assertTrue($details->juridicPerson);
    }

    public function testUserWorkstationsMapsTheList(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([['Id' => 3, 'Name' => 'Depot']]),
        );
        $client->setToken(new Token('abc'));

        $workstations = $client->onlineShop()->userWorkstations();

        self::assertSame('/api/OnlineShop/GetUserWorkstations', $http->lastRequest?->getUri()->getPath());
        self::assertSame(3, $workstations->first()->id);
        self::assertSame('Depot', $workstations->first()->name);
    }

    public function testWorkstationByIdSendsTheId(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json(['Id' => 3, 'ContactEmail' => 'a@b.ro']),
        );
        $client->setToken(new Token('abc'));

        $workstation = $client->onlineShop()->workstation(3);

        self::assertSame('/api/OnlineShop/GetWorkstationById', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['id' => '3'], self::query($http->lastRequest));
        self::assertSame('a@b.ro', $workstation->contactEmail);
    }
}
