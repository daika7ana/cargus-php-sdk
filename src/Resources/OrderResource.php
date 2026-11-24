<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Resources;

use Daika7ana\Cargus\Client;
use Daika7ana\Cargus\DTOs\Order\Order;
use Daika7ana\Cargus\DTOs\Order\OrderPickupClickCollectModel;
use Daika7ana\Cargus\DTOs\Order\OrderPickupModel;
use Daika7ana\Cargus\Exceptions\AuthenticationException;
use Daika7ana\Cargus\Exceptions\TransportException;
use Daika7ana\Cargus\Exceptions\UnexpectedStatusException;
use Daika7ana\Cargus\Exceptions\ValidationException;
use Daika7ana\Cargus\Support\ApiResponseMapper;
use Daika7ana\SimpleCollections\Collection;
use Psr\Http\Message\ResponseInterface;

/**
 * Planned operations: GET/POST/PUT /api/Orders, /GetByDate, /GetByOrderId,
 * PUT /api/Orders/PutAll, POST /api/Orders/PostClickCollect and
 * PUT /api/Orders/Cancel.
 */
final class OrderResource
{
    public function __construct(private Client $client) {}

    /**
     * @return Collection<int, Order>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function get(int $locationId, int $status, ?int $pageNumber = null, ?int $itemsPerPage = null): Collection
    {
        $response = $this->client->get('/api/Orders', array_filter([
            'locationId' => $locationId,
            'status' => $status,
            'pageNumber' => $pageNumber,
            'itemsPerPage' => $itemsPerPage,
        ], static fn(mixed $value): bool => $value !== null));

        return $this->mapOrders($response);
    }

    /**
     * @return Collection<int, Order>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function getByDate(string $fromDate, string $toDate, ?int $pageNumber = null, ?int $itemsPerPage = null): Collection
    {
        $response = $this->client->get('/api/Orders/GetByDate', array_filter([
            'FromDate' => $fromDate,
            'ToDate' => $toDate,
            'pageNumber' => $pageNumber,
            'itemsPerPage' => $itemsPerPage,
        ], static fn(mixed $value): bool => $value !== null));

        return $this->mapOrders($response);
    }

    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function getByOrderId(int $orderId): ?Order
    {
        $response = $this->client->get('/api/Orders/GetByOrderId', ['orderId' => $orderId]);
        ApiResponseMapper::assertStatus($response, [200, 204]);

        if ($response->getStatusCode() === 204) {
            return null;
        }

        $payload = ApiResponseMapper::decodeJson($response);

        return Order::fromArray(is_array($payload) ? $payload : []);
    }

    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function create(OrderPickupModel $order): int
    {
        $response = $this->client->post('/api/Orders', $order->toArray());

        return (int) ApiResponseMapper::decodeJson($response);
    }

    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function update(int $locationId, string $pickupStartDate, string $pickupEndDate, int $action): int
    {
        $response = $this->client->put('/api/Orders', [], [
            'locationId' => $locationId,
            'PickupStartDate' => $pickupStartDate,
            'PickupEndDate' => $pickupEndDate,
            'action' => $action,
        ]);

        return (int) ApiResponseMapper::decodeJson($response);
    }

    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function updateAll(string $pickupStartDate, string $pickupEndDate, int $action): string
    {
        $response = $this->client->put('/api/Orders/PutAll', [], [
            'PickupStartDate' => $pickupStartDate,
            'PickupEndDate' => $pickupEndDate,
            'action' => $action,
        ]);

        return (string) ApiResponseMapper::decodeJson($response);
    }

    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function createClickCollect(OrderPickupClickCollectModel $order): int
    {
        $response = $this->client->post('/api/Orders/PostClickCollect', $order->toArray());

        return (int) ApiResponseMapper::decodeJson($response);
    }

    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function cancel(int $orderId): int
    {
        $response = $this->client->put('/api/Orders/Cancel', [], ['orderId' => $orderId]);

        return (int) ApiResponseMapper::decodeJson($response);
    }

    /**
     * @return Collection<int, Order>
     */
    private function mapOrders(ResponseInterface $response): Collection
    {
        ApiResponseMapper::assertStatus($response, [200, 204]);

        if ($response->getStatusCode() === 204) {
            return new Collection([]);
        }

        $payload = ApiResponseMapper::decodeJson($response);

        return new Collection(array_map(
            static fn(array $row): Order => Order::fromArray($row),
            ApiResponseMapper::listOfArrays($payload),
        ));
    }
}
