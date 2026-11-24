<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\Geography;

use Daika7ana\Cargus\DTOs\Geography\Branch;
use Daika7ana\Cargus\DTOs\Geography\Country;
use Daika7ana\Cargus\DTOs\Geography\Locality;
use Daika7ana\Cargus\DTOs\Geography\PudoPoint;
use Daika7ana\Cargus\DTOs\Geography\Street;
use PHPUnit\Framework\TestCase;

final class GeographyDtoTest extends TestCase
{
    public function testCountryReadsPascalCaseAndCamelCase(): void
    {
        $country = Country::fromArray(['CountryId' => 1, 'CountryName' => 'Romania', 'Abbreviation' => 'RO']);

        self::assertSame(1, $country->id);
        self::assertSame('Romania', $country->name);
        self::assertSame('RO', $country->abbreviation);

        self::assertSame('Bulgaria', Country::fromArray(['countryId' => 2, 'countryName' => 'Bulgaria'])->name);
    }

    public function testLocalityReadsAllFields(): void
    {
        $locality = Locality::fromArray([
            'LocalityId' => 7,
            'Name' => 'Cluj-Napoca',
            'ParentId' => 12,
            'ParentName' => 'Cluj',
            'ExtraKm' => 3,
            'InNetwork' => true,
            'CountyId' => 12,
            'CountryId' => 1,
            'PostalCode' => '400001',
            'MaxHour' => '18',
            'SaturdayDelivery' => true,
        ]);

        self::assertSame(7, $locality->id);
        self::assertSame(12, $locality->parentId);
        self::assertSame(3, $locality->extraKm);
        self::assertTrue($locality->inNetwork);
        self::assertSame('400001', $locality->postalCode);
        self::assertTrue($locality->saturdayDelivery);
    }

    public function testStreetMapsPostalNumbers(): void
    {
        $street = Street::fromArray([
            'StreetId' => 4,
            'Name' => 'Mihai Viteazu',
            'PostalNumbers' => [['FromNo' => '1', 'ToNo' => '9', 'PostalCode' => '400001']],
        ]);

        self::assertSame(4, $street->id);
        self::assertSame('Mihai Viteazu', $street->name);
        self::assertCount(1, $street->postalNumbers);
        self::assertSame('400001', $street->postalNumbers->first()->postalCode);
    }

    public function testBranchReadsCodAndCoordinates(): void
    {
        $branch = Branch::fromArray(['BranchId' => 2, 'COD' => '400001', 'Latitude' => 46.7, 'Longitude' => 23.6]);

        self::assertSame(2, $branch->id);
        self::assertSame('400001', $branch->cod);
        self::assertSame(46.7, $branch->latitude);
        self::assertSame(23.6, $branch->longitude);
    }

    public function testPudoPointReadsNestedPaymentType(): void
    {
        $point = PudoPoint::fromArray([
            'Id' => 5,
            'PointType' => 2,
            'ServiceCOD' => true,
            'AcceptedPaymentType' => ['Cash' => true, 'Card' => false, 'Online' => true],
        ]);

        self::assertSame(5, $point->id);
        self::assertSame(2, $point->pointType);
        self::assertTrue($point->serviceCod);
        self::assertTrue($point->acceptedPaymentType?->cash);
        self::assertFalse($point->acceptedPaymentType?->card);
        self::assertTrue($point->acceptedPaymentType?->online);
    }
}
