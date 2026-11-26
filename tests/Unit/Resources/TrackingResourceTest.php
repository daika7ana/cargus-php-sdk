<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\Resources;

use Daika7ana\Cargus\Auth\Token;
use Daika7ana\Cargus\DTOs\Financial\AwbCash;
use Daika7ana\Cargus\DTOs\Tracking\AwbTraceDelta;
use Daika7ana\Cargus\DTOs\Tracking\PackageEntity;
use Daika7ana\Cargus\DTOs\Tracking\ShortPackage;
use Daika7ana\Cargus\DTOs\Tracking\TraceEvent;
use Daika7ana\Cargus\Exceptions\UnexpectedStatusException;
use Daika7ana\Cargus\Tests\Support\Fixture;
use Daika7ana\Cargus\Tests\Support\TestClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class TrackingResourceTest extends TestCase
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
    private static function packageRow(): array
    {
        return [
            'Code' => 'ABC123',
            'Type' => 'Parcel',
            'MeasuredWeight' => 2.5,
            'VolumetricWeight' => 3.0,
            'ConfirmationName' => 'Pop Ion',
            'Observation' => 'ok',
            'ResponseCode' => '200',
            'OrderStatus' => 1,
            'OrderLate' => true,
            'Event' => [
                [
                    'Date' => '2026-11-20T10:00:00',
                    'EventId' => 7,
                    'Description' => 'Delivered',
                    'LocalityName' => 'Bucuresti',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function cashRow(): array
    {
        return [
            'Date' => '2026-11-20T10:00:00',
            'BarCode' => 'ABC123',
            'Sender' => 'Sender SRL',
            'Receiver' => 'Receiver SRL',
            'FromLocality' => 'Bucuresti',
            'ToLocality' => 'Cluj',
            'CustomString' => '',
            'Picture' => '',
            'RepaymentDate' => '2026-11-20T10:00:00',
            'RepaymentId' => 'R1',
            'RepaymentValue' => 100.5,
            'DeductionDate' => '2026-11-20T10:00:00',
            'DeductionId' => 'D1',
            'StatusExpression' => 'Livrat',
            'ConfirmationDate' => '2026-11-21T10:00:00',
            'ConfirmationPersonName' => 'Pop Ion',
            'BarCodeTur' => 'TUR1',
            'TariffSetName' => 'Standard',
        ];
    }

    public function testShortTraceRequestsPathQueryAndMapsEvents(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([
                [
                    'AwbBarcode' => 'ABC123',
                    'AwbStatus' => 'Delivered',
                    'UndeliveredPieces' => '0',
                    'Envelope' => '1',
                    'Parcel' => '0',
                    'ConfirmedDate' => '2026-11-20 10:00:00',
                    'ConfirmedBy' => 'Pop Ion',
                    'ResponseCode' => '200',
                    'TransitTime' => '1 day',
                    'Event' => [
                        [
                            'Date' => '2026-11-20T10:00:00',
                            'EventId' => 7,
                            'Description' => 'Delivered',
                            'LocalityName' => 'Bucuresti',
                        ],
                    ],
                ],
            ]),
        );

        $packages = $client->tracking()->shortTraceNoAuth('ABC123');

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/NoAuth/GetShortAwbTrace', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['barCode' => 'ABC123'], self::query($http->lastRequest));
        self::assertFalse($http->lastRequest?->hasHeader('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
        self::assertCount(1, $packages);
        self::assertInstanceOf(ShortPackage::class, $packages->first());
        self::assertSame('ABC123', $packages->first()->awbBarcode);
        self::assertSame('Delivered', $packages->first()->awbStatus);
        self::assertSame('1 day', $packages->first()->transitTime);
        self::assertCount(1, $packages->first()->events);
        $event = $packages->first()->events->first();
        self::assertInstanceOf(TraceEvent::class, $event);
        self::assertSame('Delivered', $event->description);
        self::assertSame(7, $event->eventId);
        self::assertSame('Bucuresti', $event->localityName);
    }

    public function testNoContentReturnsAnEmptyCollection(): void
    {
        [$client] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::raw('', 204),
        );

        self::assertCount(0, $client->tracking()->shortTraceNoAuth('ABC123'));
    }

    public function testUnexpectedStatusIsMapped(): void
    {
        [$client] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::raw('upstream failed', 503),
        );

        $this->expectException(UnexpectedStatusException::class);

        $client->tracking()->shortTraceNoAuth('ABC123');
    }

    public function testTraceRequestsPathQueryHeadersAndMapsPackages(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([self::packageRow()]),
        );
        $client->setToken(new Token('abc'));

        $packages = $client->tracking()->trace('ABC123');

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/AwbTrace', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['barCode' => 'ABC123'], self::query($http->lastRequest));
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
        self::assertCount(1, $packages);
        $package = $packages->first();
        self::assertInstanceOf(PackageEntity::class, $package);
        self::assertSame('ABC123', $package->code);
        self::assertSame(2.5, $package->measuredWeight);
        self::assertSame(3.0, $package->volumetricWeight);
        self::assertSame(1, $package->orderStatus);
        self::assertTrue($package->orderLate);
        self::assertCount(1, $package->events);
        self::assertSame('Delivered', $package->events->first()->description);
    }

    public function testTraceOneRequestUsesItsOwnPath(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([self::packageRow()]),
        );
        $client->setToken(new Token('abc'));

        self::assertCount(1, $client->tracking()->traceOneRequest('ABC123'));

        self::assertSame('/api/AwbTrace/GetAwbTraceOneRequest', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['barCode' => 'ABC123'], self::query($http->lastRequest));
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
    }

    public function testTraceWithRedirectUsesItsOwnPath(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([self::packageRow()]),
        );
        $client->setToken(new Token('abc'));

        self::assertCount(1, $client->tracking()->traceWithRedirect('ABC123'));

        self::assertSame('/api/AwbTrace/WithRedirect', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['barCode' => 'ABC123'], self::query($http->lastRequest));
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
    }

    public function testConfirmDeliveryPostsQueryInput(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([self::packageRow()]),
        );
        $client->setToken(new Token('abc'));

        $packages = $client->tracking()->confirmDelivery('ABC123', '2026-11-20', 'left at door');

        self::assertSame('POST', $http->lastRequest?->getMethod());
        self::assertSame('/api/AwbTrace/PostConfirmationDelivery', $http->lastRequest?->getUri()->getPath());
        self::assertSame([
            'barCode' => 'ABC123',
            'date' => '2026-11-20',
            'remarkComment' => 'left at door',
        ], self::query($http->lastRequest));
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
        self::assertCount(1, $packages);
        self::assertSame('ABC123', $packages->first()->code);
    }

    public function testDeltaEventsMapsNestedPackagesAndEvents(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([
                [
                    'Date' => '2026-11-20T10:00:00',
                    'BarCode' => 'ABC123',
                    'Sender' => 'Sender SRL',
                    'Receiver' => 'Receiver SRL',
                    'FromCounty' => 'Bucuresti',
                    'FromLocality' => 'Bucuresti',
                    'ToCounty' => 'Cluj',
                    'ToLocality' => 'Cluj',
                    'CustomString' => '',
                    'RepaymentDate' => '2026-11-20T10:00:00',
                    'RepaymentId' => 'R1',
                    'RepaymentValue' => 100.5,
                    'DeductionDate' => '2026-11-20T10:00:00',
                    'DeductionId' => 'D1',
                    'StatusExpression' => 'Livrat',
                    'ConfirmationDate' => '2026-11-21T10:00:00',
                    'ConfirmationPersonName' => 'Pop Ion',
                    'InitialAwb' => 'ABC000',
                    'ResponseCode' => '200',
                    'Packages' => [
                        [
                            'Code' => 'ABC123',
                            'CodeNT' => 'NT1',
                            'Type' => 'Parcel',
                            'MeasuredWeight' => 2.5,
                            'VolumetricWeight' => 3.0,
                            'OrderStatus' => 1,
                            'OrderLate' => true,
                            'Events' => [
                                [
                                    'Date' => '2026-11-20T10:00:00',
                                    'EventId' => 7,
                                    'Description' => 'Delivered',
                                    'LocalityName' => 'Cluj',
                                    'CountyName' => 'Cluj',
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        );
        $client->setToken(new Token('abc'));

        $traces = $client->tracking()->deltaEvents('2026-11-01', '2026-11-24');

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/AwbTrace/GetDeltaEvents', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['FromDate' => '2026-11-01', 'ToDate' => '2026-11-24'], self::query($http->lastRequest));
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
        self::assertCount(1, $traces);
        $trace = $traces->first();
        self::assertInstanceOf(AwbTraceDelta::class, $trace);
        self::assertSame('ABC123', $trace->barCode);
        self::assertSame(100.5, $trace->repaymentValue);
        self::assertCount(1, $trace->packages);
        self::assertSame('NT1', $trace->packages->first()->codeNT);
        self::assertSame('Cluj', $trace->packages->first()->events->first()->countyName);
    }

    public function testTraceNoAuthSendsOnlyTheSubscriptionKey(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([self::packageRow()]),
        );

        $packages = $client->tracking()->traceNoAuth('ABC123');

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/NoAuth/GetAwbTrace', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['barCode' => 'ABC123'], self::query($http->lastRequest));
        self::assertFalse($http->lastRequest?->hasHeader('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
        self::assertSame('ABC123', $packages->first()->code);
    }

    public function testScanReturnsTheStringBody(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json('JVBERi0xLjQ='),
        );
        $client->setToken(new Token('abc'));

        self::assertSame('%PDF-1.4', $client->tracking()->scan('ABC123'));

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/AwbScan', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['barCode' => 'ABC123'], self::query($http->lastRequest));
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
    }

    public function testScanNoContentThrowsUnexpectedStatus(): void
    {
        [$client] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::raw('', 204),
        );
        $client->setToken(new Token('abc'));

        $this->expectException(UnexpectedStatusException::class);
        $this->expectExceptionMessage('Cargus returned no scan: the requested AWB does not exist or has not been scanned yet.');

        $client->tracking()->scan('ABC123');
    }

    public function testSyncStatusMapsAwbCashList(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([self::cashRow()]),
        );
        $client->setToken(new Token('abc'));

        $rows = $client->tracking()->syncStatus('2026-11-01', '2026-11-24');

        self::assertSame('/api/AwbStatus/GetAwbSyncStatus', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['FromDate' => '2026-11-01', 'ToDate' => '2026-11-24'], self::query($http->lastRequest));
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
        self::assertCount(1, $rows);
        self::assertInstanceOf(AwbCash::class, $rows->first());
        self::assertSame('Livrat', $rows->first()->statusExpression);
        self::assertSame(100.5, $rows->first()->repaymentValue);
    }

    public function testSyncStatusByTariffSendsTheTariffId(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([self::cashRow()]),
        );
        $client->setToken(new Token('abc'));

        self::assertCount(1, $client->tracking()->syncStatusByTariff(5, '2026-11-01', '2026-11-24'));

        self::assertSame('/api/AwbStatus/GetAwbSyncStatusByTariff', $http->lastRequest?->getUri()->getPath());
        self::assertSame([
            'IdTariff' => '5',
            'FromDate' => '2026-11-01',
            'ToDate' => '2026-11-24',
        ], self::query($http->lastRequest));
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
    }

    public function testSyncStatusByBarCodeMapsASingleAwb(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json(self::cashRow()),
        );
        $client->setToken(new Token('abc'));

        $cash = $client->tracking()->syncStatusByBarCode('ABC123');

        self::assertSame('/api/AwbStatus/GetAwbSyncStatusByBarCode', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['barCode' => 'ABC123'], self::query($http->lastRequest));
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
        self::assertInstanceOf(AwbCash::class, $cash);
        self::assertSame('ABC123', $cash->barCode);
    }

    public function testSyncStatusByBarCodeReturnsNullOnNoContent(): void
    {
        [$client] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::raw('', 204),
        );
        $client->setToken(new Token('abc'));

        self::assertNull($client->tracking()->syncStatusByBarCode('ABC123'));
    }

    public function testDeltaEventsMaps204ToAnEmptyCollection(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::raw('', 204),
        );
        $client->setToken(new Token('abc'));

        $traces = $client->tracking()->deltaEvents('2026-11-01', '2026-11-24');

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/AwbTrace/GetDeltaEvents', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['FromDate' => '2026-11-01', 'ToDate' => '2026-11-24'], self::query($http->lastRequest));
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertCount(0, $traces);
    }
}
