<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Smoke;

use Daika7ana\Cargus\Exceptions\UnexpectedStatusException;
use Daika7ana\SimpleCollections\Collection;

final class FinancialAndAccountSmokeTest extends SmokeTestCase
{
    public function testInvoicesRead(): void
    {
        $from = gmdate('Y-m-d', strtotime('-30 days'));
        $to = gmdate('Y-m-d');

        $invoices = null;

        try {
            $invoices = $this->client()->financial()->invoices($from, $to, 5, 1);
        } catch (UnexpectedStatusException $e) {
            if ($e->response?->getStatusCode() === 429) {
                $this->markTestSkipped('Live /api/Invoices is rate-limited on the shared test account: ' . $e->getMessage());
            }

            throw $e;
        }

        self::assertInstanceOf(Collection::class, $invoices);
        self::assertLessThanOrEqual(5, $invoices->count());

        foreach ($invoices as $invoice) {
            self::assertGreaterThan(0, $invoice->id);
        }
    }

    public function testCashAccountRead(): void
    {
        $barCode = $this->env('CARGUS_SMOKE_BARCODE');

        if ($barCode === null) {
            $this->markTestSkipped('Set CARGUS_SMOKE_BARCODE to run the cash account smoke test; the live API answers 409 without a barCode.');
        }

        $cash = $this->client()->financial()->cashAccount($barCode);

        self::assertInstanceOf(Collection::class, $cash);
    }

    public function testAccountReads(): void
    {
        $client = $this->client();

        $pickupLocations = $client->pickupLocations()->list();
        $recipients = $client->recipients()->list();
        $customerDetails = $client->onlineShop()->customerDetails();
        $orders = $client->orders()->getByDate(
            gmdate('Y-m-d', strtotime('-1 day')),
            gmdate('Y-m-d'),
            1,
            5,
        );

        self::assertInstanceOf(Collection::class, $pickupLocations);
        self::assertInstanceOf(Collection::class, $recipients);
        self::assertGreaterThan(0, $customerDetails->clientId);
        self::assertInstanceOf(Collection::class, $orders);
        self::assertLessThanOrEqual(5, $orders->count());
    }
}
