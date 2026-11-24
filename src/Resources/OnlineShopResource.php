<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Resources;

use Daika7ana\Cargus\Client;
use Daika7ana\Cargus\DTOs\OnlineShop\CustomerDetails;
use Daika7ana\Cargus\DTOs\OnlineShop\UserWorkstation;
use Daika7ana\Cargus\DTOs\OnlineShop\WorkstationDetails;
use Daika7ana\Cargus\Exceptions\AuthenticationException;
use Daika7ana\Cargus\Exceptions\TransportException;
use Daika7ana\Cargus\Exceptions\UnexpectedStatusException;
use Daika7ana\Cargus\Exceptions\ValidationException;
use Daika7ana\Cargus\Support\ApiResponseMapper;
use Daika7ana\SimpleCollections\Collection;
use Psr\Http\Message\ResponseInterface;

/**
 * Online shop customer and workstation lookups.
 */
final class OnlineShopResource
{
    public function __construct(public readonly Client $client) {}

    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function customerDetails(): CustomerDetails
    {
        $response = $this->client->get('/api/OnlineShop/GetCustomerDetails');

        return CustomerDetails::fromArray($this->decodeObject($response));
    }

    /**
     * @return Collection<int, UserWorkstation>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function userWorkstations(): Collection
    {
        $response = $this->client->get('/api/OnlineShop/GetUserWorkstations');

        if ($response->getStatusCode() === 204) {
            return new Collection([]);
        }

        return new Collection(array_map(
            static fn(array $row): UserWorkstation => UserWorkstation::fromArray($row),
            ApiResponseMapper::listOfArrays(ApiResponseMapper::decodeJson($response)),
        ));
    }

    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function workstation(int $id): WorkstationDetails
    {
        $response = $this->client->get('/api/OnlineShop/GetWorkstationById', ['id' => $id]);

        return WorkstationDetails::fromArray($this->decodeObject($response));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeObject(ResponseInterface $response): array
    {
        if ($response->getStatusCode() === 204) {
            return [];
        }

        $payload = ApiResponseMapper::decodeJson($response);

        return is_array($payload) ? $payload : [];
    }
}
