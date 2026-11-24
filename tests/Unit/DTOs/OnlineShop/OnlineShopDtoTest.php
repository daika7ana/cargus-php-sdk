<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\OnlineShop;

use Daika7ana\Cargus\DTOs\OnlineShop\CustomerDetails;
use Daika7ana\Cargus\DTOs\OnlineShop\UserWorkstation;
use Daika7ana\Cargus\DTOs\OnlineShop\WorkstationDetails;
use PHPUnit\Framework\TestCase;

final class OnlineShopDtoTest extends TestCase
{
    public function testCustomerDetailsReadsTheSchema(): void
    {
        $details = CustomerDetails::fromArray([
            'ClientID' => 12,
            'ClientName' => 'ACME',
            'CUI' => 'RO123',
            'PaymentTerm' => 30,
            'JuridicPerson' => true,
            'ApplyDiscount' => true,
            'ZipCode' => '400001',
        ]);

        self::assertSame(12, $details->clientId);
        self::assertSame('ACME', $details->clientName);
        self::assertSame('RO123', $details->cui);
        self::assertSame(30, $details->paymentTerm);
        self::assertTrue($details->juridicPerson);
        self::assertTrue($details->applyDiscount);
        self::assertSame('400001', $details->zipCode);
    }

    public function testUserWorkstationAndDetailsReadIds(): void
    {
        self::assertSame(3, UserWorkstation::fromArray(['Id' => 3, 'Name' => 'Depot'])->id);

        $details = WorkstationDetails::fromArray(['Id' => 3, 'ContactEmail' => 'a@b.ro', 'County' => 'Cluj']);

        self::assertSame(3, $details->id);
        self::assertSame('a@b.ro', $details->contactEmail);
        self::assertSame('Cluj', $details->county);
    }
}
