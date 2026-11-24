<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\Resources;

use Daika7ana\Cargus\Auth\Token;
use Daika7ana\Cargus\DTOs\Order\Order;
use Daika7ana\Cargus\DTOs\Order\OrderPickupClickCollectModel;
use Daika7ana\Cargus\DTOs\Order\OrderPickupModel;
use Daika7ana\Cargus\Tests\Support\Fixture;
use Daika7ana\Cargus\Tests\Support\TestClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class OrderResourceTest extends TestCase
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

    private static function orderRow(int $orderId): array
    {
        return [
            'OrderId' => $orderId,
            'LocationId' => 3,
            'PickUpSiteName' => 'Site',
            'PickupStartDate' => '2026-11-24T10:00:00',
            'PickupEndDate' => '2026-11-24T12:00:00',
            'NoParcel' => 2,
            'NoEnvelop' => 1,
            'TotalWeight' => 5,
            'NoAwb' => 3,
            'Observations' => 'note',
            'PackageContent' => 'books',
            'CreationDate' => '2026-11-23T09:00:00',
            'ValidationDate' => '2026-11-23T10:00:00',
            'ProcessedDate' => '2026-11-23T11:00:00',
            'OrdStatus' => 2,
            'OrdStatusDetails' => 'Validated',
            'AdresaExp' => 'Str. Test 1',
            'Email' => 'a@example.com',
            'OrderType' => 1,
        ];
    }

    public function testGetRequestsLocationStatusAndPaginationAndMapsOrders(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([self::orderRow(10), self::orderRow(11)]),
        );
        $client->setToken(new Token('abc'));

        $orders = $client->orders()->get(3, 1, 2, 25);

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/Orders', $http->lastRequest?->getUri()->getPath());
        self::assertSame(
            ['locationId' => '3', 'status' => '1', 'pageNumber' => '2', 'itemsPerPage' => '25'],
            self::query($http->lastRequest),
        );
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
        self::assertCount(2, $orders);
        $first = $orders->first();
        self::assertInstanceOf(Order::class, $first);
        self::assertSame(10, $first->orderId);
        self::assertSame(3, $first->locationId);
        self::assertSame('Site', $first->pickUpSiteName);
        self::assertSame(5, $first->totalWeight);
        self::assertSame(2, $first->ordStatus);
        self::assertSame('Validated', $first->ordStatusDetails);
        self::assertSame('a@example.com', $first->email);
        self::assertSame(1, $first->orderType);
    }

    public function testGetByDateRequestsDateRangeAndMapsOrders(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([self::orderRow(20)]),
        );
        $client->setToken(new Token('abc'));

        $orders = $client->orders()->getByDate('2026-11-01T00:00:00', '2026-11-30T23:59:59');

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/Orders/GetByDate', $http->lastRequest?->getUri()->getPath());
        self::assertSame(
            ['FromDate' => '2026-11-01T00:00:00', 'ToDate' => '2026-11-30T23:59:59'],
            self::query($http->lastRequest),
        );
        self::assertCount(1, $orders);
        self::assertSame(20, $orders->first()->orderId);
    }

    public function testGetByOrderIdRequestsQueryAndMapsSingleOrder(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json(self::orderRow(30)),
        );
        $client->setToken(new Token('abc'));

        $order = $client->orders()->getByOrderId(30);

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/Orders/GetByOrderId', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['orderId' => '30'], self::query($http->lastRequest));
        self::assertInstanceOf(Order::class, $order);
        self::assertSame(30, $order->orderId);
    }

    public function testCreatePostsPascalCaseBodyAndReturnsOrderId(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json(31),
        );
        $client->setToken(new Token('abc'));

        $orderId = $client->orders()->create(new OrderPickupModel(
            parcels: 2,
            envelopes: 1,
            totalWeight: 5,
            pickupStartDate: '2026-11-24T10:00:00',
            pickupEndDate: '2026-11-24T12:00:00',
            locationId: 3,
            name: 'Sender',
            countyId: 11,
            localityName: 'Bucuresti',
        ));

        self::assertSame('POST', $http->lastRequest?->getMethod());
        self::assertSame('/api/Orders', $http->lastRequest?->getUri()->getPath());
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
        self::assertSame([
            'Parcels' => 2,
            'Envelopes' => 1,
            'TotalWeight' => 5,
            'PickupStartDate' => '2026-11-24T10:00:00',
            'PickupEndDate' => '2026-11-24T12:00:00',
            'LocationId' => 3,
            'Name' => 'Sender',
            'CountyId' => 11,
            'LocalityName' => 'Bucuresti',
        ], self::body($http->lastRequest));
        self::assertSame(31, $orderId);
    }

    public function testUpdatePutsQueryParametersAndReturnsCount(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json(5),
        );
        $client->setToken(new Token('abc'));

        $count = $client->orders()->update(3, '2026-11-24T10:00:00', '2026-11-24T12:00:00', 1);

        self::assertSame('PUT', $http->lastRequest?->getMethod());
        self::assertSame('/api/Orders', $http->lastRequest?->getUri()->getPath());
        self::assertSame([
            'locationId' => '3',
            'PickupStartDate' => '2026-11-24T10:00:00',
            'PickupEndDate' => '2026-11-24T12:00:00',
            'action' => '1',
        ], self::query($http->lastRequest));
        self::assertSame(5, $count);
    }

    public function testUpdateAllPutsDateRangeAndAction(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json('ok'),
        );
        $client->setToken(new Token('abc'));

        $result = $client->orders()->updateAll('2026-11-24T10:00:00', '2026-11-24T12:00:00', 0);

        self::assertSame('PUT', $http->lastRequest?->getMethod());
        self::assertSame('/api/Orders/PutAll', $http->lastRequest?->getUri()->getPath());
        self::assertSame([
            'PickupStartDate' => '2026-11-24T10:00:00',
            'PickupEndDate' => '2026-11-24T12:00:00',
            'action' => '0',
        ], self::query($http->lastRequest));
        self::assertSame('ok', $result);
    }

    public function testCreateClickCollectPostsBodyAndReturnsOrderId(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json(32),
        );
        $client->setToken(new Token('abc'));

        $orderId = $client->orders()->createClickCollect(new OrderPickupClickCollectModel(
            observations: 'ring twice',
            parcels: 4,
            locationId: 3,
        ));

        self::assertSame('POST', $http->lastRequest?->getMethod());
        self::assertSame('/api/Orders/PostClickCollect', $http->lastRequest?->getUri()->getPath());
        self::assertSame([
            'Observations' => 'ring twice',
            'Parcels' => 4,
            'LocationId' => 3,
        ], self::body($http->lastRequest));
        self::assertSame(32, $orderId);
    }

    public function testCancelPutsOrderIdQueryAndReturnsCount(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json(1),
        );
        $client->setToken(new Token('abc'));

        $count = $client->orders()->cancel(30);

        self::assertSame('PUT', $http->lastRequest?->getMethod());
        self::assertSame('/api/Orders/Cancel', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['orderId' => '30'], self::query($http->lastRequest));
        self::assertSame(1, $count);
    }
}
