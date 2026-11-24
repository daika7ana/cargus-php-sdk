<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Resources;

use Daika7ana\Cargus\Client;
use Daika7ana\Cargus\DTOs\Financial\AwbCash;
use Daika7ana\Cargus\DTOs\Financial\CashReceiptList;
use Daika7ana\Cargus\DTOs\Financial\Invoice;
use Daika7ana\Cargus\Exceptions\AuthenticationException;
use Daika7ana\Cargus\Exceptions\TransportException;
use Daika7ana\Cargus\Exceptions\UnexpectedStatusException;
use Daika7ana\Cargus\Exceptions\ValidationException;
use Daika7ana\Cargus\Support\ApiResponseMapper;
use Daika7ana\SimpleCollections\Collection;
use Psr\Http\Message\ResponseInterface;

/**
 * Invoices, cash account and cash receipts.
 */
final class FinancialResource
{
    public function __construct(public readonly Client $client) {}

    /**
     * @return Collection<int, Invoice>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function invoices(
        string $fromDate,
        string $toDate,
        ?int $itemsPerPage = null,
        ?int $pageNumber = null,
    ): Collection {
        $response = $this->client->get('/api/Invoices', [
            'FromDate' => $fromDate,
            'ToDate' => $toDate,
            'itemsPerPage' => $itemsPerPage,
            'pageNumber' => $pageNumber,
        ]);

        return $this->mapInvoices($response);
    }

    /**
     * @return Collection<int, AwbCash>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function cashAccount(?string $barCode = null): Collection
    {
        $response = $this->client->get('/api/CashAccount', ['barCode' => $barCode]);

        return $this->mapAwbCash($response);
    }

    /**
     * @return Collection<int, AwbCash>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function cashAccountByDate(
        string $fromDate,
        string $toDate,
        ?int $pageNumber = null,
        ?int $itemsPerPage = null,
    ): Collection {
        $response = $this->client->get('/api/CashAccount/GetByDate', [
            'FromDate' => $fromDate,
            'ToDate' => $toDate,
            'pageNumber' => $pageNumber,
            'itemsPerPage' => $itemsPerPage,
        ]);

        return $this->mapAwbCash($response);
    }

    /**
     * @return Collection<int, AwbCash>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function cashAccountByDeductionDate(
        string $deductionDate,
        ?int $pageNumber = null,
        ?int $itemsPerPage = null,
    ): Collection {
        $response = $this->client->get('/api/CashAccount/GetByDeductionDate', [
            'DeductionDate' => $deductionDate,
            'pageNumber' => $pageNumber,
            'itemsPerPage' => $itemsPerPage,
        ]);

        return $this->mapAwbCash($response);
    }

    /**
     * `POST /api/CashReceipts` returns the created receipt id.
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function cashReceipts(CashReceiptList $receipts): int
    {
        $payload = ApiResponseMapper::decodeJson(
            $this->client->post('/api/CashReceipts', $receipts->toArray()),
        );

        return is_int($payload) ? $payload : (int) $payload;
    }

    /**
     * @return Collection<int, Invoice>
     */
    private function mapInvoices(ResponseInterface $response): Collection
    {
        return $this->mapList($response, Invoice::fromArray(...));
    }

    /**
     * @return Collection<int, AwbCash>
     */
    private function mapAwbCash(ResponseInterface $response): Collection
    {
        return $this->mapList($response, AwbCash::fromArray(...));
    }

    /**
     * @template T
     *
     * @param callable(array<string, mixed>): T $factory
     *
     * @return Collection<int, T>
     */
    private function mapList(ResponseInterface $response, callable $factory): Collection
    {
        if ($response->getStatusCode() === 204) {
            return new Collection([]);
        }

        return new Collection(array_map(
            $factory,
            ApiResponseMapper::listOfArrays(ApiResponseMapper::decodeJson($response)),
        ));
    }
}
