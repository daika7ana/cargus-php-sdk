<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\Resources;

use Daika7ana\Cargus\Auth\Token;
use Daika7ana\Cargus\DTOs\PickupLocation\AddressExtended;
use Daika7ana\Cargus\Tests\Support\Fixture;
use Daika7ana\Cargus\Tests\Support\TestClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class RecipientResourceTest extends TestCase
{
    public function testListGetsRecipientsAndMapsAddresses(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([[
                'LocationId' => 9,
                'Name' => 'Recipient',
                'CountyId' => 12,
                'CountyName' => 'Cluj',
                'LocalityId' => 5,
                'LocalityName' => 'Cluj-Napoca',
                'AddressText' => 'Str. B 2',
                'ContactPerson' => 'Ana',
                'PhoneNumber' => '0711111111',
                'Email' => 'ana@example.com',
                'CountryId' => 1,
            ]]),
        );
        $client->setToken(new Token('abc'));

        $recipients = $client->recipients()->list();

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/Recipients', $http->lastRequest?->getUri()->getPath());
        self::assertSame('', $http->lastRequest?->getUri()->getQuery());
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
        $first = $recipients->first();
        self::assertInstanceOf(AddressExtended::class, $first);
        self::assertSame(9, $first->locationId);
        self::assertSame('Recipient', $first->name);
        self::assertSame('Cluj', $first->countyName);
        self::assertSame('ana@example.com', $first->email);
    }
}
