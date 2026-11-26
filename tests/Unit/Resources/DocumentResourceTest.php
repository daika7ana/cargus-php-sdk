<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\Resources;

use Daika7ana\Cargus\Auth\Token;
use Daika7ana\Cargus\Exceptions\UnexpectedStatusException;
use Daika7ana\Cargus\Support\Document;
use Daika7ana\Cargus\Tests\Support\Fixture;
use Daika7ana\Cargus\Tests\Support\TestClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class DocumentResourceTest extends TestCase
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

    public function testAwbDocumentsSendsEveryQueryParameterAndReturnsTheString(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json('JVBERi0xLjQ='),
        );
        $client->setToken(new Token('abc'));

        $body = $client->documents()->awbDocuments('ABC123,ABC124', 'PDF', 1, 1, 0, 'Sender SRL', 2);

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/AwbDocuments', $http->lastRequest?->getUri()->getPath());
        self::assertSame([
            'barCodes' => 'ABC123,ABC124',
            'type' => 'PDF',
            'format' => '1',
            'printMainOnce' => '1',
            'printOneAwbPerPage' => '0',
            'senderName' => 'Sender SRL',
            'printReturn' => '2',
        ], self::query($http->lastRequest));
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
        self::assertSame('%PDF-1.4', $body);
    }

    public function testAwbDocumentsOmitsNullOptionalParameters(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json('%%%'),
        );
        $client->setToken(new Token('abc'));

        $result = $client->documents()->awbDocuments('ABC123', 'ZPL');

        self::assertSame(['barCodes' => 'ABC123', 'type' => 'ZPL'], self::query($http->lastRequest));
        self::assertSame('%%%', $result);
    }

    public function testAwbDocumentsDhlUsesItsOwnPath(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json('JVBERi0xLjQ='),
        );
        $client->setToken(new Token('abc'));

        self::assertSame('%PDF-1.4', $client->documents()->awbDocumentsDhl('ABC123', 'PDF', 0));

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/AwbDocuments/GetDHL', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['barCodes' => 'ABC123', 'type' => 'PDF', 'format' => '0'], self::query($http->lastRequest));
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
    }

    public function testOrderDocumentsSendsOrderId(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json('JVBERi0xLjQ='),
        );
        $client->setToken(new Token('abc'));

        self::assertSame('%PDF-1.4', $client->documents()->orderDocuments(42));

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/OrderDocuments', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['orderId' => '42', 'docType' => '0'], self::query($http->lastRequest));
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
    }

    public function testOrderDocumentsByDateSendsTheDateRange(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json('JVBERi0xLjQ='),
        );
        $client->setToken(new Token('abc'));

        $client->documents()->orderDocumentsByDate('2026-11-01', '2026-11-24');

        self::assertSame('/api/OrderDocuments/GetByDate', $http->lastRequest?->getUri()->getPath());
        self::assertSame([
            'FromDate' => '2026-11-01',
            'ToDate' => '2026-11-24',
            'docType' => '0',
        ], self::query($http->lastRequest));
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
    }

    public function testInvoiceDocumentsSendsInvoiceId(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json('JVBERi0xLjQ='),
        );
        $client->setToken(new Token('abc'));

        $client->documents()->invoiceDocuments(9);

        self::assertSame('/api/InvoiceDocuments', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['InvoiceId' => '9'], self::query($http->lastRequest));
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
    }

    public function testPdfAwbDocumentsReturnsABinaryDocumentWithoutABearerToken(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::raw('%PDF-1.4 label', 200, ['Content-Type' => 'application/pdf']),
        );

        $document = $client->documents()->pdfAwbDocuments('tok', 'ABC123', 1);

        self::assertSame('GET', $http->lastRequest?->getMethod());
        self::assertSame('/api/PDF/AwbDocuments', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['Token' => 'tok', 'barCodes' => 'ABC123', 'format' => '1'], self::query($http->lastRequest));
        self::assertFalse($http->lastRequest?->hasHeader('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
        self::assertInstanceOf(Document::class, $document);
        self::assertSame('%PDF-1.4 label', $document->contents);
        self::assertSame('application/pdf', $document->mimeType);
        self::assertTrue($document->isPdf());
    }

    public function testPdfOrderDocumentsSendsOrderId(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::raw('%PDF-1.4 order'),
        );

        $document = $client->documents()->pdfOrderDocuments('tok', 42);

        self::assertSame('/api/PDF/OrderDocuments', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['Token' => 'tok', 'orderId' => '42', 'docType' => '0'], self::query($http->lastRequest));
        self::assertFalse($http->lastRequest?->hasHeader('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
        self::assertTrue($document->isPdf());
    }

    public function testPdfInvoiceDocumentsSendsInvoiceId(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::raw('%PDF-1.4 invoice'),
        );

        $document = $client->documents()->pdfInvoiceDocuments('tok', 9);

        self::assertSame('/api/PDF/InvoiceDocuments', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['Token' => 'tok', 'InvoiceId' => '9'], self::query($http->lastRequest));
        self::assertFalse($http->lastRequest?->hasHeader('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
        self::assertTrue($document->isPdf());
    }

    public function testNoContentThrowsUnexpectedStatus(): void
    {
        [$client] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::raw('', 204),
        );
        $client->setToken(new Token('abc'));

        $this->expectException(UnexpectedStatusException::class);
        $this->expectExceptionMessage('Cargus returned no document: the requested AWB or document does not exist or is not available yet.');

        $client->documents()->awbDocuments('ABC123', 'PDF');
    }

    public function testNonStringBodyThrowsUnexpectedStatus(): void
    {
        [$client] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json(null),
        );
        $client->setToken(new Token('abc'));

        $this->expectException(UnexpectedStatusException::class);
        $this->expectExceptionMessage('Cargus returned no document: the requested AWB or document does not exist or is not available yet.');

        $client->documents()->orderDocuments(42);
    }

    public function testPdfNoContentThrowsUnexpectedStatus(): void
    {
        [$client] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::raw('', 204),
        );

        $this->expectException(UnexpectedStatusException::class);
        $this->expectExceptionMessage('Cargus returned no document: the requested AWB or document does not exist or is not available yet.');

        $client->documents()->pdfAwbDocuments('tok', 'ABC123');
    }

    public function testEmptyStringBodyThrowsUnexpectedStatus(): void
    {
        [$client] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json(''),
        );
        $client->setToken(new Token('abc'));

        $this->expectException(UnexpectedStatusException::class);
        $this->expectExceptionMessage('Cargus returned no document: the requested AWB or document does not exist or is not available yet.');

        $client->documents()->orderDocuments(42);
    }
}
