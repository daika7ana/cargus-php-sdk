<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\Resources;

use Daika7ana\Cargus\Auth\Token;
use Daika7ana\Cargus\DTOs\Complaint\ComplaintModel;
use Daika7ana\Cargus\Tests\Support\Fixture;
use Daika7ana\Cargus\Tests\Support\TestClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class ComplaintResourceTest extends TestCase
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

    public function testCheckAwbSendsTheBarCodeAsQuery(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([
                'ShipmentExists' => true,
                'ComplaintStatusRo' => 'Nou',
                'ComplaintStatusEn' => 'New',
            ]),
        );
        $client->setToken(new Token('abc'));

        $status = $client->complaints()->checkAwb('AWB1');

        self::assertSame('POST', $http->lastRequest?->getMethod());
        self::assertSame('/api/Complaints/CheckAwb', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['barCode' => 'AWB1'], self::query($http->lastRequest));
        self::assertSame('[]', (string) $http->lastRequest?->getBody());
        self::assertTrue($status->shipmentExists);
        self::assertSame('New', $status->complaintStatusEn);
    }

    public function testSaveComplaintPostsThePascalCaseBodyAndReturnsTheBoolean(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json(true),
        );
        $client->setToken(new Token('abc'));

        $saved = $client->complaints()->saveComplaint(new ComplaintModel(
            personName: 'Jane',
            email: 'jane@example.com',
            complaintDetails: 'Broken',
            shipment: 'AWB1',
        ));

        self::assertSame('POST', $http->lastRequest?->getMethod());
        self::assertSame('/api/Complaints/SaveComplaint', $http->lastRequest?->getUri()->getPath());
        self::assertSame([
            'Shipment' => 'AWB1',
            'PersonName' => 'Jane',
            'Email' => 'jane@example.com',
            'ComplaintDetails' => 'Broken',
        ], json_decode((string) $http->lastRequest?->getBody(), true));
        self::assertTrue($saved);
    }

    public function testCheckAwbMaps204ToNull(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::raw('', 204),
        );
        $client->setToken(new Token('abc'));

        $status = $client->complaints()->checkAwb('AWB1');

        self::assertSame('POST', $http->lastRequest?->getMethod());
        self::assertSame('/api/Complaints/CheckAwb', $http->lastRequest?->getUri()->getPath());
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertNull($status);
    }
}
