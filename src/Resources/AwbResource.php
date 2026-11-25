<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Resources;

use Daika7ana\Cargus\Client;
use Daika7ana\Cargus\DTOs\Awb\AwbExtended;
use Daika7ana\Cargus\DTOs\Awb\AwbModel;
use Daika7ana\Cargus\DTOs\Awb\AwbParcelUpdateModel;
use Daika7ana\Cargus\DTOs\Awb\AwbReturModel;
use Daika7ana\Cargus\DTOs\Awb\AwbTurModel;
use Daika7ana\Cargus\DTOs\Awb\AwbUpdate;
use Daika7ana\Cargus\DTOs\Awb\DeliveryRouteModel;
use Daika7ana\Cargus\DTOs\Awb\GeneralAwbModel;
use Daika7ana\Cargus\DTOs\Awb\IncomingAwb;
use Daika7ana\Cargus\DTOs\Awb\ReturnAwbModel;
use Daika7ana\Cargus\DTOs\Awb\RoutingDetail;
use Daika7ana\Cargus\Exceptions\AuthenticationException;
use Daika7ana\Cargus\Exceptions\TransportException;
use Daika7ana\Cargus\Exceptions\UnexpectedStatusException;
use Daika7ana\Cargus\Exceptions\ValidationException;
use Daika7ana\Cargus\Support\ApiResponseMapper;
use Daika7ana\SimpleCollections\Collection;
use Psr\Http\Message\ResponseInterface;

final class AwbResource
{
    public function __construct(private Client $client) {}

