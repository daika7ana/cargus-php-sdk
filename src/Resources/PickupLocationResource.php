<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Resources;

use Daika7ana\Cargus\Client;
use Daika7ana\Cargus\DTOs\PickupLocation\AddressExtended;
use Daika7ana\Cargus\DTOs\PickupLocation\AddressExtendedClickCollect;
use Daika7ana\Cargus\Exceptions\AuthenticationException;
use Daika7ana\Cargus\Exceptions\TransportException;
use Daika7ana\Cargus\Exceptions\UnexpectedStatusException;
use Daika7ana\Cargus\Exceptions\ValidationException;
use Daika7ana\Cargus\Support\ApiResponseMapper;
use Daika7ana\SimpleCollections\Collection;
use Psr\Http\Message\ResponseInterface;

/**
 * Planned operations: GET/POST/PUT/DELETE /api/PickupLocations,
 * /GetForClient, /AssignToUser, /GetClickCollect, /PostClickCollect,
 * /PutClickCollect and GET /api/PudoPoints.
 */
final class PickupLocationResource
{
    public function __construct(private Client $client) {}

    /**
     * @return Collection<int, AddressExtended>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function list(?int $idPickupLocation = null): Collection
    {
        $response = $this->client->get(
            '/api/PickupLocations',
            $idPickupLocation === null ? [] : ['idPickupLocation' => $idPickupLocation],
        );

        return $this->mapLocations($response);
    }

    /**
     * @return Collection<int, AddressExtended>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function listForClient(): Collection
    {
        return $this->mapLocations($this->client->get('/api/PickupLocations/GetForClient'));
    }

    /**
     * @return Collection<int, AddressExtendedClickCollect>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function listClickCollect(): Collection
    {
        $response = $this->client->get('/api/PickupLocations/GetClickCollect');
        ApiResponseMapper::assertStatus($response, [200, 204]);

        if ($response->getStatusCode() === 204) {
            return new Collection([]);
        }

        $payload = ApiResponseMapper::decodeJson($response);

        return new Collection(array_map(
            static fn(array $row): AddressExtendedClickCollect => AddressExtendedClickCollect::fromArray($row),
            ApiResponseMapper::listOfArrays($payload),
        ));
    }

    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function save(AddressExtended $location): int
    {
        $response = $this->client->post('/api/PickupLocations', $location->toArray());

        return (int) ApiResponseMapper::decodeJson($response);
    }

    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function update(AddressExtended $location): int
    {
        $response = $this->client->put('/api/PickupLocations', $location->toArray());

        return (int) ApiResponseMapper::decodeJson($response);
    }

    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function delete(int $idPickupLocation): int
    {
        $response = $this->client->delete('/api/PickupLocations', ['idPickupLocation' => $idPickupLocation]);
        ApiResponseMapper::assertStatus($response, [200, 204]);

        if ($response->getStatusCode() === 204) {
            return 0;
        }

        return (int) ApiResponseMapper::decodeJson($response);
    }

    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function saveClickCollect(AddressExtendedClickCollect $location): int
    {
        $response = $this->client->post('/api/PickupLocations/PostClickCollect', $location->toArray());

        return (int) ApiResponseMapper::decodeJson($response);
    }

    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function updateClickCollect(AddressExtendedClickCollect $location): int
    {
        $response = $this->client->put('/api/PickupLocations/PutClickCollect', $location->toArray());

        return (int) ApiResponseMapper::decodeJson($response);
    }

    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function assignToUser(int $locationId): int
    {
        $response = $this->client->post('/api/PickupLocations/AssignToUser', [], ['LocationId' => $locationId]);

        return (int) ApiResponseMapper::decodeJson($response);
    }

    /**
     * @return Collection<int, AddressExtended>
     */
    private function mapLocations(ResponseInterface $response): Collection
    {
        ApiResponseMapper::assertStatus($response, [200, 204]);

        if ($response->getStatusCode() === 204) {
            return new Collection([]);
        }

        $payload = ApiResponseMapper::decodeJson($response);

        return new Collection(array_map(
            static fn(array $row): AddressExtended => AddressExtended::fromArray($row),
            ApiResponseMapper::listOfArrays($payload),
        ));
    }
}
