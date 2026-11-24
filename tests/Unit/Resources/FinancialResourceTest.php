<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\Resources;

use Daika7ana\Cargus\Auth\Token;
use Daika7ana\Cargus\DTOs\Financial\CashReceipt;
use Daika7ana\Cargus\DTOs\Financial\CashReceiptList;
use Daika7ana\Cargus\Tests\Support\Fixture;
use Daika7ana\Cargus\Tests\Support\TestClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class FinancialResourceTest extends TestCase
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

    public function testInvoicesSendsDatesAndMapsTheList(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([
                ['InvoiceId' => 1, 'Series' => 'CRG', 'Number' => 42, 'Total' => 120.0, 'Closed' => false],
            ]),
        );
        $client->setToken(new Token('abc'));

        $invoices = $client->financial()->invoices('2026-01-01', '2026-01-31', 50, 1);

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/Invoices', $http->lastRequest?->getUri()->getPath());
        self::assertSame([
            'FromDate' => '2026-01-01',
            'ToDate' => '2026-01-31',
            'itemsPerPage' => '50',
            'pageNumber' => '1',
        ], self::query($http->lastRequest));
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame('CRG', $invoices->first()->series);
        self::assertSame(120.0, $invoices->first()->total);
    }

    public function testCashAccountSendsTheOptionalBarCode(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([['BarCode' => '123', 'RepaymentValue' => 9.5]]),
        );
        $client->setToken(new Token('abc'));

        $rows = $client->financial()->cashAccount('123');

        self::assertSame('/api/CashAccount', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['barCode' => '123'], self::query($http->lastRequest));
        self::assertSame(9.5, $rows->first()->repaymentValue);
    }

    public function testCashAccountByDateSendsTheRange(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([['BarCode' => 'A1']]),
        );
        $client->setToken(new Token('abc'));

        $rows = $client->financial()->cashAccountByDate('2026-01-01', '2026-01-31', 2, 25);

        self::assertSame('/api/CashAccount/GetByDate', $http->lastRequest?->getUri()->getPath());
        self::assertSame([
            'FromDate' => '2026-01-01',
            'ToDate' => '2026-01-31',
            'pageNumber' => '2',
            'itemsPerPage' => '25',
        ], self::query($http->lastRequest));
        self::assertSame('A1', $rows->first()->barCode);
    }

    public function testCashAccountByDeductionDateSendsTheDate(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([['DeductionId' => 'D1']]),
        );
        $client->setToken(new Token('abc'));

        $rows = $client->financial()->cashAccountByDeductionDate('2026-01-15');

        self::assertSame('/api/CashAccount/GetByDeductionDate', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['DeductionDate' => '2026-01-15'], self::query($http->lastRequest));
        self::assertSame('D1', $rows->first()->deductionId);
    }

    public function testCashReceiptsPostsTheListAndReturnsTheId(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json(77),
        );
        $client->setToken(new Token('abc'));

        $id = $client->financial()->cashReceipts(new CashReceiptList(
            date: '2026-01-31T10:00:00',
            operator: 'op',
            location: 1,
            cashReceipts: [new CashReceipt('2026-01-31T10:00:00', 'R1', 'op', 10.0, 'AWB1', 5)],
        ));

        self::assertSame('POST', $http->lastRequest?->getMethod());
        self::assertSame('/api/CashReceipts', $http->lastRequest?->getUri()->getPath());
        self::assertSame([
            'Date' => '2026-01-31T10:00:00',
            'Operator' => 'op',
            'Location' => 1,
            'CashReceipts' => [[
                'ReceiptDate' => '2026-01-31T10:00:00',
                'ReceiptNumber' => 'R1',
                'ReceiptOperator' => 'op',
                'Amount' => 10,
                'Awb' => 'AWB1',
                'IdAwb' => 5,
            ]],
        ], json_decode((string) $http->lastRequest?->getBody(), true));
        self::assertSame(77, $id);
    }
}
