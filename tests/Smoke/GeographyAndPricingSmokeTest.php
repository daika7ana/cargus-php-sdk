<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Smoke;

use Daika7ana\Cargus\DTOs\Pricing\ShippingCalculationModel;
use Daika7ana\Cargus\Exceptions\UnexpectedStatusException;
use Daika7ana\SimpleCollections\Collection;

final class GeographyAndPricingSmokeTest extends SmokeTestCase
{
    public function testGeographyReads(): void
    {
        $client = $this->client();
        $countryId = $this->sampleCountryId();

        $countries = $client->geography()->countries();
        $counties = $client->geography()->counties($countryId);
        $locality = $this->sampleLocality();
        $branches = $client->geography()->branches();
        $pudoPoints = $client->geography()->pudoPoints();
        $streets = $client->geography()->streets($locality->id);

        self::assertInstanceOf(Collection::class, $countries);
        self::assertNotEmpty($countries);
        self::assertNotEmpty($counties);
        self::assertGreaterThan(0, $locality->id);
        self::assertNotSame('', $locality->name);
        self::assertInstanceOf(Collection::class, $branches);
        self::assertInstanceOf(Collection::class, $pudoPoints);
        self::assertInstanceOf(Collection::class, $streets);

        foreach ($streets as $street) {
            self::assertNotSame('', $street->name);
        }
    }

    public function testPriceTablesRead(): void
    {
        $tables = $this->client()->pricing()->priceTables();

        self::assertInstanceOf(Collection::class, $tables);

        foreach ($tables as $table) {
            self::assertNotSame('', $table->name);
        }
    }

    public function testNoAuthShippingCalculation(): void
    {
        $locality = $this->sampleLocality();

        try {
            $cost = $this->client()->pricing()->calculateNoAuth(new ShippingCalculationModel(
                parcels: 1,
                envelopes: 0,
                totalWeight: 1,
                shipmentPayer: 1,
                declaredValue: 0.0,
                cashRepayment: 0.0,
                bankRepayment: 0.0,
                openPackage: false,
                saturdayDelivery: false,
                morningDelivery: false,
                fromLocalityId: $locality->id,
                toLocalityId: $locality->id,
            ));
        } catch (UnexpectedStatusException $e) {
            if ($e->response?->getStatusCode() === 500) {
                $this->markTestSkipped('Live /api/NoAuth/PostShippingCalculation answers 500 for this account: ' . $e->getMessage());
            }

            throw $e;
        }

        self::assertGreaterThanOrEqual(0.0, $cost->grandTotal);
    }

    public function testAuthenticatedShippingCalculation(): void
    {
        $locality = $this->sampleLocality();

        $cost = $this->client()->pricing()->calculate(new ShippingCalculationModel(
            parcels: 1,
            envelopes: 0,
            totalWeight: 1,
            shipmentPayer: 1,
            declaredValue: 0.0,
            cashRepayment: 0.0,
            bankRepayment: 0.0,
            openPackage: false,
            saturdayDelivery: false,
            morningDelivery: false,
            fromLocalityId: $locality->id,
            toLocalityId: $locality->id,
        ));

        self::assertGreaterThanOrEqual(0.0, $cost->baseCost);
        self::assertGreaterThanOrEqual(0.0, $cost->weightCost);
        self::assertGreaterThanOrEqual(0.0, $cost->subtotal);
        self::assertGreaterThanOrEqual(0.0, $cost->tax);
        self::assertGreaterThanOrEqual(0.0, $cost->grandTotal);
    }
}
