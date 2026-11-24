<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\Resources;

use Daika7ana\Cargus\Auth\Token;
use Daika7ana\Cargus\DTOs\PickupLocation\AddressExtended;
use Daika7ana\Cargus\DTOs\PickupLocation\AddressExtendedClickCollect;
use Daika7ana\Cargus\Tests\Support\Fixture;
use Daika7ana\Cargus\Tests\Support\TestClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class PickupLocationResourceTest extends TestCase
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

    /**
     * @return array<string, mixed>
     */
    private static function body(RequestInterface $request): array
    {
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);

        return is_array($body) ? $body : [];
    }

    private static function locationRow(): array
    {
        return [
            'AutomaticEOD' => '1',
            'LocationId' => 4,
            'Name' => 'Depot',
            'CountyId' => 11,
            'CountyName' => 'Bucuresti',
            'LocalityId' => 2,
            'LocalityName' => 'Sector 1',
            'StreetId' => 3,
            'StreetName' => 'Str. A',
            'BuildingNumber' => '10',
            'AddressText' => 'Str. A 10',
            'ContactPerson' => 'Ion',
            'PhoneNumber' => '0700000000',
            'Email' => 'depot@example.com',
            'CodPostal' => '010101',
            'PostalCode' => '010101',
            'CountryId' => 1,
        ];
    }

    public function testListRequestsOptionalIdQueryAndMapsLocations(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([self::locationRow()]),
        );
        $client->setToken(new Token('abc'));

        $locations = $client->pickupLocations()->list(4);

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/PickupLocations', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['idPickupLocation' => '4'], self::query($http->lastRequest));
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
        $first = $locations->first();
        self::assertInstanceOf(AddressExtended::class, $first);
        self::assertSame(4, $first->locationId);
        self::assertSame('Depot', $first->name);
        self::assertSame('1', $first->automaticEOD);
        self::assertSame('010101', $first->postalCode);
    }

    public function testListForClientGetsLocationsWithoutQuery(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([self::locationRow()]),
        );
        $client->setToken(new Token('abc'));

        $locations = $client->pickupLocations()->listForClient();

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/PickupLocations/GetForClient', $http->lastRequest?->getUri()->getPath());
        self::assertSame('', $http->lastRequest?->getUri()->getQuery());
        self::assertCount(1, $locations);
        self::assertSame('Depot', $locations->first()->name);
    }

    public function testListClickCollectMapsClickCollectLocations(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([[
                'MinihubId' => 'MH1',
                'Active' => 1,
                'Name' => 'Locker',
            ]]),
        );
        $client->setToken(new Token('abc'));

        $locations = $client->pickupLocations()->listClickCollect();

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/PickupLocations/GetClickCollect', $http->lastRequest?->getUri()->getPath());
        $first = $locations->first();
        self::assertInstanceOf(AddressExtendedClickCollect::class, $first);
        self::assertSame('MH1', $first->minihubId);
        self::assertSame(1, $first->active);
        self::assertSame('Locker', $first->name);
    }

    public function testSavePostsPascalCaseBodyAndReturnsId(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json(41),
        );
        $client->setToken(new Token('abc'));

        $id = $client->pickupLocations()->save(new AddressExtended(
            name: 'Depot',
            countyId: 11,
            localityName: 'Sector 1',
            addressText: 'Str. A 10',
        ));

        self::assertSame('POST', $http->lastRequest?->getMethod());
        self::assertSame('/api/PickupLocations', $http->lastRequest?->getUri()->getPath());
        self::assertSame([
            'Name' => 'Depot',
            'CountyId' => 11,
            'LocalityName' => 'Sector 1',
            'AddressText' => 'Str. A 10',
        ], self::body($http->lastRequest));
        self::assertSame(41, $id);
    }

    public function testUpdatePutsPascalCaseBodyAndReturnsCount(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json(1),
        );
        $client->setToken(new Token('abc'));

        $count = $client->pickupLocations()->update(new AddressExtended(locationId: 4, name: 'Depot'));

        self::assertSame('PUT', $http->lastRequest?->getMethod());
        self::assertSame('/api/PickupLocations', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['LocationId' => 4, 'Name' => 'Depot'], self::body($http->lastRequest));
        self::assertSame(1, $count);
    }

    public function testDeleteSendsIdQueryAndReturnsCount(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json(1),
        );
        $client->setToken(new Token('abc'));

        $count = $client->pickupLocations()->delete(4);

        self::assertSame('DELETE', $http->lastRequest?->getMethod());
        self::assertSame('/api/PickupLocations', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['idPickupLocation' => '4'], self::query($http->lastRequest));
        self::assertSame(1, $count);
    }

    public function testSaveClickCollectPostsBodyAndReturnsId(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json(42),
        );
        $client->setToken(new Token('abc'));

        $id = $client->pickupLocations()->saveClickCollect(new AddressExtendedClickCollect(
            minihubId: 'MH1',
            active: 1,
            name: 'Locker',
        ));

        self::assertSame('POST', $http->lastRequest?->getMethod());
        self::assertSame('/api/PickupLocations/PostClickCollect', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['MinihubId' => 'MH1', 'Active' => 1, 'Name' => 'Locker'], self::body($http->lastRequest));
        self::assertSame(42, $id);
    }

    public function testUpdateClickCollectPutsBodyAndReturnsCount(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json(1),
        );
        $client->setToken(new Token('abc'));

        $count = $client->pickupLocations()->updateClickCollect(new AddressExtendedClickCollect(
            locationId: 4,
            active: 0,
        ));

        self::assertSame('PUT', $http->lastRequest?->getMethod());
        self::assertSame('/api/PickupLocations/PutClickCollect', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['Active' => 0, 'LocationId' => 4], self::body($http->lastRequest));
        self::assertSame(1, $count);
    }

    public function testAssignToUserPostsLocationIdQuery(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json(1),
        );
        $client->setToken(new Token('abc'));

        $count = $client->pickupLocations()->assignToUser(7);

        self::assertSame('POST', $http->lastRequest?->getMethod());
        self::assertSame('/api/PickupLocations/AssignToUser', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['LocationId' => '7'], self::query($http->lastRequest));
        self::assertSame(1, $count);
    }
}