    /**
     * `GET /api/Awbs`, filtered by order id, AWB barcode or both.
     *
     * @return Collection<int, AwbExtended>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function get(?int $orderId = null, ?string $barCode = null): Collection
    {
        $response = $this->client->get('/api/Awbs', [
            'orderId' => $orderId,
            'barCode' => $barCode,
        ]);

        if ($response->getStatusCode() === 204) {
            return new Collection([]);
        }

        return new Collection(array_map(
            static fn(array $row): AwbExtended => AwbExtended::fromArray($row),
            ApiResponseMapper::listOfArrays(ApiResponseMapper::decodeJson($response)),
        ));
    }

    /**
     * `GET /api/Awbs/GetByDate`.
     *
     * @return Collection<int, AwbModel>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function getByDate(string $fromDate, string $toDate, ?int $pageNumber = null, ?int $itemsPerPage = null): Collection
    {
        $response = $this->client->get('/api/Awbs/GetByDate', [
            'FromDate' => $fromDate,
            'ToDate' => $toDate,
            'pageNumber' => $pageNumber,
            'itemsPerPage' => $itemsPerPage,
        ]);

        if ($response->getStatusCode() === 204) {
            return new Collection([]);
        }

        return new Collection(array_map(
            static fn(array $row): AwbModel => AwbModel::fromArray($row),
            ApiResponseMapper::listOfArrays(ApiResponseMapper::decodeJson($response)),
        ));
    }

    /**
     * `GET /api/Awbs/GetIncomingByDate`.
     *
     * @return Collection<int, IncomingAwb>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function getIncomingByDate(string $fromDate, string $toDate, int $destId): Collection
    {
        $response = $this->client->get('/api/Awbs/GetIncomingByDate', [
            'FromDate' => $fromDate,
            'ToDate' => $toDate,
            'DestId' => $destId,
        ]);

        return $this->mapIncoming($response);
    }

    /**
     * `GET /api/Awbs/GetIncomingByDateClickCollect`.
     *
     * @return Collection<int, IncomingAwb>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function getIncomingByDateClickCollect(string $fromDate, string $toDate, int $destId): Collection
    {
        $response = $this->client->get('/api/Awbs/GetIncomingByDateClickCollect', [
            'FromDate' => $fromDate,
            'ToDate' => $toDate,
            'DestId' => $destId,
        ]);

        return $this->mapIncoming($response);
    }

    /**
     * `POST /api/Awbs` returns the created AWB barcode.
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function create(AwbTurModel $awb): string
    {
        // The APIM gateway rejects AWB creation unless the request is routed via the Path: Crra header.
        $request = $this->client->createRequest('POST', '/api/Awbs')->withHeader('Path', 'Crra');

        $response = $this->client->send($this->client->withJsonBody($request, $awb->toArray()));

        if ($response->getStatusCode() === 204) {
            return '';
        }

        return (string) ApiResponseMapper::decodeJson($response);
    }

    /**
     * `POST /api/Awbs/WithGetAwb` returns the created AWBs with their server fields.
     *
     * @return Collection<int, AwbExtended>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function withGetAwb(AwbTurModel $awb): Collection
    {
        $response = $this->client->post('/api/Awbs/WithGetAwb', $awb->toArray());

        if ($response->getStatusCode() === 204) {
            return new Collection([]);
        }

        return new Collection(array_map(
            static fn(array $row): AwbExtended => AwbExtended::fromArray($row),
            ApiResponseMapper::listOfArrays(ApiResponseMapper::decodeJson($response)),
        ));
    }

    /**
     * `PUT /api/Awbs/PutReschedule`.
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function reschedule(string $barCode, string $date): bool
    {
        $response = $this->client->put('/api/Awbs/PutReschedule', query: [
            'BarCode' => $barCode,
            'data' => $date,
        ]);

        return self::writeResult($response);
    }

    /**
     * `PUT /api/Awbs/Update` answers with the updated AWB barcode, not the
     * spec's boolean; 204 maps to an empty string.
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function updateParcelCodes(AwbParcelUpdateModel $update): string
    {
        $response = $this->client->put('/api/Awbs/Update', $update->toArray());

        if ($response->getStatusCode() === 204) {
            return '';
        }

        return (string) ApiResponseMapper::decodeJson($response);
    }

    /**
     * `POST /api/Awbs/AwbRefUpdate`.
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function updateReference(AwbUpdate $update): bool
    {
        $response = $this->client->post('/api/Awbs/AwbRefUpdate', $update->toArray());

        return self::writeResult($response);
    }

    /**
     * `DELETE /api/Awbs`.
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function delete(string $barCode): bool
    {
        $response = $this->client->delete('/api/Awbs', ['barCode' => $barCode]);

        return self::writeResult($response);
    }

    /**
     * `POST /api/Awbs/GetRoutingAddress` returns null when the API has no route.
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function routingAddress(RoutingDetail $detail): ?DeliveryRouteModel
    {
        $response = $this->client->post('/api/Awbs/GetRoutingAddress', $detail->toArray());

        if ($response->getStatusCode() === 204) {
            return null;
        }

        $payload = ApiResponseMapper::decodeJson($response);

        return is_array($payload) ? DeliveryRouteModel::fromArray($payload) : null;
    }

    /**
     * `GET /api/AwbRetur`.
     *
     * @return Collection<int, AwbReturModel>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function returns(string $date): Collection
    {
        $response = $this->client->get('/api/AwbRetur', ['data' => $date]);

        if ($response->getStatusCode() === 204) {
            return new Collection([]);
        }

        return new Collection(array_map(
            static fn(array $row): AwbReturModel => AwbReturModel::fromArray($row),
            ApiResponseMapper::listOfArrays(ApiResponseMapper::decodeJson($response)),
        ));
    }

    /**
     * `POST /api/AwbGeneral/InternationalAwb` returns the created AWB barcode.
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function createInternational(GeneralAwbModel $awb): string
    {
        $response = $this->client->post('/api/AwbGeneral/InternationalAwb', $awb->toArray());

        if ($response->getStatusCode() === 204) {
            return '';
        }

        return (string) ApiResponseMapper::decodeJson($response);
    }

    /**
     * `POST /api/NoAuth/GenerateRwb`, authenticated by the subscription key only.
     *
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function generateReturnNoAuth(ReturnAwbModel $awb): string
    {
        $response = $this->client->postNoAuth('/api/NoAuth/GenerateRwb', $awb->toArray());

        return (string) ApiResponseMapper::decodeJson($response);
    }

    /**
     * @return Collection<int, IncomingAwb>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    private function mapIncoming(ResponseInterface $response): Collection
    {
        if ($response->getStatusCode() === 204) {
            return new Collection([]);
        }

        return new Collection(array_map(
            static fn(array $row): IncomingAwb => IncomingAwb::fromArray($row),
            ApiResponseMapper::listOfArrays(ApiResponseMapper::decodeJson($response)),
        ));
    }

    private static function writeResult(ResponseInterface $response): bool
    {
        $payload = ApiResponseMapper::decodeJson($response);

        return match ($payload) {
            true, 'True', 'true', 1, '1' => true,
            false, 'False', 'false', 0, '0' => false,
            default => throw new UnexpectedStatusException($response, 'AWB write response was not a boolean.'),
        };
    }
}
