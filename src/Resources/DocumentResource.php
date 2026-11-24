<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Resources;

use Daika7ana\Cargus\Client;
use Daika7ana\Cargus\Exceptions\AuthenticationException;
use Daika7ana\Cargus\Exceptions\TransportException;
use Daika7ana\Cargus\Exceptions\UnexpectedStatusException;
use Daika7ana\Cargus\Exceptions\ValidationException;
use Daika7ana\Cargus\Support\ApiResponseMapper;
use Daika7ana\Cargus\Support\Document;
use Psr\Http\Message\ResponseInterface;

/**
 * GET /api/AwbDocuments*, /api/OrderDocuments*, /api/InvoiceDocuments and
 * /api/PDF/*. The document endpoints return a JSON string (`format: byte`);
 * the PDF ones stream binary content.
 */
final class DocumentResource
{
    public function __construct(public readonly Client $client) {}

    /**
     * `GET /api/AwbDocuments`.
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function awbDocuments(
        string $barCodes,
        string $type,
        ?int $format = null,
        ?int $printMainOnce = null,
        ?int $printOneAwbPerPage = null,
        ?string $senderName = null,
        ?int $printReturn = null,
    ): string {
        return $this->mapString($this->client->get('/api/AwbDocuments', [
            'barCodes' => $barCodes,
            'type' => $type,
            'format' => $format,
            'printMainOnce' => $printMainOnce,
            'printOneAwbPerPage' => $printOneAwbPerPage,
            'senderName' => $senderName,
            'printReturn' => $printReturn,
        ]));
    }

    /**
     * `GET /api/AwbDocuments/GetDHL`.
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function awbDocumentsDhl(
        string $barCodes,
        string $type,
        ?int $format = null,
        ?int $printMainOnce = null,
        ?int $printOneAwbPerPage = null,
        ?string $senderName = null,
    ): string {
        return $this->mapString($this->client->get('/api/AwbDocuments/GetDHL', [
            'barCodes' => $barCodes,
            'type' => $type,
            'format' => $format,
            'printMainOnce' => $printMainOnce,
            'printOneAwbPerPage' => $printOneAwbPerPage,
            'senderName' => $senderName,
        ]));
    }

    /**
     * `GET /api/OrderDocuments`.
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function orderDocuments(int $orderId, int $docType = 0): string
    {
        return $this->mapString($this->client->get('/api/OrderDocuments', [
            'orderId' => $orderId,
            'docType' => $docType,
        ]));
    }

    /**
     * `GET /api/OrderDocuments/GetByDate`.
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function orderDocumentsByDate(string $fromDate, string $toDate, int $docType = 0): string
    {
        return $this->mapString($this->client->get('/api/OrderDocuments/GetByDate', [
            'FromDate' => $fromDate,
            'ToDate' => $toDate,
            'docType' => $docType,
        ]));
    }

    /**
     * `GET /api/InvoiceDocuments`.
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function invoiceDocuments(int $invoiceId): string
    {
        return $this->mapString($this->client->get('/api/InvoiceDocuments', ['InvoiceId' => $invoiceId]));
    }

    /**
     * `GET /api/PDF/AwbDocuments`; the spec takes the token as a query parameter.
     *
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function pdfAwbDocuments(
        string $token,
        string $barCodes,
        ?int $format = null,
        ?int $printMainOnce = null,
        ?int $printOneAwbPerPage = null,
    ): Document {
        $response = $this->client->getNoAuth('/api/PDF/AwbDocuments', [
            'Token' => $token,
            'barCodes' => $barCodes,
            'format' => $format,
            'printMainOnce' => $printMainOnce,
            'printOneAwbPerPage' => $printOneAwbPerPage,
        ]);

        return $this->mapDocument($response);
    }

    /**
     * `GET /api/PDF/OrderDocuments`; the spec takes the token as a query parameter.
     *
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function pdfOrderDocuments(string $token, int $orderId, int $docType = 0): Document
    {
        $response = $this->client->getNoAuth('/api/PDF/OrderDocuments', [
            'Token' => $token,
            'orderId' => $orderId,
            'docType' => $docType,
        ]);

        return $this->mapDocument($response);
    }

    /**
     * `GET /api/PDF/InvoiceDocuments`; the spec takes the token as a query parameter.
     *
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function pdfInvoiceDocuments(string $token, int $invoiceId): Document
    {
        $response = $this->client->getNoAuth('/api/PDF/InvoiceDocuments', [
            'Token' => $token,
            'InvoiceId' => $invoiceId,
        ]);

        return $this->mapDocument($response);
    }

    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    private function mapDocument(ResponseInterface $response): Document
    {
        if ($response->getStatusCode() === 204) {
            return new Document('');
        }

        return ApiResponseMapper::document($response);
    }

    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    private function mapString(ResponseInterface $response): string
    {
        if ($response->getStatusCode() === 204) {
            return '';
        }

        $payload = ApiResponseMapper::decodeJson($response);

        return is_string($payload) ? ApiResponseMapper::decodeByteString($payload) : '';
    }
}
