<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\Resources;

use Daika7ana\Cargus\Auth\Token;
use Daika7ana\Cargus\DTOs\Geography\County;
use Daika7ana\Cargus\Exceptions\ValidationException;
use Daika7ana\Cargus\Tests\Support\Fixture;
use Daika7ana\Cargus\Tests\Support\TestClientFactory;
use Daika7ana\SimpleCollections\Collection;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class GeographyResourceTest extends TestCase
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

    public function testCountiesRequestsPathQueryAndMapsTheCountyWrapper(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([
                [
                    'County' => [
                        ['CountyId' => 11, 'Name' => 'Bucuresti', 'Abbreviation' => 'B'],
                        ['CountyId' => 12, 'Name' => 'Cluj', 'Abbreviation' => 'CJ'],
                    ],
                    'message' => 'ok',
                ],
            ]),
        );
        $client->setToken(new Token('abc'));

        $counties = $client->geography()->counties(5);

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/Counties', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['countryId' => '5'], self::query($http->lastRequest));
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
        self::assertInstanceOf(Collection::class, $counties);
        self::assertCount(2, $counties);
        self::assertInstanceOf(County::class, $counties->first());
        self::assertSame(11, $counties->first()->id);
        self::assertSame('Bucuresti', $counties->first()->name);
        self::assertSame('B', $counties->first()->abbreviation);
    }

    public function testCountiesNoAuthSendsNoBearerToken(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([
                ['County' => [['CountyId' => 1, 'Name' => 'Alba', 'Abbreviation' => 'AB']], 'message' => 'ok'],
            ]),
        );

        $counties = $client->geography()->countiesNoAuth(2);

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/NoAuth/GetCounties', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['countryId' => '2'], self::query($http->lastRequest));
        self::assertFalse($http->lastRequest?->hasHeader('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
        self::assertSame('Alba', $counties->first()->name);
    }

    public function testNoContentReturnsAnEmptyCollection(): void
    {
        [$client] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::raw('', 204),
        );
        $client->setToken(new Token('abc'));

        $counties = $client->geography()->counties(1);

        self::assertCount(0, $counties);
    }

    public function testValidationErrorsAreMapped(): void
    {
        [$client] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([
                'message' => 'Invalid country',
                'errors' => ['countryId' => ['The countryId field is required.']],
            ], 422),
        );
        $client->setToken(new Token('abc'));

        try {
            $client->geography()->counties(0);
            self::fail('Expected ValidationException.');
        } catch (ValidationException $e) {
            self::assertSame(['countryId' => ['The countryId field is required.']], $e->errors);
            self::assertSame('Invalid country', $e->getMessage());
        }
    }

    public function testCountriesRequestsThePathAndMapsTheList(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([
                ['CountryId' => 1, 'CountryName' => 'Romania', 'Abbreviation' => 'RO'],
            ]),
        );
        $client->setToken(new Token('abc'));

        $countries = $client->geography()->countries();

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/Countries', $http->lastRequest?->getUri()->getPath());
        self::assertSame('', $http->lastRequest?->getUri()->getQuery());
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
        self::assertSame('Romania', $countries->first()->name);
        self::assertSame('RO', $countries->first()->abbreviation);
    }

    public function testLocalitiesSendsTheSpecQueryAndFlattensTheWrapper(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([
                [
                    'Locality' => [
                        ['LocalityId' => 7, 'Name' => 'Cluj-Napoca', 'CountyId' => 12, 'CountryId' => 1],
                    ],
                    'message' => 'ok',
                ],
            ]),
        );
        $client->setToken(new Token('abc'));

        $localities = $client->geography()->localities(1, 12, '400001');

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/Localities', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['countryId' => '1', 'countyId' => '12', 'postalCode' => '400001'], self::query($http->lastRequest));
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame('Cluj-Napoca', $localities->first()->name);
        self::assertSame(12, $localities->first()->countyId);
    }

    public function testLocalitiesNoAuthSendsOnlyTheSubscriptionKey(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([
                ['Locality' => [['LocalityId' => 3, 'Name' => 'Alba Iulia']], 'message' => 'ok'],
            ]),
        );

        $localities = $client->geography()->localitiesNoAuth(1, 2);

        self::assertSame('/api/NoAuth/GetLocalities', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['countryId' => '1', 'countyId' => '2'], self::query($http->lastRequest));
        self::assertFalse($http->lastRequest?->hasHeader('Authorization'));
        self::assertSame('Alba Iulia', $localities->first()->name);
    }

    public function testLocalityDetailsRequestsTheIdAndMapsTheObject(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([
                'LocalityId' => 9,
                'Name' => 'Turda',
                'ParentName' => 'Cluj',
                'InNetwork' => true,
                'SaturdayDelivery' => true,
            ]),
        );
        $client->setToken(new Token('abc'));

        $locality = $client->geography()->localityDetails(9);

        self::assertSame('/api/Localities/DetailsLocality', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['LocalityId' => '9'], self::query($http->lastRequest));
        self::assertSame(9, $locality->id);
        self::assertSame('Turda', $locality->name);
        self::assertTrue($locality->inNetwork);
        self::assertTrue($locality->saturdayDelivery);
    }

    public function testStreetsMapsThePostalNumbers(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([
                [
                    'StreetId' => 4,
                    'Name' => 'Mihai Viteazu',
                    'PostalNumbers' => [['FromNo' => '1', 'ToNo' => '9', 'PostalCode' => '400001']],
                ],
            ]),
        );
        $client->setToken(new Token('abc'));

        $streets = $client->geography()->streets(7);

        self::assertSame('/api/Streets', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['localityId' => '7'], self::query($http->lastRequest));
        self::assertSame('Mihai Viteazu', $streets->first()->name);
        self::assertSame('400001', $streets->first()->postalNumbers->first()->postalCode);
    }

    public function testBranchesMapsTheList(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([
                ['BranchId' => 2, 'Name' => 'Cluj', 'COD' => '400001', 'Latitude' => 46.7, 'Longitude' => 23.6],
            ]),
        );
        $client->setToken(new Token('abc'));

        $branches = $client->geography()->branches();

        self::assertSame('/api/Branches', $http->lastRequest?->getUri()->getPath());
        self::assertSame(2, $branches->first()->id);
        self::assertSame('400001', $branches->first()->cod);
        self::assertSame(46.7, $branches->first()->latitude);
    }

    public function testPudoPointsMapsTheNestedPaymentType(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([
                [
                    'Id' => 5,
                    'Name' => 'Pudo 1',
                    'PointType' => 2,
                    'ServiceCOD' => true,
                    'AcceptedPaymentType' => ['Cash' => true, 'Card' => false, 'Online' => true],
                ],
            ]),
        );
        $client->setToken(new Token('abc'));

        $points = $client->geography()->pudoPoints();

        self::assertSame('/api/PudoPoints', $http->lastRequest?->getUri()->getPath());
        self::assertSame(5, $points->first()->id);
        self::assertSame(2, $points->first()->pointType);
        self::assertTrue($points->first()->serviceCod);
        self::assertTrue($points->first()->acceptedPaymentType?->cash);
        self::assertFalse($points->first()->acceptedPaymentType?->card);
    }
}
