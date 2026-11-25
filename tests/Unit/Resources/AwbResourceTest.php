<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\Resources;

use Daika7ana\Cargus\Auth\Token;
use Daika7ana\Cargus\DTOs\Awb\Address;
use Daika7ana\Cargus\DTOs\Awb\AwbExtended;
use Daika7ana\Cargus\DTOs\Awb\AwbModel;
use Daika7ana\Cargus\DTOs\Awb\AwbParcelUpdateModel;
use Daika7ana\Cargus\DTOs\Awb\AwbReturModel;
use Daika7ana\Cargus\DTOs\Awb\AwbTurModel;
use Daika7ana\Cargus\DTOs\Awb\AwbUpdate;
use Daika7ana\Cargus\DTOs\Awb\DeliveryRouteModel;
use Daika7ana\Cargus\DTOs\Awb\GeneralAwbModel;
use Daika7ana\Cargus\DTOs\Awb\IncomingAwb;
use Daika7ana\Cargus\DTOs\Awb\ParcelCode;
use Daika7ana\Cargus\DTOs\Awb\ReturnAwbModel;
use Daika7ana\Cargus\DTOs\Awb\RoutingDetail;
use Daika7ana\Cargus\DTOs\Awb\SenderAddress;
use Daika7ana\Cargus\Exceptions\UnexpectedStatusException;
use Daika7ana\Cargus\Tests\Support\Fixture;
use Daika7ana\Cargus\Tests\Support\TestClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class AwbResourceTest extends TestCase
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
        $body = json_decode((string) $request->getBody(), true);

        return is_array($body) ? $body : [];
    }

    private static function assertAuthenticated(RequestInterface $request): void
    {
        self::assertSame('Bearer abc', $request->getHeaderLine('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $request->getHeaderLine('Ocp-Apim-Subscription-Key'));
    }

    private static function awb(): AwbTurModel
    {
        return new AwbTurModel(
            sender: new Address(name: 'Sender', localityId: 1, countyId: 2),
            recipient: new Address(name: 'Receiver', localityId: 3, countyId: 4, streetId: 5),
            parcels: 1,
            envelopes: 0,
            totalWeight: 2,
            shipmentPayer: 1,
            serviceId: 7,
            cashRepayment: 10.5,
            parcelCodes: [new ParcelCode(code: 'P1', type: 0, weight: 2.0)],
        );
    }

    public function testGetRequestsFiltersAndMapsAwbExtended(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([
                [
                    'Sender' => ['Name' => 'Sender', 'LocalityId' => 1],
                    'Recipient' => ['Name' => 'Receiver'],
                    'Parcels' => 1,
                    'Envelopes' => 0,
                    'TotalWeight' => 2,
                    'ShipmentPayer' => 1,
                    'ReturnCode' => 'RC',
                    'BarCode' => 'AWB1',
                    'ParcelCodes' => [['Code' => 'P1', 'Type' => 0]],
                    'ShippingCost' => ['GrandTotal' => 12.5],
                ],
            ]),
        );
        $client->setToken(new Token('abc'));

        $awbs = $client->awbs()->get(orderId: 42, barCode: 'AWB1');

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/Awbs', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['orderId' => '42', 'barCode' => 'AWB1'], self::query($http->lastRequest));
        self::assertAuthenticated($http->lastRequest);
        self::assertCount(1, $awbs);
        self::assertInstanceOf(AwbExtended::class, $awbs->first());
        self::assertSame('RC', $awbs->first()->returnCode);
        self::assertSame('P1', $awbs->first()->parcelCodes->first()->code);
        self::assertSame(12.5, $awbs->first()->shippingCost?->grandTotal);
    }

    public function testGetReturnsEmptyCollectionOnNoContent(): void
    {
        [$client] = TestClientFactory::create(static fn(): ResponseInterface => Fixture::raw('', 204));
        $client->setToken(new Token('abc'));

        self::assertCount(0, $client->awbs()->get());
    }

    public function testGetByDateRequestsPaginationAndMapsAwbModel(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([
                ['Sender' => ['Name' => 'S'], 'Recipient' => ['Name' => 'R'], 'Parcels' => 2, 'BarCode' => 'AWB2'],
            ]),
        );
        $client->setToken(new Token('abc'));

        $awbs = $client->awbs()->getByDate('2026-01-01T00:00:00', '2026-01-31T00:00:00', 2, 50);

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/Awbs/GetByDate', $http->lastRequest?->getUri()->getPath());
        self::assertSame([
            'FromDate' => '2026-01-01T00:00:00',
            'ToDate' => '2026-01-31T00:00:00',
            'pageNumber' => '2',
            'itemsPerPage' => '50',
        ], self::query($http->lastRequest));
        self::assertInstanceOf(AwbModel::class, $awbs->first());
        self::assertSame('AWB2', $awbs->first()->barCode);
        self::assertSame(2, $awbs->first()->parcels);
    }

    public function testGetIncomingByDateMapsIncomingAwb(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([
                [
                    'AwbId' => 9,
                    'AwbCode' => 'IN1',
                    'AwbAmount' => 4.5,
                    'ReturnAddress' => ['Name' => 'Return'],
                    'Packages' => [['PackageId' => 3, 'PackageBarCode' => 'PK3']],
                ],
            ]),
        );
        $client->setToken(new Token('abc'));

        $awbs = $client->awbs()->getIncomingByDate('2026-01-01', '2026-01-02', 11);

        self::assertSame('/api/Awbs/GetIncomingByDate', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['FromDate' => '2026-01-01', 'ToDate' => '2026-01-02', 'DestId' => '11'], self::query($http->lastRequest));
        self::assertInstanceOf(IncomingAwb::class, $awbs->first());
        self::assertSame(9, $awbs->first()->awbId);
        self::assertSame('Return', $awbs->first()->returnAddress?->name);
        self::assertSame('PK3', $awbs->first()->packages->first()->packageBarCode);
    }

    public function testGetIncomingByDateClickCollectUsesItsOwnPath(): void
    {
        [$client, $http] = TestClientFactory::create(static fn(): ResponseInterface => Fixture::json([]));
        $client->setToken(new Token('abc'));

        $client->awbs()->getIncomingByDateClickCollect('2026-01-01', '2026-01-02', 12);

        self::assertSame('/api/Awbs/GetIncomingByDateClickCollect', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['FromDate' => '2026-01-01', 'ToDate' => '2026-01-02', 'DestId' => '12'], self::query($http->lastRequest));
    }

    public function testCreatePostsAwbTurModelAndReturnsTheBarcode(): void
    {
        [$client, $http] = TestClientFactory::create(static fn(): ResponseInterface => Fixture::json('AWB123'));
        $client->setToken(new Token('abc'));

        $barcode = $client->awbs()->create(self::awb());

        self::assertSame('POST', $http->lastRequest?->getMethod());
        self::assertSame('/api/Awbs', $http->lastRequest?->getUri()->getPath());
        self::assertSame('Crra', $http->lastRequest?->getHeaderLine('Path'));
        self::assertAuthenticated($http->lastRequest);
        self::assertSame([
            'Sender' => ['Name' => 'Sender', 'CountyId' => 2, 'LocalityId' => 1],
            'Recipient' => ['Name' => 'Receiver', 'CountyId' => 4, 'LocalityId' => 3, 'StreetId' => 5],
            'Parcels' => 1,
            'Envelopes' => 0,
            'TotalWeight' => 2,
            'ShipmentPayer' => 1,
            'ServiceId' => 7,
            'CashRepayment' => 10.5,
            'ParcelCodes' => [['Code' => 'P1', 'Type' => 0, 'Weight' => 2]],
        ], self::body($http->lastRequest));
        self::assertSame('AWB123', $barcode);
    }

    public function testCreateReturnsEmptyStringOnNoContent(): void
    {
        [$client] = TestClientFactory::create(static fn(): ResponseInterface => Fixture::raw('', 204));
        $client->setToken(new Token('abc'));

        self::assertSame('', $client->awbs()->create(self::awb()));
    }

    public function testWithGetAwbPostsTheSameBodyAndMapsAwbExtended(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([
                ['Sender' => ['Name' => 'S'], 'Recipient' => ['Name' => 'R'], 'Parcels' => 1, 'BarCode' => 'AWB9'],
            ]),
        );
        $client->setToken(new Token('abc'));

        $awbs = $client->awbs()->withGetAwb(self::awb());

        self::assertSame('POST', $http->lastRequest?->getMethod());
        self::assertSame('/api/Awbs/WithGetAwb', $http->lastRequest?->getUri()->getPath());
        self::assertSame('Sender', self::body($http->lastRequest)['Sender']['Name']);
        self::assertInstanceOf(AwbExtended::class, $awbs->first());
        self::assertSame('AWB9', $awbs->first()->barCode);
    }

    public function testRescheduleSendsQueryParameters(): void
    {
        [$client, $http] = TestClientFactory::create(static fn(): ResponseInterface => Fixture::json(true));
        $client->setToken(new Token('abc'));

        $result = $client->awbs()->reschedule('AWB1', '2026-02-01T10:00:00');

        self::assertSame('PUT', $http->lastRequest?->getMethod());
        self::assertSame('/api/Awbs/PutReschedule', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['BarCode' => 'AWB1', 'data' => '2026-02-01T10:00:00'], self::query($http->lastRequest));
        self::assertTrue($result);
    }

    public function testUpdateParcelCodesPutsTheUpdateModel(): void
    {
        [$client, $http] = TestClientFactory::create(static fn(): ResponseInterface => Fixture::json('AWB1'));
        $client->setToken(new Token('abc'));

        $result = $client->awbs()->updateParcelCodes(new AwbParcelUpdateModel(
            barcode: 'AWB1',
            parcelCodes: [new ParcelCode(code: 'P1', type: 1)],
        ));

        self::assertSame('PUT', $http->lastRequest?->getMethod());
        self::assertSame('/api/Awbs/Update', $http->lastRequest?->getUri()->getPath());
        self::assertSame([
            'Barcode' => 'AWB1',
            'ParcelCodes' => [['Code' => 'P1', 'Type' => 1]],
        ], self::body($http->lastRequest));
        self::assertSame('AWB1', $result);
    }

    public function testUpdateReferencePostsAwbUpdate(): void
    {
        [$client, $http] = TestClientFactory::create(static fn(): ResponseInterface => Fixture::json(true));
        $client->setToken(new Token('abc'));

        $result = $client->awbs()->updateReference(new AwbUpdate(
            awbBarcode: 'AWB1',
            reference: 'REF',
            referenceDate: '2026-02-01T10:00:00',
        ));

        self::assertSame('POST', $http->lastRequest?->getMethod());
        self::assertSame('/api/Awbs/AwbRefUpdate', $http->lastRequest?->getUri()->getPath());
        self::assertSame([
            'AwbBarcode' => 'AWB1',
            'Reference' => 'REF',
            'ReferenceDate' => '2026-02-01T10:00:00',
        ], self::body($http->lastRequest));
        self::assertTrue($result);
    }

    public function testDeleteSendsBarcodeInTheQuery(): void
    {
        [$client, $http] = TestClientFactory::create(static fn(): ResponseInterface => Fixture::json(true));
        $client->setToken(new Token('abc'));

        $result = $client->awbs()->delete('AWB1');

        self::assertSame('DELETE', $http->lastRequest?->getMethod());
        self::assertSame('/api/Awbs', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['barCode' => 'AWB1'], self::query($http->lastRequest));
        self::assertTrue($result);
    }

    public function testRoutingAddressPostsRoutingDetailAndMapsTheRoute(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([
                'TransitPriority' => 'P',
                'TransitRoute' => 'R',
                'DeliveryRoute' => 'D',
                'OriginDeposit' => 'O',
            ]),
        );
        $client->setToken(new Token('abc'));

        $route = $client->awbs()->routingAddress(new RoutingDetail(
            totalWeight: 5,
            sender: new SenderAddress(countyName: 'Cluj', localityId: 1),
        ));

        self::assertSame('POST', $http->lastRequest?->getMethod());
        self::assertSame('/api/Awbs/GetRoutingAddress', $http->lastRequest?->getUri()->getPath());
        self::assertSame([
            'TotalWeight' => 5,
            'Sender' => ['CountyName' => 'Cluj', 'LocalityId' => 1],
        ], self::body($http->lastRequest));
        self::assertInstanceOf(DeliveryRouteModel::class, $route);
        self::assertSame('D', $route->deliveryRoute);
    }

    public function testRoutingAddressReturnsNullOnNoContent(): void
    {
        [$client] = TestClientFactory::create(static fn(): ResponseInterface => Fixture::raw('', 204));
        $client->setToken(new Token('abc'));

        self::assertNull($client->awbs()->routingAddress(new RoutingDetail()));
    }

    public function testReturnsMapsNestedAwbModels(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([
                [
                    'Awb' => ['Sender' => ['Name' => 'S'], 'Recipient' => ['Name' => 'R'], 'BarCode' => 'AWB1'],
                    'AwbReturn' => ['BarCode' => 'AWB2'],
                    'Courier' => 'C',
                    'TimestampDeparture' => '2026-03-01T10:00:00',
                    'ClientObservation' => 'obs',
                ],
            ]),
        );
        $client->setToken(new Token('abc'));

        $returns = $client->awbs()->returns('2026-03-01');

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/AwbRetur', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['data' => '2026-03-01'], self::query($http->lastRequest));
        self::assertInstanceOf(AwbReturModel::class, $returns->first());
        self::assertSame('AWB1', $returns->first()->awb?->barCode);
        self::assertSame('AWB2', $returns->first()->awbReturn?->barCode);
        self::assertSame('C', $returns->first()->courier);
    }

    public function testCreateInternationalPostsGeneralAwbModel(): void
    {
        [$client, $http] = TestClientFactory::create(static fn(): ResponseInterface => Fixture::json('INT1'));
        $client->setToken(new Token('abc'));

        $barcode = $client->awbs()->createInternational(new GeneralAwbModel(
            sender: new Address(name: 'S'),
            recipient: new Address(name: 'R'),
            parcels: 1,
            envelopes: 0,
            totalWeight: 3,
            shipmentPayer: 1,
            isReturn: false,
            serviceId: 8,
            parcelCodes: [new ParcelCode(code: 'G1', type: 2)],
        ));

        self::assertSame('POST', $http->lastRequest?->getMethod());
        self::assertSame('/api/AwbGeneral/InternationalAwb', $http->lastRequest?->getUri()->getPath());
        $body = self::body($http->lastRequest);
        self::assertSame('S', $body['Sender']['Name']);
        self::assertFalse($body['IsReturn']);
        self::assertSame([['Code' => 'G1', 'Type' => 2]], $body['ParcelCodes']);
        self::assertSame('INT1', $barcode);
    }

    public function testGenerateReturnNoAuthPostsWithoutBearerToken(): void
    {
        [$client, $http] = TestClientFactory::create(static fn(): ResponseInterface => Fixture::json('RET1'));

        $barcode = $client->awbs()->generateReturnNoAuth(new ReturnAwbModel(
            barcode: 'AWB1',
            clientId: 5,
            hasContract: true,
        ));

        self::assertSame('POST', $http->lastRequest?->getMethod());
        self::assertSame('/api/NoAuth/GenerateRwb', $http->lastRequest?->getUri()->getPath());
        self::assertFalse($http->lastRequest?->hasHeader('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
        self::assertSame(['Barcode' => 'AWB1', 'ClientId' => 5, 'HasContract' => true], self::body($http->lastRequest));
        self::assertSame('RET1', $barcode);
    }

    public function testWriteResultParsesTheStringTrue(): void
    {
        [$client, $http] = TestClientFactory::create(static fn(): ResponseInterface => Fixture::json('True'));
        $client->setToken(new Token('abc'));

        $result = $client->awbs()->delete('AWB1');

        self::assertSame('DELETE', $http->lastRequest?->getMethod());
        self::assertSame('/api/Awbs', $http->lastRequest?->getUri()->getPath());
        self::assertTrue($result);
    }

    public function testWriteResultParsesTheStringFalse(): void
    {
        [$client, $http] = TestClientFactory::create(static fn(): ResponseInterface => Fixture::json('False'));
        $client->setToken(new Token('abc'));

        $result = $client->awbs()->reschedule('AWB1', '2026-02-01T10:00:00');

        self::assertSame('PUT', $http->lastRequest?->getMethod());
        self::assertSame('/api/Awbs/PutReschedule', $http->lastRequest?->getUri()->getPath());
        self::assertFalse($result);
    }

    public function testWriteResultRejectsAnUnknownPayload(): void
    {
        [$client] = TestClientFactory::create(static fn(): ResponseInterface => Fixture::json('garbage'));
        $client->setToken(new Token('abc'));

        $this->expectException(UnexpectedStatusException::class);

        $client->awbs()->updateReference(new AwbUpdate(
            awbBarcode: 'AWB1',
            reference: 'REF',
            referenceDate: '2026-02-01T10:00:00',
        ));
    }
}
