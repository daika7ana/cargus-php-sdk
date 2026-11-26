<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Resources;

use Daika7ana\Cargus\Client;
use Daika7ana\Cargus\DTOs\Financial\AwbCash;
use Daika7ana\Cargus\DTOs\Tracking\AwbTraceDelta;
use Daika7ana\Cargus\DTOs\Tracking\PackageEntity;
use Daika7ana\Cargus\DTOs\Tracking\ShortPackage;
use Daika7ana\Cargus\Exceptions\AuthenticationException;
use Daika7ana\Cargus\Exceptions\TransportException;
use Daika7ana\Cargus\Exceptions\UnexpectedStatusException;
use Daika7ana\Cargus\Exceptions\ValidationException;
use Daika7ana\Cargus\Support\ApiResponseMapper;
use Daika7ana\SimpleCollections\Collection;
use Psr\Http\Message\ResponseInterface;

/**
 * GET /api/AwbTrace*, /api/AwbScan, /api/AwbStatus/*,
 * /api/NoAuth/GetAwbTrace and /api/NoAuth/GetShortAwbTrace.
 */
final class TrackingResource
{
    private const string NO_SCAN = 'Cargus returned no scan: the requested AWB does not exist or has not been scanned yet.';

    public function __construct(private Client $client) {}

    /**
     * `GET /api/NoAuth/GetShortAwbTrace`, authenticated by the subscription key only.
     *
     * @return Collection<int, ShortPackage>
     *
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function shortTraceNoAuth(string $barCode): Collection
    {
        $response = $this->client->getNoAuth('/api/NoAuth/GetShortAwbTrace', ['barCode' => $barCode]);

        if ($response->getStatusCode() === 204) {
            return new Collection([]);
        }

        return $this->mapShortPackages($response);
    }

    /**
     * `GET /api/AwbTrace`.
     *
     * @return Collection<int, PackageEntity>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function trace(string $barCode): Collection
    {
        return $this->traceVia('/api/AwbTrace', $barCode);
    }

    /**
     * `GET /api/AwbTrace/GetAwbTraceOneRequest`.
     *
     * @return Collection<int, PackageEntity>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function traceOneRequest(string $barCode): Collection
    {
        return $this->traceVia('/api/AwbTrace/GetAwbTraceOneRequest', $barCode);
    }

    /**
     * `GET /api/AwbTrace/WithRedirect`.
     *
     * @return Collection<int, PackageEntity>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function traceWithRedirect(string $barCode): Collection
    {
        return $this->traceVia('/api/AwbTrace/WithRedirect', $barCode);
    }

    /**
     * `POST /api/AwbTrace/PostConfirmationDelivery`, no request body, all input in the query.
     *
     * @return Collection<int, PackageEntity>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function confirmDelivery(string $barCode, string $date, string $remarkComment): Collection
    {
        $response = $this->client->post('/api/AwbTrace/PostConfirmationDelivery', [], [
            'barCode' => $barCode,
            'date' => $date,
            'remarkComment' => $remarkComment,
        ]);

        return $this->mapPackages($response);
    }

    /**
     * `GET /api/AwbTrace/GetDeltaEvents`.
     *
     * @return Collection<int, AwbTraceDelta>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function deltaEvents(string $fromDate, string $toDate): Collection
    {
        $response = $this->client->get('/api/AwbTrace/GetDeltaEvents', [
            'FromDate' => $fromDate,
            'ToDate' => $toDate,
        ]);
        ApiResponseMapper::assertStatus($response, [200, 204]);

        if ($response->getStatusCode() === 204) {
            return new Collection();
        }

        return new Collection(array_map(
            static fn(array $row): AwbTraceDelta => AwbTraceDelta::fromArray($row),
            ApiResponseMapper::listOfArrays(ApiResponseMapper::decodeJson($response)),
        ));
    }

    /**
     * `GET /api/NoAuth/GetAwbTrace`, authenticated by the subscription key only.
     *
     * @return Collection<int, PackageEntity>
     *
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function traceNoAuth(string $barCode): Collection
    {
        $response = $this->client->getNoAuth('/api/NoAuth/GetAwbTrace', ['barCode' => $barCode]);

        return $this->mapPackages($response);
    }

    /**
     * `GET /api/AwbScan`; the spec models the picture as a string (`format: byte`).
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function scan(string $barCode): string
    {
        $response = $this->client->get('/api/AwbScan', ['barCode' => $barCode]);

        return $this->mapString($response);
    }

    /**
     * `GET /api/AwbStatus/GetAwbSyncStatus`.
     *
     * @return Collection<int, AwbCash>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function syncStatus(string $fromDate, string $toDate): Collection
    {
        $response = $this->client->get('/api/AwbStatus/GetAwbSyncStatus', [
            'FromDate' => $fromDate,
            'ToDate' => $toDate,
        ]);

        return $this->mapCashList($response);
    }

    /**
     * `GET /api/AwbStatus/GetAwbSyncStatusByTariff`.
     *
     * @return Collection<int, AwbCash>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function syncStatusByTariff(int $tariffId, string $fromDate, string $toDate): Collection
    {
        $response = $this->client->get('/api/AwbStatus/GetAwbSyncStatusByTariff', [
            'IdTariff' => $tariffId,
            'FromDate' => $fromDate,
            'ToDate' => $toDate,
        ]);

        return $this->mapCashList($response);
    }

    /**
     * `GET /api/AwbStatus/GetAwbSyncStatusByBarCode`; null on 204.
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function syncStatusByBarCode(string $barCode): ?AwbCash
    {
        $response = $this->client->get('/api/AwbStatus/GetAwbSyncStatusByBarCode', ['barCode' => $barCode]);

        if ($response->getStatusCode() === 204) {
            return null;
        }

        $payload = ApiResponseMapper::decodeJson($response);

        return is_array($payload) ? AwbCash::fromArray($payload) : null;
    }

    /**
     * @return Collection<int, PackageEntity>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    private function traceVia(string $path, string $barCode): Collection
    {
        return $this->mapPackages($this->client->get($path, ['barCode' => $barCode]));
    }

    /**
     * @return Collection<int, PackageEntity>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    private function mapPackages(ResponseInterface $response): Collection
    {
        if ($response->getStatusCode() === 204) {
            return new Collection([]);
        }

        return new Collection(array_map(
            static fn(array $row): PackageEntity => PackageEntity::fromArray($row),
            ApiResponseMapper::listOfArrays(ApiResponseMapper::decodeJson($response)),
        ));
    }

    /**
     * @return Collection<int, AwbCash>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    private function mapCashList(ResponseInterface $response): Collection
    {
        if ($response->getStatusCode() === 204) {
            return new Collection([]);
        }

        return new Collection(array_map(
            static fn(array $row): AwbCash => AwbCash::fromArray($row),
            ApiResponseMapper::listOfArrays(ApiResponseMapper::decodeJson($response)),
        ));
    }

    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     */
    private function mapString(ResponseInterface $response): string
    {
        return ApiResponseMapper::byteString($response, self::NO_SCAN);
    }

    /**
     * @return Collection<int, ShortPackage>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    private function mapShortPackages(ResponseInterface $response): Collection
    {
        $payload = ApiResponseMapper::decodeJson($response);

        return new Collection(array_map(
            static fn(array $row): ShortPackage => ShortPackage::fromArray($row),
            ApiResponseMapper::listOfArrays($payload),
        ));
    }
}
