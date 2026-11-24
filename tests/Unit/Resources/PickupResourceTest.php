<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\Resources;

use Daika7ana\Cargus\Auth\Token;
use Daika7ana\Cargus\DTOs\Pickup\AwbPickupIndividualModel;
use Daika7ana\Cargus\DTOs\Pickup\AwbPickupModel;
use Daika7ana\Cargus\DTOs\PickupLocation\AddressExtended;
use Daika7ana\Cargus\Tests\Support\Fixture;
use Daika7ana\Cargus\Tests\Support\TestClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class PickupResourceTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private static function body(RequestInterface $request): array
    {
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);

        return is_array($body) ? $body : [];
    }

    private static function pickup(): AwbPickupModel
    {
        return new AwbPickupModel(
            pickupStartDate: '2026-11-24T10:00:00',
            pickupEndDate: '2026-11-24T12:00:00',
            sender: new AddressExtended(name: 'Sender', countyId: 11, localityId: 1, addressText: 'Str. A 1'),
            recipient: new AddressExtended(name: 'Recipient', countyId: 12, localityId: 2, addressText: 'Str. B 2'),
            parcels: 2,
            envelopes: 1,
            totalWeight: 5,
            shipmentPayer: 1,
        );
    }

    public function testRequestPostsBodyAndReturnsAwbBarcode(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json('AWB123456'),
        );
        $client->setToken(new Token('abc'));

        $barcode = $client->pickup()->request(self::pickup());

        self::assertSame('POST', $http->lastRequest?->getMethod());
        self::assertSame('/api/AwbPickup', $http->lastRequest?->getUri()->getPath());
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
        $body = self::body($http->lastRequest);
        self::assertSame('2026-11-24T10:00:00', $body['PickupStartDate']);
        self::assertSame(2, $body['Parcels']);
        self::assertSame(1, $body['ShipmentPayer']);
        self::assertSame(['Name' => 'Sender', 'CountyId' => 11, 'LocalityId' => 1, 'AddressText' => 'Str. A 1'], $body['Sender']);
        self::assertSame('Recipient', $body['Recipient']['Name']);
        self::assertSame('AWB123456', $barcode);
    }

    public function testRequestWithGetAwbPostsSameBodyAndReturnsPayload(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json(['AwbBarcode' => 'AWB123456', 'Status' => 'Created']),
        );
        $client->setToken(new Token('abc'));

        $payload = $client->pickup()->requestWithGetAwb(self::pickup());

        self::assertSame('POST', $http->lastRequest?->getMethod());
        self::assertSame('/api/AwbPickup/WithGetAwb', $http->lastRequest?->getUri()->getPath());
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame(2, self::body($http->lastRequest)['Parcels']);
        self::assertSame(['AwbBarcode' => 'AWB123456', 'Status' => 'Created'], $payload);
    }

    public function testRequestIndividualNoAuthPostsBodyWithoutBearerToken(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json('AWB789'),
        );

        $barcode = $client->pickup()->requestIndividualNoAuth(new AwbPickupIndividualModel(
            pickUpPudoPoint: 7,
            pickupStartDate: '2026-11-24T10:00:00',
            pickupEndDate: '2026-11-24T12:00:00',
            sender: new AddressExtended(name: 'Sender'),
            recipient: new AddressExtended(name: 'Recipient'),
            parcels: 1,
            envelopes: 0,
            totalWeight: 1,
            shipmentPayer: 2,
            senderReturnAccountDetails: ['Name' => 'Sender', 'Iban' => 'RO49AAAA1B31007593840000'],
        ));

        self::assertSame('POST', $http->lastRequest?->getMethod());
        self::assertSame('/api/NoAuth/IndividualWithGetAwb', $http->lastRequest?->getUri()->getPath());
        self::assertFalse($http->lastRequest?->hasHeader('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
        $body = self::body($http->lastRequest);
        self::assertSame(7, $body['PickUpPudoPoint']);
        self::assertSame(['Name' => 'Sender', 'Iban' => 'RO49AAAA1B31007593840000'], $body['SenderReturnAccountDetails']);
        self::assertSame('AWB789', $barcode);
    }
}
