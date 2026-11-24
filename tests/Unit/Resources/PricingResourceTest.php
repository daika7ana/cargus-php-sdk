<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\Resources;

use Daika7ana\Cargus\Auth\Token;
use Daika7ana\Cargus\DTOs\Pricing\ShippingCalculationModel;
use Daika7ana\Cargus\Tests\Support\Fixture;
use Daika7ana\Cargus\Tests\Support\TestClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class PricingResourceTest extends TestCase
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

    private static function model(): ShippingCalculationModel
    {
        return new ShippingCalculationModel(
            parcels: 2,
            envelopes: 0,
            totalWeight: 5,
            shipmentPayer: 1,
            declaredValue: 100.5,
            cashRepayment: 0.0,
            bankRepayment: 0.0,
            openPackage: false,
            saturdayDelivery: true,
            morningDelivery: false,
            fromLocalityId: 7,
            toLocalityId: 9,
        );
    }

    public function testCalculatePostsThePascalCaseBodyAndMapsTheCost(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([
                'BaseCost' => 10.0,
                'ExtraKmCost' => 1.5,
                'Subtotal' => 11.5,
                'Tax' => 2.185,
                'GrandTotal' => 13.685,
            ]),
        );
        $client->setToken(new Token('abc'));

        $cost = $client->pricing()->calculate(self::model());

        self::assertSame('POST', $http->lastRequest?->getMethod());
        self::assertSame('/api/ShippingCalculation', $http->lastRequest?->getUri()->getPath());
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
        self::assertSame([
            'FromLocalityId' => 7,
            'ToLocalityId' => 9,
            'Parcels' => 2,
            'Envelopes' => 0,
            'TotalWeight' => 5,
            'ShipmentPayer' => 1,
            'DeclaredValue' => 100.5,
            'CashRepayment' => 0,
            'BankRepayment' => 0,
            'OpenPackage' => false,
            'SaturdayDelivery' => true,
            'MorningDelivery' => false,
        ], json_decode((string) $http->lastRequest?->getBody(), true));
        self::assertSame(10.0, $cost->baseCost);
        self::assertSame(13.685, $cost->grandTotal);
    }

    public function testCalculateNoAuthSendsNoBearerToken(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json(['GrandTotal' => 4.0]),
        );

        $cost = $client->pricing()->calculateNoAuth(self::model());

        self::assertSame('/api/NoAuth/PostShippingCalculation', $http->lastRequest?->getUri()->getPath());
        self::assertFalse($http->lastRequest?->hasHeader('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
        self::assertSame(4.0, $cost->grandTotal);
    }

    public function testPriceTablesMapsTheTypoedTariffField(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([
                ['PriceTableId' => 1, 'Name' => 'Standard', 'TariffVerion' => 3],
            ]),
        );
        $client->setToken(new Token('abc'));

        $tables = $client->pricing()->priceTables();

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/PriceTables', $http->lastRequest?->getUri()->getPath());
        self::assertSame([], self::query($http->lastRequest));
        self::assertSame('Standard', $tables->first()->name);
        self::assertSame(3, $tables->first()->tariffVersion);
    }
}
