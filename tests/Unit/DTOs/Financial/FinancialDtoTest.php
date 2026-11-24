<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\Financial;

use Daika7ana\Cargus\DTOs\Financial\AwbCash;
use Daika7ana\Cargus\DTOs\Financial\CashReceipt;
use Daika7ana\Cargus\DTOs\Financial\CashReceiptList;
use Daika7ana\Cargus\DTOs\Financial\Invoice;
use PHPUnit\Framework\TestCase;

final class FinancialDtoTest extends TestCase
{
    public function testInvoiceReadsTheSchema(): void
    {
        $invoice = Invoice::fromArray([
            'InvoiceId' => 1,
            'Series' => 'CRG',
            'Number' => 42,
            'Value' => 100.0,
            'Total' => 120.0,
            'Closed' => true,
            'Balance' => 20.0,
            'CurrencyId' => 1,
        ]);

        self::assertSame(1, $invoice->id);
        self::assertSame('CRG', $invoice->series);
        self::assertSame(42, $invoice->number);
        self::assertSame(120.0, $invoice->total);
        self::assertTrue($invoice->closed);
        self::assertSame(20.0, $invoice->balance);
    }

    public function testAwbCashReadsTheSchema(): void
    {
        $awbCash = AwbCash::fromArray([
            'BarCode' => 'AWB1',
            'RepaymentValue' => 9.5,
            'DeductionId' => 'D1',
            'TariffSetName' => 'Standard',
        ]);

        self::assertSame('AWB1', $awbCash->barCode);
        self::assertSame(9.5, $awbCash->repaymentValue);
        self::assertSame('D1', $awbCash->deductionId);
        self::assertSame('Standard', $awbCash->tariffSetName);
    }

    public function testCashReceiptListDropsNullOptionals(): void
    {
        $list = new CashReceiptList(
            date: '2026-01-31T10:00:00',
            operator: 'op',
            location: 1,
            cashReceipts: [new CashReceipt('2026-01-31T10:00:00', 'R1', 'op', 10.0, 'AWB1', 5)],
        );

        self::assertSame([
            'Date' => '2026-01-31T10:00:00',
            'Operator' => 'op',
            'Location' => 1,
            'CashReceipts' => [[
                'ReceiptDate' => '2026-01-31T10:00:00',
                'ReceiptNumber' => 'R1',
                'ReceiptOperator' => 'op',
                'Amount' => 10.0,
                'Awb' => 'AWB1',
                'IdAwb' => 5,
            ]],
        ], $list->toArray());
    }
}
