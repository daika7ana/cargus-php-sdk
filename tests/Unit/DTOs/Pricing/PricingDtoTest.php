<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\Pricing;

use Daika7ana\Cargus\DTOs\Pricing\PriceTable;
use Daika7ana\Cargus\DTOs\Pricing\ShippingCalculationModel;
use Daika7ana\Cargus\DTOs\Pricing\ShippingCalculationParcelCode;
use PHPUnit\Framework\TestCase;

final class PricingDtoTest extends TestCase
{
    public function testShippingCalculationModelSerializesPascalCaseAndDropsNulls(): void
    {
        $model = new ShippingCalculationModel(
            parcels: 1,
            envelopes: 0,
            totalWeight: 2,
            shipmentPayer: 1,
            declaredValue: 10.0,
            cashRepayment: 0.0,
            bankRepayment: 0.0,
            openPackage: false,
            saturdayDelivery: false,
            morningDelivery: false,
            parcelCodes: [new ShippingCalculationParcelCode(type: 1, weight: 1.5)],
        );

        self::assertSame([
            'Parcels' => 1,
            'Envelopes' => 0,
            'TotalWeight' => 2,
            'ShipmentPayer' => 1,
            'DeclaredValue' => 10.0,
            'CashRepayment' => 0.0,
            'BankRepayment' => 0.0,
            'OpenPackage' => false,
            'SaturdayDelivery' => false,
            'MorningDelivery' => false,
            'ParcelCodes' => [['Type' => 1, 'Weight' => 1.5]],
        ], $model->toArray());
    }

    public function testPriceTableReadsTheTypoedTariffVersion(): void
    {
        $table = PriceTable::fromArray(['PriceTableId' => 1, 'Name' => 'Standard', 'TariffVerion' => 3]);

        self::assertSame(1, $table->id);
        self::assertSame('Standard', $table->name);
        self::assertSame(3, $table->tariffVersion);
    }
}
